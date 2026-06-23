#!/usr/bin/env python3
"""
Test: test_generic_send_save_error_behavior.py

Objetivo:
    Proteger que el guardado genérico no trata como éxito una respuesta JSON con
    `error`, y que solo ejecuta onSuccess cuando el backend devuelve
    `success: true`.

Objective:
    Protect that generic save does not treat a JSON `error` response as success,
    and only runs onSuccess when the backend returns `success: true`.
"""
import subprocess
from pathlib import Path

repo = Path(__file__).resolve().parents[3]
js_path = repo / "web/my_js/generic_table.js"

node_script = f'''
const fs = require('fs');
global.document = {{ querySelector: () => null, createElement: () => ({{}}) }};
global.getCsrfToken = () => 'csrf-token';
global.alert = (message) => {{ throw new Error('alert fallback should not run with onError: ' + message); }};
const logs = [];
console.log = (...args) => logs.push(args.join(' '));
console.error = (...args) => logs.push(args.join(' '));
eval(fs.readFileSync({str(js_path)!r}, 'utf8'));

async function run() {{
  let successCalls = 0;
  let errorMessages = [];
  global.fetch = () => Promise.resolve({{ text: () => Promise.resolve(JSON.stringify({{error: 'backend rejected value'}})) }});
  send_Generic('FORWARDING', '/fake', {{id:'1'}}, [], () => successCalls++, msg => errorMessages.push(msg));
  await new Promise(resolve => setTimeout(resolve, 0));
  if (successCalls !== 0) throw new Error('onSuccess called on backend error');
  if (errorMessages.join('|') !== 'backend rejected value') throw new Error('onError not called with backend message: ' + errorMessages.join('|'));

  successCalls = 0;
  errorMessages = [];
  global.fetch = () => Promise.resolve({{ text: () => Promise.resolve(JSON.stringify({{success: true}})) }});
  send_Generic('FORWARDING', '/fake', {{id:'1'}}, [], () => successCalls++, msg => errorMessages.push(msg));
  await new Promise(resolve => setTimeout(resolve, 0));
  if (successCalls !== 1) throw new Error('onSuccess not called on success true');
  if (errorMessages.length !== 0) throw new Error('onError called on success');

  successCalls = 0;
  errorMessages = [];
  global.fetch = () => Promise.resolve({{ text: () => Promise.resolve(JSON.stringify({{ok: true}})) }});
  send_Generic('FORWARDING', '/fake', {{id:'1'}}, [], () => successCalls++, msg => errorMessages.push(msg));
  await new Promise(resolve => setTimeout(resolve, 0));
  if (successCalls !== 0) throw new Error('onSuccess called on unexpected JSON');
  if (errorMessages.join('|') !== 'Respuesta inesperada del backend') throw new Error('unexpected JSON did not call onError');

  console.info('PASS generic send save error behavior');
}}

run().catch(error => {{ console.error(error); process.exit(1); }});
'''

result = subprocess.run(["node", "-e", node_script], cwd=repo, text=True, capture_output=True)
print(result.stdout, end="")
if result.returncode != 0:
    print(result.stderr, end="")
    raise SystemExit(result.returncode)
