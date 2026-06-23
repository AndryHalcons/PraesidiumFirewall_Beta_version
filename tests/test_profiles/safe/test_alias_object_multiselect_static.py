#!/usr/bin/env python3
"""
Test: test_alias_object_multiselect_static.py

Objetivo:
    Proteger el soporte opt-in object_multiselect para grupos de Alias.
    Asegurar que solo alias_addr_group.content y alias_service_group.content usan
    el selector buscable de objetos.

Objective:
    Protect opt-in object_multiselect support for Alias groups.
    Ensure only alias_addr_group.content and alias_service_group.content use the
    searchable object selector.
"""
import json
from pathlib import Path

repo = Path(__file__).resolve().parents[3]
forms = json.loads((repo / "backend/checks/system_data/default_forms/forms_alias.json").read_text())
generic_js = (repo / "web/my_js/generic_table.js").read_text()
forms_php = (repo / "web/alias/common_alias_actions/get_forms_from_table.php").read_text()

assert "function genericIsObjectMultiSelectField" in generic_js
assert "function genericCreateObjectMultiSelectControl" in generic_js
assert "function genericReadObjectMultiSelectControl" in generic_js
assert "slice(0, 10)" in generic_js
assert "cleanTerm.length >= 3" in generic_js

assert "object_multiselect" not in forms["alias_address"]
assert "object_multiselect" not in forms["alias_service"]
assert forms["alias_addr_group"]["object_multiselect"]["content"] == []
assert forms["alias_service_group"]["object_multiselect"]["content"] == []

assert "get_alias_object_names('alias_address')" in forms_php
assert "get_alias_object_names('alias_service')" in forms_php
assert "$json['alias_addr_group']['object_multiselect']['content']" in forms_php
assert "$json['alias_service_group']['object_multiselect']['content']" in forms_php

print("PASS alias object_multiselect static")
