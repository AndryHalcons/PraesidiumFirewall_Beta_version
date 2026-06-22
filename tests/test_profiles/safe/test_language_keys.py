#!/usr/bin/env python3
"""
Test: test_language_keys.py

Objetivo:
    Comprobar que los ficheros de idioma ES/EN exponen las mismas claves.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita que una seccion muestre claves sin traducir o textos incompletos.

Seguridad:
    Ejecuta PHP solo para incluir los ficheros de idioma y exportar arrays; no modifica nada.
"""
from pathlib import Path
import json
import subprocess
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
script = r"""
<?php
function load_lang_file($path) {
    $value = include $path;
    if (is_array($value)) { return $value; }
    return [];
}
echo json_encode([
    'es' => load_lang_file('web/lang/es.php'),
    'en' => load_lang_file('web/lang/en.php'),
], JSON_UNESCAPED_UNICODE);
?>
"""
result = subprocess.run(['php'], cwd=root, input=script, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
if result.returncode != 0:
    fail('language keys', [result.stderr.strip() or 'php include failed'])
try:
    payload = json.loads(result.stdout)
except Exception as exc:
    fail('language keys', [f'PHP output no es JSON valido: {exc}', result.stdout[:300]])

es = payload.get('es') or {}
en = payload.get('en') or {}
errors = []
if not isinstance(es, dict) or not isinstance(en, dict):
    errors.append('Los idiomas no exportan arrays/dicts')
elif len(es) == 0 or len(en) == 0:
    errors.append(f'Los idiomas no pueden estar vacios: es={len(es)} en={len(en)}')
else:
    missing_en = sorted(set(es) - set(en))
    missing_es = sorted(set(en) - set(es))
    if missing_en:
        errors.append('Faltan en EN: ' + ', '.join(missing_en[:50]) + ('' if len(missing_en) <= 50 else f' ... total={len(missing_en)}'))
    if missing_es:
        errors.append('Faltan en ES: ' + ', '.join(missing_es[:50]) + ('' if len(missing_es) <= 50 else f' ... total={len(missing_es)}'))
    empty = [f'es:{k}' for k,v in es.items() if v in ('', None)] + [f'en:{k}' for k,v in en.items() if v in ('', None)]
    if empty:
        errors.append('Claves vacias: ' + ', '.join(empty[:50]))

if errors:
    fail('language keys', errors)
pass_('language keys', f'es={len(es)} en={len(en)}')
