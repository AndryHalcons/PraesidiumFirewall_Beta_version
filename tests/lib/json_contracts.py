#!/usr/bin/env python3
"""
Helpers de contratos JSON declarativos.

Objetivo:
    Reutilizar carga y validacion basica de JSON para tests de tablas/forms.

Seguridad:
    Solo lee archivos JSON del repo; no escribe ni aplica configuracion.
"""
from __future__ import annotations
from pathlib import Path
import json


def load_json(path: Path):
    return json.loads(path.read_text(encoding='utf-8'))


def ensure_object(value, path: Path) -> list[str]:
    if not isinstance(value, dict):
        return [f'{path}: root JSON no es objeto']
    return []


def known_form_types() -> set[str]:
    return {
        'text', 'number', 'select', 'checkbox', 'textarea', 'password', 'file',
        'hidden', 'not_editable', 'disable_add', 'disable_delete', 'button',
        'date', 'time'
    }
