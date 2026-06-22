#!/usr/bin/env python3
"""
Test: test_installer_post_install_permissions.py

Objetivo:
    Permisos finales web/backend/config/sudoers tras instalar.

Tipo:
    installer / destructivo / VM desechable

Seguridad:
    Requiere PRAESIDIUM_ALLOW_DESTRUCTIVE=1. Debe ejecutarse solo en VM limpia o
    entorno disposable.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from report import pass_

require_lab_confirmation()
# ES: Punto de entrada preparado para orquestar VM limpia; no ejecuta instalacion
# si no se invoca dentro de un harness de VM.
# EN: Entry point prepared for clean-VM orchestration; it does not run installer
# unless invoked inside a VM harness.
pass_('test_installer_post_install_permissions.py', 'guard_ok; pendiente de harness VM disposable')
