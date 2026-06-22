#!/usr/bin/env python3
"""
Salida de resultados para tests.

Objetivo:
    Dar un formato simple y legible para PASS/FAIL sin depender de frameworks
    externos. Mas adelante se puede migrar a pytest si conviene.

Seguridad:
    Solo imprime resultados; no modifica estado.
"""
from __future__ import annotations


def fail(title: str, findings: list[str]) -> None:
    print(f'FAIL: {title}')
    for item in findings:
        print(f'  - {item}')
    raise SystemExit(1)


def pass_(title: str, details: str | None = None) -> None:
    print(f'PASS: {title}')
    if details:
        print(details)


def warn(title: str, findings: list[str]) -> None:
    print(f'WARN: {title}')
    for item in findings:
        print(f'  - {item}')
