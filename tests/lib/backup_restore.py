#!/usr/bin/env python3
"""
Helpers de backup/restore para tests destructivos.

Objetivo:
    Proveer funciones pequenas y explicitas para guardar archivos antes de tocar
    candidate/running/configs del sistema en tests lab.

Seguridad:
    Las funciones de escritura solo deben usarse desde tests protegidos por
    PRAESIDIUM_ALLOW_DESTRUCTIVE=1.
"""
from __future__ import annotations
from pathlib import Path
import shutil
import tempfile


def make_backup_dir(prefix: str = 'praesidium-test-') -> Path:
    return Path(tempfile.mkdtemp(prefix=prefix))


def backup_file(path: Path, backup_dir: Path) -> Path | None:
    if not path.exists():
        return None
    target = backup_dir / path.name
    shutil.copy2(path, target)
    return target


def restore_file(original: Path, backup: Path | None) -> None:
    if backup and backup.exists():
        shutil.copy2(backup, original)
