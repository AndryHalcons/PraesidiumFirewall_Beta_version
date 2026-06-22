#!/usr/bin/env python3
"""
Checks de sistema para tests de laboratorio.

Objetivo:
    Envolver comandos como nft, dnsmasq, systemctl y sysctl con salida clara.

Seguridad:
    No se importa en tests safe salvo para comprobar disponibilidad. Los tests
    destructivos deben estar protegidos por PRAESIDIUM_ALLOW_DESTRUCTIVE=1.
"""
from __future__ import annotations
import subprocess


def run(command: list[str], check: bool = False) -> subprocess.CompletedProcess:
    return subprocess.run(command, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=check)


def command_available(name: str) -> bool:
    return run(['bash', '-lc', f'command -v {name} >/dev/null 2>&1']).returncode == 0
