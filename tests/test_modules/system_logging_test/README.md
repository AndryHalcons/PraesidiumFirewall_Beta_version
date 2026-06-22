# system_logging_test

## Objetivo

Tests del modulo `system_logging` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/system/logging`
- `backend/checks/system_data/default_forms/forms_system_logging.json`
- `backend/checks/system_data/default_tables_structure/structure_table_system_logging.json`
- `data/system_logging.json`
- `data_running/system_logging.json`
- `backend/commits/commit_task/task_apply_system_logging.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_system_logging_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module system_logging
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
