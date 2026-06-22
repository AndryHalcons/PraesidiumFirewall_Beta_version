#!/usr/bin/env python3
"""
Test: test_static_auth_csrf_matrix.py

Objetivo:
    Auditoria estatica inicial para comprobar que endpoints PHP mutantes usan
    controles de admin/CSRF de forma visible.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Detecta endpoints con php://input o mutacion que podrian aceptar cambios sin CSRF/admin.

Seguridad:
    Solo lee codigo PHP; no hace peticiones HTTP ni modifica runtime.
"""
from pathlib import Path
import re
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
errors = []
checked = 0
for rel in tracked_files():
    if not rel.startswith('web/') or not rel.endswith('.php'):
        continue
    endpoint_like = any(marker in rel for marker in [
        '/get_update', '/get_delete', '/get_save', '/get_download', '/get_client_config',
        '/get_client_qr', '/upload_files.php', '/commit_apply.php', '/reload_system_routes_running.php'
    ])
    if not endpoint_like:
        continue
    path = root / rel
    text = path.read_text(encoding='utf-8', errors='ignore')
    sensitive_or_mutating = any(token in text for token in ['php://input', 'file_put_contents', 'unlink(', 'shell_exec', 'exec(', 'sudo '])
    if not sensitive_or_mutating:
        continue
    checked += 1
    has_admin = bool(re.search(r'require_admin|is_admin|require_role|require_login|\$_SESSION\[[\'\"]role[\'\"]\]|admin', text, re.I))
    has_csrf = 'csrf' in text.lower()
    # Descargas sensibles pueden usar controles admin/login y cabeceras no-store sin CSRF.
    is_download = 'download' in rel.lower() or 'get_client_config' in rel or 'get_client_qr' in rel
    if not has_admin:
        errors.append(f'{rel}: endpoint sensible/mutante sin control auth/admin visible')
    if not has_csrf and not is_download and ('php://input' in text or 'file_put_contents' in text or 'unlink(' in text):
        errors.append(f'{rel}: endpoint mutante sin CSRF visible')

if errors:
    fail('static auth/csrf matrix', errors)
pass_('static auth/csrf matrix', f'checked_mutating_or_sensitive_php={checked}')
