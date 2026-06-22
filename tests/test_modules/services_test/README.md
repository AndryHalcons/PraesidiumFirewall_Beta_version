# services_test

## Objetivo

Tests del modulo `services` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/services/services.php`
- `web/services/services_table`
- `backend/checks/system_data/default_forms/forms_services.json`
- `backend/checks/system_data/default_tables_structure/structure_table_services.json`
- `data/services.json`
- `data_running/services.json`
- `backend/commits/commit_task/task_apply_services.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_services_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module services
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
