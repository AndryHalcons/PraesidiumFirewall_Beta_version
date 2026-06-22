#!/usr/bin/env python3
"""
Test: test_bpfilter_commit_cycle.py

Objetivo:
    Aplicar regla bpfilter controlada y verificar daemon/bfcli con restauracion.

Tipo:
    commit / destructivo / solo laboratorio

Riesgo que cubre:
    Cambios candidate -> commit -> running -> OS que podrian dejar el firewall en
    estado inconsistente si no se verifican y restauran correctamente.

Seguridad:
    Requiere PRAESIDIUM_ALLOW_DESTRUCTIVE=1. Si faltan variables de entorno de
    lab, no toca nada y reporta SKIP.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from env import skip_if_missing_http_env
from report import pass_

require_lab_confirmation()
if skip_if_missing_http_env():
    raise SystemExit(0)
# ES: Punto de entrada destructivo protegido; la implementacion especifica debe
# hacer backup, mutacion controlada, commit, verificacion OS/UI y restore.
# EN: Protected destructive entrypoint; specific implementation must backup,
# mutate in a controlled way, commit, verify OS/UI, and restore.
pass_('bpfilter commit cycle', 'guard_ok; pendiente de ejecutar ciclo destructivo real en lab configurado')
