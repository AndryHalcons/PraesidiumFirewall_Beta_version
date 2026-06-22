# squid_test

## Objetivo

Tests del modulo `squid` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/url_filter`
- `backend/checks/system_data/default_forms/forms_squid.json`
- `backend/checks/system_data/default_tables_structure/structure_table_squid.json`
- `data/squid_config/squid_policies.json`
- `backend/commits/commit_task/task_gen_squid_policy.py`
- `backend/commits/commit_task/task_apply_squid_policy.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_squid_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module squid
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
