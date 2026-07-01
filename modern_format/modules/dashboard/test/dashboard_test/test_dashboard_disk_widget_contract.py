#!/usr/bin/env python3
"""
Test: test_dashboard_disk_widget_contract.py

Objetivo:
    Validar que el widget de disco del Panel tiene endpoint propio, integración
    frontend y un contrato JSON robusto para layouts de disco variables.
"""
from __future__ import annotations

import json
import subprocess
from pathlib import Path

import sys
for parent in Path(__file__).resolve().parents:
    test_lib = parent / 'tests' / 'lib'
    if test_lib.is_dir():
        sys.path.insert(0, str(test_lib))
        break
else:
    raise RuntimeError('tests/lib not found')
from repo_paths import repo_root
from report import fail, pass_

ROOT = repo_root()
DASHBOARD_ROOT = ROOT / 'modern_format/modules/dashboard'
WEBGUI_ROOT = ROOT / 'modern_format/system/web_gui/mainpage'


def php_session_include(path: str) -> dict:
    code = (
        "error_reporting(E_ALL); "
        "$_SERVER['DOCUMENT_ROOT']='/var/www/html'; "
        "session_start(); "
        "$_SESSION['username']='test'; "
        f"include '{path}';"
    )
    result = subprocess.run(
        ['php', '-r', code],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=20,
        check=False,
    )
    if result.returncode != 0:
        fail('dashboard disk widget contract', [f'endpoint PHP failed: {result.stderr.strip()}'])
    try:
        return json.loads(result.stdout)
    except json.JSONDecodeError as exc:
        fail('dashboard disk widget contract', [f'endpoint did not return JSON: {exc}', result.stdout[:300]])


def main() -> None:
    findings: list[str] = []

    endpoint = DASHBOARD_ROOT / 'web/dashboard/disk_stats.php'
    runtime_endpoint = Path('/var/www/html/dashboard/disk_stats.php')
    dashboard = DASHBOARD_ROOT / 'web/dashboard/dashboard.php'
    script = DASHBOARD_ROOT / 'web/dashboard/dashboard.js'
    css = WEBGUI_ROOT / 'styles.css'

    for path in [endpoint, dashboard, script, css]:
        if not path.exists():
            findings.append(f'missing {path.relative_to(ROOT)}')

    if not findings:
        endpoint_to_execute = runtime_endpoint if runtime_endpoint.exists() else endpoint
        data = php_session_include(str(endpoint_to_execute))
        summary = data.get('summary')
        mounts = data.get('mounts')
        if not isinstance(summary, dict):
            findings.append('summary is not an object')
        if not isinstance(mounts, list):
            findings.append('mounts is not a list')
        if isinstance(summary, dict):
            for key in ['total', 'used', 'available', 'used_percent', 'device_count']:
                if key not in summary:
                    findings.append(f'missing summary.{key}')
            if summary.get('total', 0) < summary.get('used', 0):
                findings.append('summary.total is lower than summary.used')
        if isinstance(mounts, list) and mounts:
            required = {'mountpoint', 'fstype', 'total', 'used', 'available', 'used_percent', 'status'}
            missing = required - set(mounts[0])
            if missing:
                findings.append(f'mount entry missing keys: {sorted(missing)}')

    dashboard_text = dashboard.read_text(encoding='utf-8') if dashboard.exists() else ''
    script_text = script.read_text(encoding='utf-8') if script.exists() else ''
    css_text = css.read_text(encoding='utf-8') if css.exists() else ''

    for needle in ['dashboard-disk-used-percent', 'dashboard-disk-mounts', 'dashboard_disk_usage']:
        if needle not in dashboard_text:
            findings.append(f'dashboard.php missing {needle}')
    for needle in ['/dashboard/disk_stats.php', 'updateDisk()', 'dashboard-disk-mount']:
        if needle not in script_text:
            findings.append(f'dashboard.js missing {needle}')
    for needle in ['dashboard-disk-bar', 'dashboard-disk-mounts', 'data-status="critical"']:
        if needle not in css_text:
            findings.append(f'styles.css missing {needle}')

    if findings:
        fail('dashboard disk widget contract', findings)
    pass_('dashboard disk widget contract')


if __name__ == '__main__':
    main()
