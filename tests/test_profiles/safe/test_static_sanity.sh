#!/usr/bin/env bash
# Test: test_static_sanity.sh
#
# Objetivo:
#   Ejecutar checks de sintaxis no destructivos sobre PHP, Python y Bash.
#
# Tipo:
#   safe / no destructivo
#
# Riesgo que cubre:
#   Evita subir cambios con sintaxis rota en archivos ejecutables.
#
# Seguridad:
#   No modifica archivos, servicios, red, candidate ni running.

set -u
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR" || exit 1

failures=0
checked_php=0
checked_py=0
checked_sh=0

echo "Checking PHP syntax..."
while IFS= read -r file; do
    checked_php=$((checked_php + 1))
    if ! php -l "$file" >/tmp/praesidium_php_lint.out 2>&1; then
        echo "FAIL php -l $file"
        cat /tmp/praesidium_php_lint.out
        failures=$((failures + 1))
    fi
done < <(git ls-files '*.php')

echo "Checking Python syntax..."
while IFS= read -r file; do
    case "$file" in
        backend/vendor/*) continue ;;
    esac
    checked_py=$((checked_py + 1))
    # Parseamos AST en modo read-only para no crear .pyc en __pycache__ root-owned.
    # AST parsing is read-only and avoids writing bytecode into root-owned __pycache__.
    if ! python3 - "$file" >/tmp/praesidium_py_compile.out 2>&1 <<'PYAST'
import ast, pathlib, sys
path = pathlib.Path(sys.argv[1])
ast.parse(path.read_text(encoding='utf-8', errors='ignore'), filename=str(path))
PYAST
    then
        echo "FAIL python ast parse $file"
        cat /tmp/praesidium_py_compile.out
        failures=$((failures + 1))
    fi
done < <(git ls-files '*.py')

echo "Checking Bash syntax..."
while IFS= read -r file; do
    checked_sh=$((checked_sh + 1))
    if ! bash -n "$file" >/tmp/praesidium_bash_lint.out 2>&1; then
        echo "FAIL bash -n $file"
        cat /tmp/praesidium_bash_lint.out
        failures=$((failures + 1))
    fi
done < <(git ls-files '*.sh')

if [ "$failures" -ne 0 ]; then
    echo "FAIL: static sanity found $failures failure(s)"
    exit 1
fi

echo "PASS: static sanity php=$checked_php python=$checked_py bash=$checked_sh"
