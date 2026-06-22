#!/usr/bin/env python3
"""
Test: test_required_project_docs.py

Objetivo:
    Comprobar documentacion minima esperada para BETA.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita publicar BETA sin documentos basicos de licencia, instalacion y limites.

Seguridad:
    Solo comprueba presencia de archivos.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
required = ['README.md', 'LICENSE']
recommended = ['NOTICE', 'THIRD_PARTY_LICENSES.md', 'SECURITY.md', 'docs/INSTALLATION.md', 'docs/TESTING.md']
missing_required = [rel for rel in required if not (root / rel).exists()]
missing_recommended = [rel for rel in recommended if not (root / rel).exists()]
errors = []
if missing_required:
    errors.append('faltan obligatorios: ' + ', '.join(missing_required))
if missing_recommended:
    errors.append('faltan recomendados BETA: ' + ', '.join(missing_recommended))
if errors:
    fail('required project docs', errors)
pass_('required project docs')
