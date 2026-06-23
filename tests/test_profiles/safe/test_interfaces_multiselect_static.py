#!/usr/bin/env python3
"""
Test: test_interfaces_multiselect_static.py

Objetivo:
    Proteger el soporte opt-in de campos multiselect en la tabla genérica.
    Asegurar que Bonds usa multiselect para interfaces sin cambiar los select existentes.

Objective:
    Protect opt-in multiselect field support in the generic table.
    Ensure Bonds uses multiselect for interfaces without changing existing select fields.
"""
import json
from pathlib import Path

repo = Path(__file__).resolve().parents[3]

generic_js = (repo / "web/my_js/generic_table.js").read_text()
forms = json.loads((repo / "backend/checks/system_data/default_forms/forms_interfaces.json").read_text())
forms_php = (repo / "web/interfaces/interfaces_table/get_forms_from_table.php").read_text()
validation_php = (repo / "web/interfaces/interfaces_table/validation_interface.php").read_text()
update_php = (repo / "web/interfaces/interfaces_table/get_update_interface.php").read_text()

assert "function genericIsMultiSelectField" in generic_js
assert "function genericCreateMultiSelectControl" in generic_js
assert "function genericReadMultiSelectControl" in generic_js
assert "formConfig?.multiselect?.[key]" in generic_js
assert "genericIsMultiSelectField(formConfig, key)" in generic_js

assert forms["bonds"].get("select", {}) == {}
assert forms["bonds"]["multiselect"]["interfaces"] == [""]
assert '$json["bonds"]["multiselect"]["interfaces"]' in forms_php
assert "validation_form_field_review(array $rule, ?string $chain = null)" in validation_php
assert "validation_form_field_review_multiselect" in validation_php
assert "validation_form_field_review($rule, $chain);" in update_php

print("PASS test_interfaces_multiselect_static")
