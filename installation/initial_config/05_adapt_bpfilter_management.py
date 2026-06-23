#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import subprocess
from pathlib import Path
from typing import Any

INTERFACES_JSON = Path(os.environ.get('PRAESIDIUM_INTERFACES_JSON', '/var/www/config/interfaces.json'))
BPFILTER_RULES_JSON = Path(os.environ.get('PRAESIDIUM_BPFILTER_RULES_JSON', '/var/www/config/rules_bpfilter_human_viewer.json'))


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding='utf-8'))


def save_json(path: Path, data: dict[str, Any]) -> None:
    path.write_text(json.dumps(data, indent=4, ensure_ascii=False) + '\n', encoding='utf-8')


def default_route_interface() -> str:
    try:
        output = subprocess.check_output(
            ['ip', '-o', '-4', 'route', 'show', 'default'],
            text=True,
            stderr=subprocess.DEVNULL,
        )
    except Exception:
        return ''
    parts = output.split()
    for idx, part in enumerate(parts):
        if part == 'dev' and idx + 1 < len(parts):
            return parts[idx + 1]
    return ''


def first_ethernet() -> str:
    if not INTERFACES_JSON.exists():
        return ''
    data = load_json(INTERFACES_JSON)
    ethernets = data.get('network', {}).get('ethernets', {})
    if not isinstance(ethernets, dict):
        return ''
    return next(iter(ethernets), '')


def detect_management_interface() -> str:
    # BPFilter trabaja sobre interfaces físicas; no traduce ethernets a vmbr.
    # BPFilter works on physical interfaces; do not translate ethernets to vmbr.
    management_interface = default_route_interface() or first_ethernet()
    if not management_interface:
        raise SystemExit('No se pudo detectar la interfaz de gestión. / Could not detect management interface.')
    return management_interface


def adapt_bpfilter_rules(management_interface: str) -> None:
    if not BPFILTER_RULES_JSON.exists():
        raise SystemExit(f'{BPFILTER_RULES_JSON} not found')

    data = load_json(BPFILTER_RULES_JSON)
    updated = 0
    for item in data.get('bpfilter', []):
        rule = item.get('rule') if isinstance(item, dict) else None
        if not isinstance(rule, dict):
            continue
        # Solo adapta reglas default antiguas atadas a ens21.
        # Only adapt old default rules bound to ens21.
        if rule.get('interface') != 'ens21':
            continue
        rule['interface'] = management_interface
        hook = str(rule.get('hook', '')).lower()
        if hook and rule.get('chain'):
            rule['chain'] = f'{management_interface}_{hook}'
        updated += 1

    if updated == 0:
        already_adapted = any(
            isinstance(item, dict)
            and isinstance(item.get('rule'), dict)
            and item['rule'].get('interface') == management_interface
            for item in data.get('bpfilter', [])
        )
        if not already_adapted:
            raise SystemExit('No bpfilter default rules were adapted')

    save_json(BPFILTER_RULES_JSON, data)
    json.loads(BPFILTER_RULES_JSON.read_text(encoding='utf-8'))


def main() -> None:
    adapt_bpfilter_rules(detect_management_interface())


if __name__ == '__main__':
    main()
