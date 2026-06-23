#!/usr/bin/env python3
"""
Test: test_nftables_object_multiselect_static.py

Objetivo:
    Proteger que nftables declara object_multiselect únicamente para los campos
    que aceptan objetos Alias de dirección/servicio, y que el endpoint común
    rellena esas opciones desde alias.json.

Objective:
    Protect that nftables declares object_multiselect only for fields accepting
    address/service Alias objects, and that the common endpoint fills those
    options from alias.json.
"""
import json
from pathlib import Path

repo = Path(__file__).resolve().parents[3]
forms = json.loads((repo / 'backend/checks/system_data/default_forms/forms_policies_nft.json').read_text())
endpoint = (repo / 'web/policies/common_policy_actions_nft/get_forms_from_table.php').read_text()

expected_address_fields = {'ip.saddr', 'ip.daddr', 'dnat.addr', 'snat.addr'}
expected_service_fields = {'sport', 'dport', 'dnat.port', 'redirect'}
expected_all = expected_address_fields | expected_service_fields

object_fields = set(forms.get('object_multiselect', {}).keys())
assert object_fields == expected_all, object_fields

for field in expected_all:
    assert forms['object_multiselect'][field] == [], field

assert "get_alias_object_names_for_nft(['alias_address', 'alias_addr_group'])" in endpoint
assert "get_alias_object_names_for_nft(['alias_service', 'alias_service_group'])" in endpoint
assert "$addressFields = ['ip.saddr', 'ip.daddr', 'dnat.addr', 'snat.addr'];" in endpoint
assert "$serviceFields = ['sport', 'dport', 'dnat.port', 'redirect'];" in endpoint
assert '$formData = populate_nft_object_multiselect_options($formData);' in endpoint

print('PASS nftables object_multiselect static')
