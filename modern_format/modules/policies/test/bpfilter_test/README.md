# bpfilter_test

## Objetivo

Tests del modulo `bpfilter` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/policies/common_policy_actions_bpf`
- `backend/checks/system_data/default_forms/forms_policies_bpf.json`
- `backend/checks/system_data/default_tables_structure/structure_tables_policies_bpf.json`
- `data/rules_bpfilter_human_viewer.json`
- `backend/commits/commit_task/convert_bpfilter.py`
- `backend/commits/commit_task/task_gen_bpfilter_policies.py`
- `backend/commits/commit_task/task_apply_bpfilter_policies.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_bpfilter_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module bpfilter
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
