# nftables_test

## Objetivo

Tests del modulo `nftables` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/policies/common_policy_actions_nft`
- `backend/checks/system_data/default_forms/forms_policies_nft.json`
- `backend/checks/system_data/default_tables_structure/structure_tables_policies.json`
- `data/rules_nftables_human_viewer.json`
- `backend/commits/commit_task/convert_nftables.py`
- `backend/commits/commit_task/task_gen_nftables_policies.py`
- `backend/commits/commit_task/task_apply_nftables_policies.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_nftables_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module nftables
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
