#!/usr/bin/env python3
"""
Asserts reutilizables para tests modulares.

Objetivo:
    Evitar duplicar logica de lectura JSON, comprobacion de endpoints y cobertura
    estatica de validadores/generadores en cada modulo.

Seguridad:
    Todas las funciones son read-only sobre archivos versionados.
"""
from __future__ import annotations
from pathlib import Path
import json
import re
from repo_paths import repo_root
from report import fail, pass_
from module_metadata import MODULES


def module_root(module: str) -> Path:
    return repo_root() / MODULES[module]['module_path']


def module_rel(module: str, rel: str) -> Path:
    # ES: Traduce rutas legacy de tests al layout modular.
    # EN: Translate legacy test paths to the modular layout.
    if rel.startswith('data_running/'):
        rel = 'running_config/' + rel.removeprefix('data_running/')
    elif rel.startswith('data/'):
        rel = 'initial_config/' + rel.removeprefix('data/')
    path = module_root(module) / rel
    if path.exists():
        return path
    legacy_rel = rel.replace('backend/checks/system_data/default_forms/', 'backend/checks/default_forms/').replace('backend/checks/system_data/default_tables_structure/', 'backend/checks/default_tables_structure/')
    return module_root(module) / legacy_rel


def module_test_dir(module: str) -> Path:
    return module_root(module) / 'test' / MODULES[module]['dir']


def _load(rel: str):
    path = repo_root() / rel
    return json.loads(path.read_text(encoding='utf-8'))


def check_json_contract(module: str) -> None:
    cfg = MODULES[module]
    root = repo_root()
    errors: list[str] = []
    checked = 0
    for group in ('forms', 'structures', 'candidate', 'running'):
        for rel in cfg.get(group, []):
            checked += 1
            path = module_rel(module, rel)
            if not path.exists():
                errors.append(f'{rel}: no existe')
                continue
            try:
                data = json.loads(path.read_text(encoding='utf-8'))
            except Exception as exc:
                errors.append(f'{rel}: JSON invalido: {exc}')
                continue
            if not isinstance(data, dict):
                errors.append(f'{rel}: root JSON no es objeto')
            elif len(data) == 0:
                errors.append(f'{rel}: objeto JSON vacio')
    expected_keys = set(cfg.get('expected_keys', []))
    if expected_keys:
        available: set[str] = set()
        for rel in cfg.get('forms', []) + cfg.get('structures', []) + cfg.get('candidate', []) + cfg.get('running', []):
            path = module_rel(module, rel)
            if not path.exists():
                continue
            try:
                data = json.loads(path.read_text(encoding='utf-8'))
            except Exception:
                continue
            if isinstance(data, dict):
                available.update(data.keys())
        missing = sorted(expected_keys - available)
        if missing:
            errors.append(f'claves esperadas no encontradas en ningun JSON del modulo: {missing}')
    if errors:
        fail(f'{module} json contract', errors)
    pass_(f'{module} json contract', f'checked_json_files={checked}')


def check_endpoint_contract(module: str) -> None:
    cfg = MODULES[module]
    root = repo_root()
    errors: list[str] = []
    checked = 0
    for rel in cfg.get('endpoints', []):
        checked += 1
        path = module_rel(module, rel)
        if not path.exists():
            errors.append(f'{rel}: endpoint no existe')
            continue
        text = path.read_text(encoding='utf-8', errors='ignore')
        if '<?php' not in text[:200]:
            errors.append(f'{rel}: no parece PHP endpoint')
        is_validation_helper = Path(rel).name.startswith('validation_')
        if not is_validation_helper and 'json_encode' not in text and 'header(' not in text and 'readfile(' not in text:
            errors.append(f'{rel}: no hay salida JSON/header/readfile visible')
        is_mutating = any(marker in rel for marker in ['get_update', 'get_delete', 'get_save', 'commit_apply', 'reload_system_routes_running'])
        if is_mutating and 'csrf' not in text.lower():
            errors.append(f'{rel}: endpoint mutante sin CSRF visible')
        if is_mutating and not re.search(r'admin|require_admin|is_admin|role', text, re.I):
            errors.append(f'{rel}: endpoint mutante sin control admin visible')
    if errors:
        fail(f'{module} endpoint contract', errors)
    pass_(f'{module} endpoint contract', f'checked_endpoints={checked}')


def check_backend_static_coverage(module: str) -> None:
    cfg = MODULES[module]
    root = repo_root()
    errors: list[str] = []
    checked = 0
    combined = ''
    for rel in cfg.get('backend', []) + cfg.get('endpoints', []):
        path = module_rel(module, rel)
        if not path.exists():
            continue
        checked += 1
        combined += '\n' + path.read_text(encoding='utf-8', errors='ignore').lower()
    for term in cfg.get('validation_terms', []):
        if term.lower() not in combined:
            errors.append(f'termino de cobertura no encontrado en codigo del modulo: {term}')
    if errors:
        fail(f'{module} backend/static validation coverage', errors)
    pass_(f'{module} backend/static validation coverage', f'checked_code_files={checked}')


def check_invalid_fixtures(module: str) -> None:
    root = repo_root()
    fixture = module_test_dir(module) / 'fixtures' / 'invalid_payloads.json'
    if not fixture.exists():
        fail(f'{module} invalid fixtures', [f'{fixture.relative_to(root)}: no existe'])
    try:
        data = json.loads(fixture.read_text(encoding='utf-8'))
    except Exception as exc:
        fail(f'{module} invalid fixtures', [f'JSON invalido: {exc}'])
    errors: list[str] = []
    if not isinstance(data, dict) or not data:
        errors.append('fixture debe ser objeto no vacio')
    else:
        for name, payload in data.items():
            if not isinstance(name, str) or not name:
                errors.append('nombre de caso invalido')
            if not isinstance(payload, dict):
                errors.append(f'{name}: payload no es objeto')
    if errors:
        fail(f'{module} invalid fixtures', errors)
    pass_(f'{module} invalid fixtures', f'invalid_cases={len(data)}')
