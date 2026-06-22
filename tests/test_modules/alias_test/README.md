# alias_test

## Objetivo

Tests del modulo `alias` de PraesidiumFirewall.

## Archivos del producto relacionados

- `web/alias`
- `backend/checks/system_data/default_forms/forms_alias.json`
- `backend/checks/system_data/default_tables_structure/structure_tables_alias.json`
- `data/alias.json`

## Tests incluidos

| Test | Tipo | Destructivo | Que verifica |
|------|------|-------------|--------------|
| `test_alias_structure_presence.py` | safe | no | Que los archivos principales del modulo existen y estan versionados. |

## Como ejecutar

```bash
./tests/run_tests.sh module alias
```

## Notas de seguridad

Los tests actuales de esta carpeta son no destructivos. Cuando se anadan tests `commit_cycle`, deberan exigir `PRAESIDIUM_ALLOW_DESTRUCTIVE=1` y restaurar estado.
