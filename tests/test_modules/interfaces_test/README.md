# interfaces_test

## Objetivo

Tests del modulo `interfaces` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/interfaces`
- `backend/checks/system_data/default_forms/forms_interfaces.json`
- `backend/checks/system_data/default_tables_structure/structure_table_interfaces.json`
- `backend/commits/commit_task/task_gen_interface_config.py`
- `backend/commits/commit_task/task_apply_interface_config.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_interfaces_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module interfaces
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
