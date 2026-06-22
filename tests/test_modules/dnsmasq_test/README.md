# dnsmasq_test

## Objetivo

Tests del modulo `dnsmasq` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/networking/dhcp_table`
- `backend/checks/system_data/default_forms/forms_dhcp.json`
- `backend/checks/system_data/default_tables_structure/structure_table_dhcp.json`
- `data/dhcp.json`
- `data_running/dhcp.json`
- `backend/commits/commit_task/task_gen_dhcp_config.py`
- `backend/commits/commit_task/task_apply_dhcp_config.py`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_dnsmasq_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module dnsmasq
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
