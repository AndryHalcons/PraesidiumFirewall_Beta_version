#!/usr/bin/env python3
"""
Test: test_no_test_bytecode_tracked.py

Objetivo:
    Verificar que la carpeta tests no versiona bytecode ni __pycache__.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita repetir el error de commitear artefactos generados dentro de tests/.

Seguridad:
    Solo lee git ls-files.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import tracked_files
from report import fail, pass_

bad = [rel for rel in tracked_files() if rel.startswith('tests/') and ('__pycache__' in rel or rel.endswith('.pyc'))]
if bad:
    fail('no test bytecode tracked', bad)
pass_('no test bytecode tracked')
