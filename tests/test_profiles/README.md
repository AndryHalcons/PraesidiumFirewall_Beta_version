# test_profiles

## Objetivo

Suites transversales por tipo de riesgo. Estas pruebas recorren varias areas del producto o comprueban contratos globales.

## Perfiles

- `safe`: no modifica nada.
- `validation`: payloads invalidos y validadores.
- `web`: contrato de endpoints/JSON para Web UI.
- `security`: auth, CSRF, descargas y permisos.
- `commit`: destructivo/lab.
- `e2e`: navegador/lab.
- `installer`: VM desechable.
