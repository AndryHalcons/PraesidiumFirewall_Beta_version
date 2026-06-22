#!/usr/bin/env python3
"""
Helpers de rutas para tests de PraesidiumFirewall.

Objetivo:
    Centralizar la deteccion de la raiz del repo y rutas comunes para que los
    tests no dependan del directorio actual desde el que se ejecutan.

Seguridad:
    Este helper solo calcula rutas; no modifica archivos ni runtime.
"""
from pathlib import Path


def repo_root() -> Path:
    current = Path(__file__).resolve()
    for parent in current.parents:
        if (parent / '.git').exists() and (parent / 'web').exists() and (parent / 'backend').exists():
            return parent
    raise RuntimeError('No se pudo detectar la raiz del repo PraesidiumFirewall')


def tracked_files() -> list[str]:
    import subprocess
    root = repo_root()
    result = subprocess.run(['git', 'ls-files'], cwd=root, check=True, text=True, stdout=subprocess.PIPE)
    return [line.strip() for line in result.stdout.splitlines() if line.strip()]
