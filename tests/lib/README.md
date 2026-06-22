# lib

## Objetivo

Helpers comunes para los tests: rutas, carga JSON, salida PASS/FAIL, guardas destructivas y utilidades HTTP/sistema.

## Regla

Los helpers no deben ejecutar cambios destructivos salvo que el test que los invoque haya pasado por `destructive_guard.py`.
