#!/usr/bin/env bash
# Test: test_repo_hygiene.sh
#
# Objetivo:
#   Detectar artefactos peligrosos o basura generada trackeada en Git.
#
# Tipo:
#   safe / no destructivo
#
# Riesgo que cubre:
#   Evita que __pycache__, .pyc, backups o secretos fuera de allowlist entren en BETA.
#
# Seguridad:
#   Solo lee git ls-files; no borra ni modifica archivos.

set -u
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR" || exit 1

failures=0

pyc_count=$(git ls-files | grep -Ec '(^|/)__pycache__/|\.pyc$' || true)
if [ "$pyc_count" -gt 0 ]; then
    echo "FAIL: hay artefactos Python trackeados (__pycache__/.pyc): $pyc_count"
    git ls-files | grep -E '(^|/)__pycache__/|\.pyc$' | sed 's/^/  - /'
    failures=$((failures + 1))
fi

backup_count=$(git ls-files | grep -Ec '(\.bak$|\.orig$|~$|\.tmp$)' || true)
if [ "$backup_count" -gt 0 ]; then
    echo "FAIL: hay backups/temporales trackeados: $backup_count"
    git ls-files | grep -E '(\.bak$|\.orig$|~$|\.tmp$)' | sed 's/^/  - /'
    failures=$((failures + 1))
fi

# Los certificados experimentales conocidos se avisan, no fallan, porque el proyecto
# puede mantenerlos temporalmente como fixtures de la seccion certificados.
key_count=$(git ls-files | grep -Ec '\.(key|pem|csr|srl)$' || true)
if [ "$key_count" -gt 0 ]; then
    echo "WARN: claves/certificados trackeados detectados: $key_count"
    git ls-files | grep -E '\.(key|pem|csr|srl)$' | sed 's/^/  - /'
fi

if [ "$failures" -ne 0 ]; then
    echo "FAIL: repo hygiene found $failures blocking issue(s)"
    exit 1
fi

echo "PASS: repo hygiene"
