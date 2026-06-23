#!/usr/bin/env python3
"""
Test: test_object_multiselect_js_behavior.py

Objetivo:
    Ejecutar generic_table.js con un DOM mínimo simulado y comprobar el selector
    de objetos: no muestra resultados hasta 3 caracteres, filtra con 3+
    caracteres, limita a 10 resultados, añade chips desde objetos y valores
    manuales confirmados con coma, evita duplicados y la X sincroniza el CSV.

Objective:
    Run generic_table.js with a minimal simulated DOM and verify the object
    selector: shows no results until 3 characters, filters with 3+ characters,
    limits to 10 results, adds chips from objects and comma-confirmed manual
    values, prevents duplicates, and X syncs the CSV.
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
    this.placeholder = '';
    this.onclick = null;
    this.onfocus = null;
    this.oninput = null;
  }
  appendChild(child) { this.children.push(child); return child; }
  get innerHTML() { return this.children.map(c => c.textContent).join(''); }
  set innerHTML(value) { this.children = []; }
  querySelector(selector) {
    const all = this.querySelectorAll(selector);
    return all.length ? all[0] : null;
  }
  querySelectorAll(selector) {
    const match = (el) => {
      if (selector.startsWith('.')) return String(el.className).split(' ').includes(selector.slice(1));
      if (selector === 'input') return el.tagName === 'INPUT';
      if (selector === 'button') return el.tagName === 'BUTTON';
      return false;
    };
    const found = [];
    const stack = [...this.children];
    while (stack.length) {
      const el = stack.shift();
      if (match(el)) found.push(el);
      stack.push(...el.children);
    }
    return found;
  }
}

global.document = { createElement: (tag) => new Element(tag) };
eval(fs.readFileSync('__GENERIC_TABLE_JS__', 'utf8'));

const options = [
  'alpha-one','alpha-two','alpha-three','alpha-four','alpha-five',
  'alpha-six','alpha-seven','alpha-eight','alpha-nine','alpha-ten',
  'alpha-eleven','beta-one','gamma-one'
];
const control = genericCreateObjectMultiSelectControl(options, 'alpha-one,beta-one');
const dropdown = control.querySelector('.object-multiselect-dropdown');
let shown = dropdown.querySelectorAll('.object-multiselect-option').map(o => o.textContent);
if (shown.length !== 0) throw new Error('initial options must stay empty before 3 chars: ' + shown.length);

const search = control.querySelector('.object-multiselect-search');
search.value = 'ga';
search.oninput();
shown = dropdown.querySelectorAll('.object-multiselect-option').map(o => o.textContent);
if (shown.length !== 0) throw new Error('2 chars must show no options: ' + shown.join(','));

search.value = 'alp';
search.oninput();
shown = dropdown.querySelectorAll('.object-multiselect-option').map(o => o.textContent);
if (shown.length !== 10) throw new Error('3 chars must show at most 10 options: ' + shown.length);
if (shown.includes('alpha-one') || shown.includes('beta-one')) throw new Error('selected values shown again: ' + shown.join(','));

search.value = 'gam';
search.oninput();
shown = dropdown.querySelectorAll('.object-multiselect-option').map(o => o.textContent);
if (shown.join(',') !== 'gamma-one') throw new Error('3 chars must filter to gamma-one: ' + shown.join(','));

dropdown.querySelector('.object-multiselect-option').onclick();
if (control.dataset.values !== 'alpha-one,beta-one,gamma-one') throw new Error('add object failed: ' + control.dataset.values);

search.value = 'gam';
search.oninput();
shown = dropdown.querySelectorAll('.object-multiselect-option').map(o => o.textContent);
if (shown.includes('gamma-one')) throw new Error('duplicate object option displayed');

search.value = '1.1.1.1,';
search.oninput();
if (control.dataset.values !== 'alpha-one,beta-one,gamma-one,1.1.1.1') throw new Error('manual IP comma add failed: ' + control.dataset.values);
if (search.value !== '') throw new Error('manual comma should clear exact completed input: ' + search.value);

search.value = '443,8000-9000,partial';
search.oninput();
if (control.dataset.values !== 'alpha-one,beta-one,gamma-one,1.1.1.1,443,8000-9000') throw new Error('multiple manual comma add failed: ' + control.dataset.values);
if (search.value !== 'partial') throw new Error('pending text after manual comma not preserved: ' + search.value);

search.value = '443,';
search.oninput();
if (control.dataset.values !== 'alpha-one,beta-one,gamma-one,1.1.1.1,443,8000-9000') throw new Error('duplicate manual value added: ' + control.dataset.values);

const remove = control.querySelector('.multiselect-chip-remove');
remove.onclick();
if (control.dataset.values !== 'beta-one,gamma-one,1.1.1.1,443,8000-9000') throw new Error('remove did not sync CSV: ' + control.dataset.values);

console.log('PASS object multiselect JS behavior');
'''
node_script = node_script_template.replace("__GENERIC_TABLE_JS__", str(js_path))
result = subprocess.run(["node", "-e", node_script], cwd=repo, text=True, capture_output=True)
print(result.stdout, end="")
if result.returncode != 0:
    print(result.stderr, end="")
    raise SystemExit(result.returncode)
