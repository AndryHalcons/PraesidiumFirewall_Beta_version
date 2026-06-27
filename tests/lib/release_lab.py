#!/usr/bin/env python3
"""
Helpers reales para pruebas de release en laboratorio.

ES: Estos helpers NO ejecutan nada por si solos. Los tests destructivos deben
llamar a require_lab_confirmation() antes de usarlos.
EN: These helpers do not run anything by themselves. Destructive tests must call
require_lab_confirmation() before using them.
"""
from __future__ import annotations
import json
import os
import shutil
import subprocess
import time
from pathlib import Path
from urllib.parse import quote
import tempfile

from http_client import PraesidiumHttpClient
from repo_paths import repo_root
from report import fail, pass_, warn


CONFIG_DIR = Path(os.environ.get('PRAESIDIUM_CONFIG_DIR', '/var/www/config'))
CONFIG_RUNNING_DIR = Path(os.environ.get('PRAESIDIUM_CONFIG_RUNNING_DIR', '/var/www/config_running'))
BACKEND_DATA_DIR = Path(os.environ.get('PRAESIDIUM_BACKEND_DATA_DIR', '/var/www/backend/checks/system_data'))
TEST_BACKUP_ROOT = Path(os.environ.get('PRAESIDIUM_TEST_BACKUP_ROOT', '/tmp/praesidium-release-tests'))


def env_required(names: list[str]) -> dict[str, str]:
    missing = [name for name in names if not os.environ.get(name)]
    if missing:
        print('SKIP: faltan variables: ' + ', '.join(missing))
        raise SystemExit(0)
    return {name: os.environ[name] for name in names}


def http_client_from_env() -> PraesidiumHttpClient:
    env = env_required(['PRAESIDIUM_TEST_BASE_URL', 'PRAESIDIUM_TEST_ADMIN_USER', 'PRAESIDIUM_TEST_ADMIN_PASS'])
    client = PraesidiumHttpClient(env['PRAESIDIUM_TEST_BASE_URL'])
    if not client.login(env['PRAESIDIUM_TEST_ADMIN_USER'], env['PRAESIDIUM_TEST_ADMIN_PASS']):
        fail('login HTTP lab', ['no se pudo iniciar sesion con usuario admin de pruebas'])
    if not client.csrf_token:
        fail('login HTTP lab', ['no se encontro CSRF token en mainpage.php'])
    return client


def run_cmd(command: list[str], *, sudo: bool = False, check: bool = False, timeout: int = 120) -> subprocess.CompletedProcess:
    cmd = ['sudo', '-n'] + command if sudo else command
    return subprocess.run(cmd, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=timeout, check=check)


def backup_paths(label: str, paths: list[Path]) -> Path:
    backup_dir = TEST_BACKUP_ROOT / f'{label}-{int(time.time())}'
    backup_dir.mkdir(parents=True, exist_ok=False)
    manifest = []
    uid = str(os.getuid())
    gid = str(os.getgid())
    for path in paths:
        if not path.exists():
            manifest.append(f'MISSING {path}')
            continue
        target = backup_dir / path.as_posix().lstrip('/')
        target.parent.mkdir(parents=True, exist_ok=True)
        try:
            if path.is_dir():
                shutil.copytree(path, target, dirs_exist_ok=True)
            else:
                shutil.copy2(path, target)
        except (PermissionError, shutil.Error):
            run_cmd(['rm', '-rf', str(target)], sudo=True, check=True)
            run_cmd(['mkdir', '-p', str(target.parent)], sudo=True, check=True)
            run_cmd(['cp', '-a', str(path), str(target)], sudo=True, check=True)
            run_cmd(['chown', '-R', f'{uid}:{gid}', str(target)], sudo=True, check=True)
            run_cmd(['chmod', '-R', 'u+rwX', str(target)], sudo=True, check=True)
        manifest.append(f'COPIED {path} -> {target}')
    (backup_dir / 'MANIFEST.txt').write_text('\n'.join(manifest) + '\n', encoding='utf-8')
    return backup_dir


