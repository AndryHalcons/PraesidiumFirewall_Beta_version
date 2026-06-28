# Praesidium auth centralization flow control

Purpose: centralize username/role authorization checks in web/common/security/auth.php after session.php centralization.

Rules:
- Work file-by-file with mechanical, reviewable changes only.
- Preserve response type: JSON endpoints return JSON; HTML pages keep short No autorizado behavior unless explicitly changed later.
- Do not migrate login/index/logout public/special flows automatically.
- Do not change CSRF behavior in this phase.
- After migration: php -l every touched PHP, static grep for manual username checks, deploy, HTTP smoke, all-safe, e2e.

Backup directory: `/home/ubuntu/praesidium_backups/auth_centralization_20260627_220024`
Backup tar: `/home/ubuntu/praesidium_backups/auth_centralization_20260627_220024.tar.gz`

| # | Status | File | Kind | Target | Notes |
|---:|---|---|---|---|---|
| 1 | MIGRATED | `web/alias/address_alias.php` | html | require_login_page | MANUAL_AUTH |
| 2 | MIGRATED | `web/alias/address_alias_group.php` | html | require_login_page | MANUAL_AUTH |
| 3 | MIGRATED | `web/alias/common_alias_actions/delete_alias.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 4 | MIGRATED | `web/alias/common_alias_actions/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 5 | MIGRATED | `web/alias/common_alias_actions/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 6 | MIGRATED | `web/alias/common_alias_actions/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 7 | MIGRATED | `web/alias/common_alias_actions/update_alias.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 8 | MIGRATED | `web/alias/common_alias_actions/validation_alias.php` | json | require_login_json | MANUAL_AUTH |
| 9 | MIGRATED | `web/alias/service_alias.php` | html | require_login_page | MANUAL_AUTH |
| 10 | MIGRATED | `web/alias/service_alias_group.php` | html | require_login_page | MANUAL_AUTH |
| 11 | MIGRATED | `web/certificates/certificates.php` | html | require_login_page | MANUAL_AUTH |
| 12 | MIGRATED | `web/certificates/certificates_table/get_delete_certificates.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 13 | MIGRATED | `web/certificates/certificates_table/get_download_certificate.php` | json | require_login_json | MANUAL_AUTH |
| 14 | MIGRATED | `web/certificates/certificates_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 15 | MIGRATED | `web/certificates/certificates_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 16 | MIGRATED | `web/certificates/certificates_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 17 | REVIEW | `web/certificates/certificates_table/get_update_certificate.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 18 | MIGRATED | `web/certificates/certificates_table/validation_certificates.php` | json | require_login_json | MANUAL_AUTH |
| 19 | MIGRATED | `web/commits/check_commit/commit_apply/commit_apply.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 20 | REVIEW | `web/commits/check_commit/commit_common_actions/get_praesidium_config.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 21 | MIGRATED | `web/commits/check_commit/commit_common_actions/get_user.php` | json | require_login_json | MANUAL_AUTH |
| 22 | MIGRATED | `web/commits/commit.php` | html | require_login_page | MANUAL_AUTH |
| 23 | REVIEW | `web/common/file/json_store.php` | json | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 24 | REVIEW | `web/common/security/audit.php` | json | keep | SECURITY_HELPER |
| 25 | REVIEW | `web/common/security/auth.php` | json | keep | SECURITY_HELPER |
| 26 | REVIEW | `web/common/security/csrf.php` | json | keep | SECURITY_HELPER |
| 27 | REVIEW | `web/common/security/session.php` | mixed | keep | SECURITY_HELPER |
| 28 | MIGRATED | `web/common_functions/get_system_time.php` | json | require_login_json | MANUAL_AUTH |
| 29 | MIGRATED | `web/common_functions/upload_files.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 30 | REVIEW | `web/common_functions/validation_uploads.php` | mixed | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 31 | MIGRATED | `web/dashboard/cpu_stats.php` | json | require_login_json | MANUAL_AUTH |
| 32 | MIGRATED | `web/dashboard/dashboard.php` | json | require_login_json | MANUAL_AUTH |
| 33 | MIGRATED | `web/dashboard/disk_stats.php` | json | require_login_json | MANUAL_AUTH |
| 34 | MIGRATED | `web/dashboard/net_stats.php` | json | require_login_json | MANUAL_AUTH |
| 35 | MIGRATED | `web/dashboard/ram_stats.php` | json | require_login_json | MANUAL_AUTH |
| 36 | REVIEW | `web/index.php` | html | review_only | PUBLIC_OR_SESSION_SPECIAL |
| 37 | MIGRATED | `web/interfaces/bonds.php` | html | require_login_page | MANUAL_AUTH |
| 38 | MIGRATED | `web/interfaces/bridges.php` | html | require_login_page | MANUAL_AUTH |
| 39 | MIGRATED | `web/interfaces/ethernets.php` | html | require_login_page | MANUAL_AUTH |
| 40 | MIGRATED | `web/interfaces/interfaces_table/get_delete_interface.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 41 | MIGRATED | `web/interfaces/interfaces_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 42 | MIGRATED | `web/interfaces/interfaces_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 43 | MIGRATED | `web/interfaces/interfaces_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 44 | MIGRATED | `web/interfaces/interfaces_table/get_update_interface.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 45 | MIGRATED | `web/interfaces/interfaces_table/validation_interface.php` | json | require_login_json | MANUAL_AUTH |
| 46 | MIGRATED | `web/interfaces/vlans.php` | html | require_login_page | MANUAL_AUTH |
| 47 | MIGRATED | `web/interfaces/wifis.php` | html | require_login_page | MANUAL_AUTH |
| 48 | REVIEW | `web/interfaces/wireguard/common/wireguard_store.php` | json | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 49 | MIGRATED | `web/interfaces/wireguard/index.php` | html | require_login_page | MANUAL_AUTH |
| 50 | MIGRATED | `web/interfaces/wireguard/remote_access.php` | html | require_login_page | MANUAL_AUTH |
| 51 | REVIEW | `web/interfaces/wireguard/remote_access_table/get_delete.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 52 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 53 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 54 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 55 | REVIEW | `web/interfaces/wireguard/remote_access_table/get_update.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 56 | REVIEW | `web/interfaces/wireguard/remote_clients_table/get_client_config.php` | mixed | keep_or_review | CENTRAL_AUTH_USED |
| 57 | REVIEW | `web/interfaces/wireguard/remote_clients_table/get_client_qr.php` | mixed | keep_or_review | CENTRAL_AUTH_USED |
| 58 | REVIEW | `web/interfaces/wireguard/remote_clients_table/get_delete.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 59 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 60 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 61 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 62 | REVIEW | `web/interfaces/wireguard/remote_clients_table/get_update.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 63 | MIGRATED | `web/interfaces/wireguard/site_to_site.php` | html | require_login_page | MANUAL_AUTH |
| 64 | REVIEW | `web/interfaces/wireguard/site_to_site_table/get_delete.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 65 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 66 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 67 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 68 | REVIEW | `web/interfaces/wireguard/site_to_site_table/get_update.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 69 | REVIEW | `web/lang/en.php` | mixed | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 70 | REVIEW | `web/lang/es.php` | mixed | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 71 | REVIEW | `web/login.php` | mixed | review_only | PUBLIC_OR_SESSION_SPECIAL |
| 72 | REVIEW | `web/logout.php` | mixed | review_only | PUBLIC_OR_SESSION_SPECIAL |
| 73 | MIGRATED | `web/system/system-logs.php` | html | require_login_page | MANUAL_AUTH |
| 74 | MIGRATED | `web/mainpage.php` | html | require_login_page | MANUAL_AUTH |
| 75 | MIGRATED | `web/monitor/get_logs/get_logs.php` | json | require_login_json | MANUAL_AUTH |
| 76 | MIGRATED | `web/monitor/logs_table/get_table_content_monitor.php` | json | require_login_json | MANUAL_AUTH |
| 77 | MIGRATED | `web/monitor/logs_table/get_table_structure_monitor.php` | json | require_login_json | MANUAL_AUTH |
| 78 | MIGRATED | `web/monitor/logs_table/get_table_structure_monitor_log.php` | json | require_login_json | MANUAL_AUTH |
| 79 | MIGRATED | `web/monitor/monitor.php` | html | require_login_page | MANUAL_AUTH |
| 80 | MIGRATED | `web/networking/dhcp_config.php` | html | require_login_page | MANUAL_AUTH |
| 81 | REVIEW | `web/networking/dhcp_table/get_delete_dhcp.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 82 | MIGRATED | `web/networking/dhcp_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 83 | MIGRATED | `web/networking/dhcp_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 84 | MIGRATED | `web/networking/dhcp_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 85 | REVIEW | `web/networking/dhcp_table/get_update_dhcp.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 86 | REVIEW | `web/networking/dhcp_table/validation_dhcp.php` | json | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 87 | MIGRATED | `web/policies/common_policy_actions_bpf/get_delete_policy.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 88 | MIGRATED | `web/policies/common_policy_actions_bpf/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 89 | MIGRATED | `web/policies/common_policy_actions_bpf/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 90 | MIGRATED | `web/policies/common_policy_actions_bpf/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 91 | MIGRATED | `web/policies/common_policy_actions_bpf/get_update_policy.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 92 | MIGRATED | `web/policies/common_policy_actions_bpf/validation_policy.php` | json | require_login_json | MANUAL_AUTH |
| 93 | MIGRATED | `web/policies/common_policy_actions_nft/get_delete_policy.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 94 | MIGRATED | `web/policies/common_policy_actions_nft/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 95 | MIGRATED | `web/policies/common_policy_actions_nft/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 96 | MIGRATED | `web/policies/common_policy_actions_nft/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 97 | MIGRATED | `web/policies/common_policy_actions_nft/get_update_policy.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 98 | MIGRATED | `web/policies/common_policy_actions_nft/validation_policy.php` | json | require_login_json | MANUAL_AUTH |
| 99 | MIGRATED | `web/policies/policies_TC_egress.php` | html | require_login_page | MANUAL_AUTH |
| 100 | MIGRATED | `web/policies/policies_TC_ingress.php` | html | require_login_page | MANUAL_AUTH |
| 101 | MIGRATED | `web/policies/policies_nftables_forwarding.php` | html | require_login_page | MANUAL_AUTH |
| 102 | MIGRATED | `web/policies/policies_nftables_input.php` | html | require_login_page | MANUAL_AUTH |
| 103 | MIGRATED | `web/policies/policies_nftables_output.php` | html | require_login_page | MANUAL_AUTH |
| 104 | MIGRATED | `web/policies/policies_nftables_postrouting.php` | html | require_login_page | MANUAL_AUTH |
| 105 | MIGRATED | `web/policies/policies_nftables_prerouting.php` | html | require_login_page | MANUAL_AUTH |
| 106 | MIGRATED | `web/policies/policies_xdp.php` | html | require_login_page | MANUAL_AUTH |
| 107 | MIGRATED | `web/routing/routing.php` | html | require_login_page | MANUAL_AUTH |
| 108 | MIGRATED | `web/routing/update_routing/get_routes.php` | json | require_login_json | MANUAL_AUTH |
| 109 | MIGRATED | `web/routing/update_routing/reload_system_routes_running.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 110 | MIGRATED | `web/system/services/services.php` | html | require_login_page | MANUAL_AUTH |
| 111 | REVIEW | `web/system/services/services_table/get_delete.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 112 | MIGRATED | `web/system/services/services_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 113 | MIGRATED | `web/system/services/services_table/get_runtime_status.php` | json | require_login_json | MANUAL_AUTH |
| 114 | MIGRATED | `web/system/services/services_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 115 | MIGRATED | `web/system/services/services_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 116 | REVIEW | `web/system/services/services_table/get_update.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 117 | REVIEW | `web/system/services/services_table/services_common.php` | mixed | review_only | NO_SESSION_HELPER_OR_PUBLIC |
| 119 | REVIEW | `web/system/logging/system_logging_table/get_delete_system_logging.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 120 | MIGRATED | `web/system/logging/system_logging_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 121 | MIGRATED | `web/system/logging/system_logging_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 122 | MIGRATED | `web/system/logging/system_logging_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 123 | REVIEW | `web/system/logging/system_logging_table/get_update_system_logging.php` | json | keep_or_review | CENTRAL_AUTH_USED |
| 139 | MIGRATED | `web/users/users.php` | html | require_login_page | MANUAL_AUTH |
| 140 | MIGRATED | `web/users/users_table/get_delete_user.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 141 | MIGRATED | `web/users/users_table/get_forms_from_table.php` | json | require_login_json | MANUAL_AUTH |
| 142 | MIGRATED | `web/users/users_table/get_table_content.php` | json | require_login_json | MANUAL_AUTH |
| 143 | MIGRATED | `web/users/users_table/get_table_structure.php` | json | require_login_json | MANUAL_AUTH |
| 144 | MIGRATED | `web/users/users_table/get_update_user.php` | json | remove_manual_redundant | CENTRAL_PLUS_MANUAL |
| 145 | MIGRATED | `web/users/users_table/validation_user.php` | json | require_login_json | MANUAL_AUTH |

## Migration batch auth centralization

- Files changed: 115
- auth.php extended with page/text/download helpers.



- Real module page: `web/system/logging/system_logging.php`.
