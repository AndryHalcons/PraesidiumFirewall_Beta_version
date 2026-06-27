#!/usr/bin/env python3
"""
Test: test_interfaces_vlan_validation_contract.py

Objetivo:
    Proteger la validación específica de VLANs: id obligatorio, nombre vlan{id}
    y rechazo de duplicados id+link.

Tipo:
    modulo / no destructivo

Seguridad:
    Este test solo lee archivos versionados. No modifica candidate, running,
    servicios, firewall, red ni runtime del sistema.
"""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

repo = repo_root()
errors: list[str] = []

update_php = repo / 'web/interfaces/interfaces_table/get_update_interface.php'
validation_php = repo / 'web/interfaces/interfaces_table/validation_interface.php'

update = update_php.read_text(encoding='utf-8')
validation = validation_php.read_text(encoding='utf-8')

if 'function validate_and_normalize_vlan_rule(array $rule, array $config): array' not in validation:
    errors.append('falta validate_and_normalize_vlan_rule()')

required_fragments = [
    "VLAN id obligatorio",
    "VLAN id debe ser numérico",
    "VLAN id debe estar entre 1 y 4094",
    "VLAN link obligatorio",
    "$expectedName = 'vlan' . $id;",
    "VLAN name debe ser",
    "Ya existe VLAN id",
]
for fragment in required_fragments:
    if fragment not in validation:
        errors.append(f'falta validación VLAN: {fragment}')

start = update.find('function get_vlans($chain)')
end = update.find('function get_wireguard($chain)', start)
if start == -1 or end == -1:
    errors.append('no se pudo localizar get_vlans()')
else:
    vlan_region = update[start:end]
    if 'validate_and_normalize_vlan_rule($rule, $json)' not in vlan_region:
        errors.append('get_vlans() no usa validate_and_normalize_vlan_rule()')
    if 'check_create_Name($rule, $chain)' in vlan_region:
        errors.append('get_vlans() todavía usa check_create_Name()')

if errors:
    fail('interfaces VLAN validation contract', errors)
pass_('interfaces VLAN validation contract')
