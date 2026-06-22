#!/usr/bin/env python3
"""
Guarda para tests destructivos.

Objetivo:
    Evitar que tests de commit/apply, red o servicios se ejecuten por accidente.

Uso:
    import destructive_guard; destructive_guard.require_lab_confirmation()
"""
import os
import sys


def require_lab_confirmation() -> None:
    if os.environ.get('PRAESIDIUM_ALLOW_DESTRUCTIVE') != '1':
        print('ERROR: test destructivo bloqueado. Define PRAESIDIUM_ALLOW_DESTRUCTIVE=1 solo en lab.', file=sys.stderr)
        raise SystemExit(2)
