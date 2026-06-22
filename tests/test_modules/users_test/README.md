# users_test

## Objetivo

Tests del modulo `users` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/users`
- `backend/checks/system_data/default_forms/forms_table_users.json`
- `backend/checks/system_data/default_tables_structure/structure_table_users.json`
- `data/users.json`
- `data_running/users.json`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_users_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module users
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