def _install_file_preserving_metadata(src: Path, dst: Path) -> None:
    try:
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(src, dst)
        return
    except PermissionError:
        pass
    run_cmd(['mkdir', '-p', str(dst.parent)], sudo=True, check=True)
    if dst.exists():
        run_cmd(['cp', '--preserve=mode,ownership,timestamps', str(src), str(dst)], sudo=True, check=True)
    else:
        run_cmd(['cp', str(src), str(dst)], sudo=True, check=True)


def restore_paths(backup_dir: Path) -> None:
    for stored in sorted(backup_dir.rglob('*')):
        if stored.is_dir() or stored.name == 'MANIFEST.txt':
            continue
        original = Path('/') / stored.relative_to(backup_dir)
        _install_file_preserving_metadata(stored, original)

def load_json(path: Path):
    return json.loads(path.read_text(encoding='utf-8'))


def save_json(path: Path, data) -> None:
    text = json.dumps(data, indent=2, ensure_ascii=False) + '\n'
    try:
        path.write_text(text, encoding='utf-8')
        return
    except PermissionError:
        pass
    fd, tmp_name = tempfile.mkstemp(prefix='praesidium-json-', suffix='.json')
    tmp = Path(tmp_name)
    try:
        with os.fdopen(fd, 'w', encoding='utf-8') as fh:
            fh.write(text)
        _install_file_preserving_metadata(tmp, path)
    finally:
        tmp.unlink(missing_ok=True)


def call_commit(client: PraesidiumHttpClient) -> dict:
    status, headers, body = client.post_json('/commits/check_commit/commit_apply/commit_apply.php', '{}', csrf=True)
    try:
        payload = json.loads(body)
    except Exception as exc:
        fail('commit HTTP JSON', [f'HTTP {status}', f'JSON invalido: {exc}', body[:500]])
    if status != 200:
        fail('commit HTTP status', [f'HTTP {status}', json.dumps(payload, ensure_ascii=False)[:800]])
    result = payload.get('commit_result') or {}
    details = payload.get('commit_details') or {}
    if result.get('status') not in ('ok', 'success'):
        fail('commit result status', [json.dumps(payload, indent=2, ensure_ascii=False)[:2000]])
    detail_text = json.dumps(details, ensure_ascii=False).lower()
    if 'fail' in detail_text or 'error' in detail_text:
        fail('commit details contain failure/error', [json.dumps(details, indent=2, ensure_ascii=False)[:3000]])
    return payload


def assert_service_active_or_known(name: str) -> None:
    result = run_cmd(['systemctl', 'is-active', name], sudo=False, check=False)
    if result.returncode != 0:
        fail(f'service {name} active', [result.stdout.strip(), result.stderr.strip()])


def assert_command_ok(name: str, command: list[str], timeout: int = 120) -> None:
    result = run_cmd(command, check=False, timeout=timeout)
    if result.returncode != 0:
        fail(name, [f'cmd={command}', f'rc={result.returncode}', result.stdout[-1000:], result.stderr[-1000:]])


def mutate_json_file(path: Path, mutator) -> None:
    data = load_json(path)
    new_data = mutator(data)
    save_json(path, new_data)


def commit_cycle(label: str, mutator, verifier, paths: list[Path] | None = None) -> None:
    client = http_client_from_env()
    paths = paths or [CONFIG_DIR, CONFIG_RUNNING_DIR]
    backup_dir = backup_paths(label, paths)
    try:
        mutator()
        payload = call_commit(client)
        verifier(payload)
    finally:
        restore_paths(backup_dir)
        # ES: Commit final de restauracion para devolver candidate/running al estado previo.
        # EN: Final restore commit to return candidate/running to previous state.
        try:
            call_commit(client)
        except SystemExit:
            raise
        except Exception as exc:
            warn('restore commit warning', [str(exc), f'backup_dir={backup_dir}'])


def require_tool(name: str) -> None:
    if shutil.which(name) is None:
        print(f'SKIP: falta herramienta requerida: {name}')
        raise SystemExit(0)


def ssh_command(host: str, command: str, timeout: int = 600) -> subprocess.CompletedProcess:
    return subprocess.run(['ssh', '-o', 'BatchMode=yes', host, command], text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=timeout)


def scp_to(src: Path, dest: str, timeout: int = 600) -> subprocess.CompletedProcess:
    return subprocess.run(['scp', '-r', str(src), dest], text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=timeout)
