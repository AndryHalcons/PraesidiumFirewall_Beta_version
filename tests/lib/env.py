#!/usr/bin/env python3
"""
Helpers de entorno para tests opcionales.

Objetivo:
    Centralizar lectura de variables para tests HTTP, E2E y destructivos.

Seguridad:
    No modifica estado; solo valida configuracion de entorno.
"""
from __future__ import annotations
import os


def base_url() -> str | None:
    return os.environ.get('PRAESIDIUM_TEST_BASE_URL')


def admin_user() -> str | None:
    return os.environ.get('PRAESIDIUM_TEST_ADMIN_USER')


def admin_pass() -> str | None:
    return os.environ.get('PRAESIDIUM_TEST_ADMIN_PASS')


def allow_destructive() -> bool:
    return os.environ.get('PRAESIDIUM_ALLOW_DESTRUCTIVE') == '1'


def skip_if_missing_http_env() -> bool:
    missing = [name for name in ['PRAESIDIUM_TEST_BASE_URL', 'PRAESIDIUM_TEST_ADMIN_USER', 'PRAESIDIUM_TEST_ADMIN_PASS'] if not os.environ.get(name)]
    if missing:
        print('SKIP: faltan variables HTTP: ' + ', '.join(missing))
        return True
    return False
