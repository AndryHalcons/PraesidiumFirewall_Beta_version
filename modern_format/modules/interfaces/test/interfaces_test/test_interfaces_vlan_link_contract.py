#!/usr/bin/env python3
"""
Test: test_interfaces_vlan_link_contract.py

Objetivo:
    Verificar que VLAN link acepta todos los tipos de dispositivo que Netplan
    permite como definición subyacente en Praesidium: ethernets, bonds y bridges.

Tipo:
    modulo / no destructivo

Seguridad:
    Este test solo lee archivos versionados. No modifica candidate, running,
    servicios, firewall, red ni runtime del sistema.
"""
from pathlib import Path
import sys

for parent in Path(__file__).resolve().parents:
    test_lib = parent / 'tests' / 'lib'
    if test_lib.is_dir():
        sys.path.insert(0, str(test_lib))
        break
else:
    raise RuntimeError('tests/lib not found')
from module_assertions import module_rel
from report import fail, pass_

errors: list[str] = []

forms_php = module_rel('interfaces', 'web/interfaces/interfaces_table/get_forms_from_table.php')
validation_php = module_rel('interfaces', 'web/interfaces/interfaces_table/validation_interface.php')

forms = forms_php.read_text(encoding='utf-8')
validation = validation_php.read_text(encoding='utf-8')

expected_literal = '["ethernets", "bonds", "bridges"]'
if expected_literal not in forms:
    errors.append('get_forms_from_table.php no añade bridges a VLAN link junto a ethernets/bonds')

expected_validation_literal = "['ethernets', 'bonds', 'bridges']"
if expected_validation_literal not in validation:
    errors.append('validation_interface.php no valida bridges como VLAN link junto a ethernets/bonds')

for text, label in [(forms, 'forms'), (validation, 'validation')]:
    if 'VLAN link' not in text or 'bridges' not in text:
        errors.append(f'{label} no documenta claramente VLAN link con bridges')

if errors:
    fail('interfaces VLAN link contract', errors)
pass_('interfaces VLAN link contract')
