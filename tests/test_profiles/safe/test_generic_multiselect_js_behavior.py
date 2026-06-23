#!/usr/bin/env python3
"""
Test: test_generic_multiselect_js_behavior.py

Objetivo:
    Ejecutar el generic_table.js real contra un DOM mínimo simulado para comprobar
    el comportamiento del control multiselect: valor inicial CSV, añadir sin duplicar
    y eliminar con la X actualizando el CSV interno.

Objective:
    Run the real generic_table.js against a minimal simulated DOM to verify
    multiselect behaviour: initial CSV value, add without duplicates, and remove
    with the X updating the internal CSV.
"""
import subprocess
from pathlib import Path

repo = Path(__file__).resolve().parents[3]
js_path = repo / "web/my_js/generic_table.js"

node_script_template = r'''
const fs = require('fs');

class Element {
  constructor(tag) {
    this.tagName = tag.toUpperCase();
    this.children = [];
    this.dataset = {};
    this.className = '';
    this.textContent = '';
    this.value = '';
    this.type = '';
    this.onclick = null;
  }
  appendChild(child) { this.children.push(child); return child; }
  querySelector(selector) {
    const match = (el) => {
      if (selector === '.modal-multiselect') return String(el.className).split(' ').includes('modal-multiselect');
      if (selector === '.modal-multiselect-select') return String(el.className).split(' ').includes('modal-multiselect-select');
      if (selector === '.modal-multiselect-chips') return String(el.className).split(' ').includes('modal-multiselect-chips');
      if (selector === '.multiselect-chip-remove') return String(el.className).split(' ').includes('multiselect-chip-remove');
      if (selector === '.multiselect-add') return String(el.className).split(' ').includes('multiselect-add');
      if (selector === 'select') return el.tagName === 'SELECT';
      return false;
    };
    const stack = [...this.children];
    while (stack.length) {
      const el = stack.shift();
      if (match(el)) return el;
      stack.push(...el.children);
    }
    return null;
  }
}

global.document = { createElement: (tag) => new Element(tag) };
eval(fs.readFileSync('__GENERIC_TABLE_JS__', 'utf8'));

const control = genericCreateMultiSelectControl(['', 'eth0', 'ens19', 'ens20'], 'ens19,ens20');
if (control.dataset.values !== 'ens19,ens20') throw new Error('initial CSV mismatch: ' + control.dataset.values);

const select = control.querySelector('select');
const add = control.querySelector('.multiselect-add');
select.value = 'ens20';
add.onclick();
if (control.dataset.values !== 'ens19,ens20') throw new Error('duplicate was added: ' + control.dataset.values);

select.value = 'eth0';
add.onclick();
if (control.dataset.values !== 'ens19,ens20,eth0') throw new Error('add failed: ' + control.dataset.values);

const remove = control.querySelector('.multiselect-chip-remove');
remove.onclick();
if (control.dataset.values !== 'ens20,eth0') throw new Error('remove did not sync CSV: ' + control.dataset.values);

console.log('PASS generic multiselect JS behavior');
'''
node_script = node_script_template.replace("__GENERIC_TABLE_JS__", str(js_path))
result = subprocess.run(["node", "-e", node_script], cwd=repo, text=True, capture_output=True)
print(result.stdout, end="")
if result.returncode != 0:
    print(result.stderr, end="")
    raise SystemExit(result.returncode)
