# monitor_test

## Objetivo

Tests del modulo `monitor` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/monitor`
- `backend/checks/system_data/default_forms/forms_monitor.json`
- `backend/checks/system_data/default_tables_structure/structure_table_monitor.json`
- `backend/checks/check_monitor_log_extract/extract_monitor_log_nftables_for_get_user.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_monitor_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module monitor
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
