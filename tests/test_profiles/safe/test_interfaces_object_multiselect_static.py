#!/usr/bin/env python3
"""
Test: test_interfaces_object_multiselect_static.py

Objetivo:
    Proteger que Interfaces expone object_multiselect solo en campos que deben
    poder recibir alias de dirección, y que el endpoint rellena opciones desde
    alias_address sin sugerir alias_addr_group.
"""
import json
from pathlib import Path

repo = Path(__file__).resolve().parents[3]
forms = json.loads((repo / "backend/checks/system_data/default_forms/forms_interfaces.json").read_text())
php = (repo / "web/interfaces/interfaces_table/get_forms_from_table.php").read_text()

expected = {
    # gateway4/gateway6 son campos legacy de Netplan y ya no deben exponerse como selectores de objetos.
    # gateway4/gateway6 are legacy Netplan fields and must no longer be exposed as object selectors.
    "ethernets": {"addresses", "nameservers.addresses", "routes.to", "routes.via"},
    "bonds": {"addresses", "nameservers.addresses", "routes.to", "routes.via"},
    "bridges": {"addresses", "nameservers.addresses", "routes.to", "routes.via"},
    "vlans": {"addresses", "nameservers.addresses", "routes.to", "routes.via"},
    "wifis": {"addresses", "nameservers.addresses", "routes.to", "routes.via"},
    "wireguard": {"addresses", "peers.allowed-ips", "routes.to", "routes.via", "routing-policy.from"},
}

for section, fields in expected.items():
    actual = set(forms[section].get("object_multiselect", {}).keys())
    assert actual == fields, f"{section} object_multiselect mismatch: {sorted(actual)} != {sorted(fields)}"

assert "function get_interface_address_alias_options" in php
assert "function populate_interface_object_multiselect_options" in php
assert "$aliasData['alias_address']" in php
assert "$aliasData['alias_addr_group']" not in php, "Interfaces UI must not suggest address groups"
for section in expected:
    assert f"populate_interface_object_multiselect_options($json['{section}']);" in php

print("PASS interfaces object_multiselect static")
