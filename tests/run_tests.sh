#!/usr/bin/env bash
# Test runner principal de PraesidiumFirewall.
# Main PraesidiumFirewall test runner.
#
# Objetivo:
#   Ejecutar perfiles transversales o tests por modulo sin mezclar por accidente
#   tests seguros con tests destructivos de laboratorio.
#
# Seguridad:
#   Los perfiles destructivos requieren PRAESIDIUM_ALLOW_DESTRUCTIVE=1.

set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

PYTHON_BIN="${PYTHON_BIN:-python3}"
FAILED=0

run_cmd() {
    printf '
== %s ==
' "$*"
    "$@"
    code=$?
    if [ "$code" -ne 0 ]; then
        FAILED=1
    fi
    return "$code"
}

finish_profile() {
    if [ "$FAILED" -ne 0 ]; then
        echo "FAIL: one or more tests failed" >&2
        exit 1
    fi
}

require_destructive() {
    if [ "${PRAESIDIUM_ALLOW_DESTRUCTIVE:-0}" != "1" ]; then
        echo "ERROR: este perfil puede modificar servicios/red/runtime y requiere PRAESIDIUM_ALLOW_DESTRUCTIVE=1" >&2
        exit 2
    fi
}

run_safe() {
    run_cmd tests/test_profiles/safe/test_static_sanity.sh
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_json_parse.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_language_keys.py
    run_cmd tests/test_profiles/safe/test_repo_hygiene.sh
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_no_test_bytecode_tracked.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_readme_beta_repo_url.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_no_old_praesidium_repo_refs.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_bpfilter_repo_url_is_unchanged.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_test_readmes_complete.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_required_project_docs.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_installer_bpfilter_service.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/safe/test_installer_dnsmasq_dhcp_only.py
    finish_profile
}

run_validation() {
    run_cmd "$PYTHON_BIN" tests/test_profiles/validation/test_common_invalid_payloads.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/validation/test_invalid_fixture_catalog.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/validation/test_common_attack_strings_nonempty.py
    finish_profile
}

run_web() {
    run_cmd "$PYTHON_BIN" tests/test_profiles/web/test_generic_table_contract.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/web/test_endpoint_inventory.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/web/test_generic_table_js_contract.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/web/test_php_json_endpoints_no_closing_html_noise.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/web/test_http_login_smoke.py
    finish_profile
}

run_security() {
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_static_auth_csrf_matrix.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_no_destructive_profiles_without_guard.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_installer_shell_safety_static.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_sudoers_static_scope.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_shell_exec_escape_static.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_sensitive_download_headers_static.py
    run_cmd "$PYTHON_BIN" tests/test_profiles/security/test_http_mutating_endpoints_reject_without_csrf.py
    finish_profile
}


run_profile_dir() {
    profile_dir="$1"
    while IFS= read -r test_file; do
        case "$test_file" in
            *.py) run_cmd "$PYTHON_BIN" "$test_file" ;;
            *.sh) run_cmd "$test_file" ;;
        esac
    done < <(find "$profile_dir" -maxdepth 1 -type f \( -name 'test_*.py' -o -name 'test_*.sh' \) | sort)
    finish_profile
}

run_module() {
    module="${1:-}"
    if [ -z "$module" ]; then
        echo "Uso: ./tests/run_tests.sh module <nombre_modulo>" >&2
        exit 2
    fi
    case "$module" in
        nftables) module_dir="tests/test_modules/nftables_test" ;;
        bpfilter) module_dir="tests/test_modules/bpfilter_test" ;;
        dnsmasq|dhcp) module_dir="tests/test_modules/dnsmasq_test" ;;
        services) module_dir="tests/test_modules/services_test" ;;
        wireguard) module_dir="tests/test_modules/wireguard_test" ;;
        squid|url_filter) module_dir="tests/test_modules/squid_test" ;;
        users) module_dir="tests/test_modules/users_test" ;;
        certificates) module_dir="tests/test_modules/certificates_test" ;;
        interfaces) module_dir="tests/test_modules/interfaces_test" ;;
        monitor) module_dir="tests/test_modules/monitor_test" ;;
        system_logging) module_dir="tests/test_modules/system_logging_test" ;;
        alias) module_dir="tests/test_modules/alias_test" ;;
        *) echo "Modulo desconocido: $module" >&2; exit 2 ;;
    esac
    while IFS= read -r test_file; do
        case "$test_file" in
            *.py) run_cmd "$PYTHON_BIN" "$test_file" ;;
            *.sh) run_cmd "$test_file" ;;
        esac
    done < <(find "$module_dir" -maxdepth 1 -type f \( -name 'test_*.py' -o -name 'test_*.sh' \) | sort)
    finish_profile
}

profile="${1:-}"
case "$profile" in
    safe) run_safe ;;
    validation) run_validation ;;
    web) run_web ;;
    security) run_security ;;
    all-safe) run_safe; run_validation; run_web; run_security ;;
    commit) require_destructive; run_profile_dir tests/test_profiles/commit ;;
    e2e) require_destructive; run_profile_dir tests/test_profiles/e2e ;;
    installer) require_destructive; run_profile_dir tests/test_profiles/installer ;;
    all-lab) require_destructive; run_safe; run_validation; run_web; run_security; run_profile_dir tests/test_profiles/commit; run_profile_dir tests/test_profiles/e2e; run_profile_dir tests/test_profiles/installer ;;
    module) shift; run_module "${1:-}" ;;
    *)
        cat >&2 <<'USAGE'
Uso:
  ./tests/run_tests.sh safe
  ./tests/run_tests.sh validation
  ./tests/run_tests.sh web
  ./tests/run_tests.sh security
  ./tests/run_tests.sh all-safe
  ./tests/run_tests.sh module <nftables|bpfilter|dnsmasq|services|wireguard|squid|users|certificates|interfaces|monitor|system_logging|alias>
  PRAESIDIUM_ALLOW_DESTRUCTIVE=1 ./tests/run_tests.sh commit
USAGE
        exit 2
        ;;
esac
