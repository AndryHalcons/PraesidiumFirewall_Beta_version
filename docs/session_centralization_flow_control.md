# Praesidium session centralization flow control

Purpose: track every PHP file that currently calls session_start() directly before replacing it with web/common/security/session.php.

Rules:
- Do not edit files outside this checklist for this phase.
- Edit in small batches by module/directory, not all at once.
- Before each batch: make backup/checksum.
- After each batch: php -l touched files + grep for direct session_start().
- Deploy only after syntax passes.
- Browser/HTTP verify login, CSRF token, representative pages and cookie flags after deploy.
- Keep web/common/security/session.php as the only allowed direct session_start() owner.

Total files to migrate: 136

| # | Status | File | Notes |
|---:|---|---|---|
| 1 | MIGRATED | `web/alias/address_alias.php` | uses web/common/security/session.php |
| 2 | MIGRATED | `web/alias/address_alias_group.php` | uses web/common/security/session.php |
| 3 | MIGRATED | `web/alias/common_alias_actions/delete_alias.php` | uses web/common/security/session.php |
| 4 | MIGRATED | `web/alias/common_alias_actions/get_forms_from_table.php` | uses web/common/security/session.php |
| 5 | MIGRATED | `web/alias/common_alias_actions/get_table_content.php` | uses web/common/security/session.php |
| 6 | MIGRATED | `web/alias/common_alias_actions/get_table_structure.php` | uses web/common/security/session.php |
| 7 | MIGRATED | `web/alias/common_alias_actions/update_alias.php` | uses web/common/security/session.php |
| 8 | MIGRATED | `web/alias/common_alias_actions/validation_alias.php` | uses web/common/security/session.php |
| 9 | MIGRATED | `web/alias/service_alias.php` | uses web/common/security/session.php |
| 10 | MIGRATED | `web/alias/service_alias_group.php` | uses web/common/security/session.php |
| 11 | MIGRATED | `web/certificates/certificates.php` | uses web/common/security/session.php |
| 12 | MIGRATED | `web/certificates/certificates_table/get_delete_certificates.php` | uses web/common/security/session.php |
| 13 | MIGRATED | `web/certificates/certificates_table/get_download_certificate.php` | uses web/common/security/session.php |
| 14 | MIGRATED | `web/certificates/certificates_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 15 | MIGRATED | `web/certificates/certificates_table/get_table_content.php` | uses web/common/security/session.php |
| 16 | MIGRATED | `web/certificates/certificates_table/get_table_structure.php` | uses web/common/security/session.php |
| 17 | MIGRATED | `web/certificates/certificates_table/get_update_certificate.php` | uses web/common/security/session.php |
| 18 | MIGRATED | `web/certificates/certificates_table/validation_certificates.php` | uses web/common/security/session.php |
| 19 | MIGRATED | `web/commits/check_commit/commit_apply/commit_apply.php` | uses web/common/security/session.php |
| 20 | MIGRATED | `web/commits/check_commit/commit_common_actions/get_praesidium_config.php` | uses web/common/security/session.php |
| 21 | MIGRATED | `web/commits/check_commit/commit_common_actions/get_user.php` | uses web/common/security/session.php |
| 22 | MIGRATED | `web/commits/commit.php` | uses web/common/security/session.php |
| 23 | MIGRATED | `web/common/security/auth.php` | uses web/common/security/session.php |
| 24 | MIGRATED | `web/common/security/csrf.php` | uses web/common/security/session.php |
| 25 | MIGRATED | `web/common_functions/get_system_time.php` | uses web/common/security/session.php |
| 26 | MIGRATED | `web/common_functions/upload_files.php` | uses web/common/security/session.php |
| 27 | MIGRATED | `web/dashboard/cpu_stats.php` | uses web/common/security/session.php |
| 28 | MIGRATED | `web/dashboard/dashboard.php` | uses web/common/security/session.php |
| 29 | MIGRATED | `web/dashboard/disk_stats.php` | uses web/common/security/session.php |
| 30 | MIGRATED | `web/dashboard/net_stats.php` | uses web/common/security/session.php |
| 31 | MIGRATED | `web/dashboard/ram_stats.php` | uses web/common/security/session.php |
| 32 | MIGRATED | `web/index.php` | uses web/common/security/session.php |
| 33 | MIGRATED | `web/interfaces/bonds.php` | uses web/common/security/session.php |
| 34 | MIGRATED | `web/interfaces/bridges.php` | uses web/common/security/session.php |
| 35 | MIGRATED | `web/interfaces/ethernets.php` | uses web/common/security/session.php |
| 36 | MIGRATED | `web/interfaces/interfaces_table/get_delete_interface.php` | uses web/common/security/session.php |
| 37 | MIGRATED | `web/interfaces/interfaces_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 38 | MIGRATED | `web/interfaces/interfaces_table/get_table_content.php` | uses web/common/security/session.php |
| 39 | MIGRATED | `web/interfaces/interfaces_table/get_table_structure.php` | uses web/common/security/session.php |
| 40 | MIGRATED | `web/interfaces/interfaces_table/get_update_interface.php` | uses web/common/security/session.php |
| 41 | MIGRATED | `web/interfaces/interfaces_table/validation_interface.php` | uses web/common/security/session.php |
| 42 | MIGRATED | `web/interfaces/vlans.php` | uses web/common/security/session.php |
| 43 | MIGRATED | `web/interfaces/wifis.php` | uses web/common/security/session.php |
| 44 | MIGRATED | `web/interfaces/wireguard/index.php` | uses web/common/security/session.php |
| 45 | MIGRATED | `web/interfaces/wireguard/remote_access.php` | uses web/common/security/session.php |
| 46 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_delete.php` | uses web/common/security/session.php |
| 47 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 48 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_table_content.php` | uses web/common/security/session.php |
| 49 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_table_structure.php` | uses web/common/security/session.php |
| 50 | MIGRATED | `web/interfaces/wireguard/remote_access_table/get_update.php` | uses web/common/security/session.php |
| 51 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_client_config.php` | uses web/common/security/session.php |
| 52 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_client_qr.php` | uses web/common/security/session.php |
| 53 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_delete.php` | uses web/common/security/session.php |
| 54 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 55 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_table_content.php` | uses web/common/security/session.php |
| 56 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_table_structure.php` | uses web/common/security/session.php |
| 57 | MIGRATED | `web/interfaces/wireguard/remote_clients_table/get_update.php` | uses web/common/security/session.php |
| 58 | MIGRATED | `web/interfaces/wireguard/site_to_site.php` | uses web/common/security/session.php |
| 59 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_delete.php` | uses web/common/security/session.php |
| 60 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 61 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_table_content.php` | uses web/common/security/session.php |
| 62 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_table_structure.php` | uses web/common/security/session.php |
| 63 | MIGRATED | `web/interfaces/wireguard/site_to_site_table/get_update.php` | uses web/common/security/session.php |
| 64 | MIGRATED | `web/login.php` | uses web/common/security/session.php |
| 65 | MIGRATED | `web/logout.php` | uses web/common/security/session.php |
| 66 | MIGRATED | `web/logs.php` | uses web/common/security/session.php |
| 67 | MIGRATED | `web/mainpage.php` | uses web/common/security/session.php |
| 68 | MIGRATED | `web/monitor/get_logs/get_logs.php` | uses web/common/security/session.php |
| 69 | MIGRATED | `web/monitor/logs_table/get_table_content_monitor.php` | uses web/common/security/session.php |
| 70 | MIGRATED | `web/monitor/logs_table/get_table_structure_monitor.php` | uses web/common/security/session.php |
| 71 | MIGRATED | `web/monitor/logs_table/get_table_structure_monitor_log.php` | uses web/common/security/session.php |
| 72 | MIGRATED | `web/monitor/monitor.php` | uses web/common/security/session.php |
| 73 | MIGRATED | `web/networking/dhcp_config.php` | uses web/common/security/session.php |
| 74 | MIGRATED | `web/networking/dhcp_table/get_delete_dhcp.php` | uses web/common/security/session.php |
| 75 | MIGRATED | `web/networking/dhcp_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 76 | MIGRATED | `web/networking/dhcp_table/get_table_content.php` | uses web/common/security/session.php |
| 77 | MIGRATED | `web/networking/dhcp_table/get_table_structure.php` | uses web/common/security/session.php |
| 78 | MIGRATED | `web/networking/dhcp_table/get_update_dhcp.php` | uses web/common/security/session.php |
| 79 | MIGRATED | `web/policies/common_policy_actions_bpf/get_delete_policy.php` | uses web/common/security/session.php |
| 80 | MIGRATED | `web/policies/common_policy_actions_bpf/get_forms_from_table.php` | uses web/common/security/session.php |
| 81 | MIGRATED | `web/policies/common_policy_actions_bpf/get_table_content.php` | uses web/common/security/session.php |
| 82 | MIGRATED | `web/policies/common_policy_actions_bpf/get_table_structure.php` | uses web/common/security/session.php |
| 83 | MIGRATED | `web/policies/common_policy_actions_bpf/get_update_policy.php` | uses web/common/security/session.php |
| 84 | MIGRATED | `web/policies/common_policy_actions_bpf/validation_policy.php` | uses web/common/security/session.php |
| 85 | MIGRATED | `web/policies/common_policy_actions_nft/get_delete_policy.php` | uses web/common/security/session.php |
| 86 | MIGRATED | `web/policies/common_policy_actions_nft/get_forms_from_table.php` | uses web/common/security/session.php |
| 87 | MIGRATED | `web/policies/common_policy_actions_nft/get_table_content.php` | uses web/common/security/session.php |
| 88 | MIGRATED | `web/policies/common_policy_actions_nft/get_table_structure.php` | uses web/common/security/session.php |
| 89 | MIGRATED | `web/policies/common_policy_actions_nft/get_update_policy.php` | uses web/common/security/session.php |
| 90 | MIGRATED | `web/policies/common_policy_actions_nft/validation_policy.php` | uses web/common/security/session.php |
| 91 | MIGRATED | `web/policies/policies_TC_egress.php` | uses web/common/security/session.php |
| 92 | MIGRATED | `web/policies/policies_TC_ingress.php` | uses web/common/security/session.php |
| 93 | MIGRATED | `web/policies/policies_nftables_forwarding.php` | uses web/common/security/session.php |
| 94 | MIGRATED | `web/policies/policies_nftables_input.php` | uses web/common/security/session.php |
| 95 | MIGRATED | `web/policies/policies_nftables_output.php` | uses web/common/security/session.php |
| 96 | MIGRATED | `web/policies/policies_nftables_postrouting.php` | uses web/common/security/session.php |
| 97 | MIGRATED | `web/policies/policies_nftables_prerouting.php` | uses web/common/security/session.php |
| 98 | MIGRATED | `web/policies/policies_xdp.php` | uses web/common/security/session.php |
| 99 | MIGRATED | `web/routing/routing.php` | uses web/common/security/session.php |
| 100 | MIGRATED | `web/routing/update_routing/get_routes.php` | uses web/common/security/session.php |
| 101 | MIGRATED | `web/routing/update_routing/reload_system_routes_running.php` | uses web/common/security/session.php |
| 102 | MIGRATED | `web/system/services/services.php` | uses web/common/security/session.php |
| 103 | MIGRATED | `web/system/services/services_table/get_delete.php` | uses web/common/security/session.php |
| 104 | MIGRATED | `web/system/services/services_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 105 | MIGRATED | `web/system/services/services_table/get_runtime_status.php` | uses web/common/security/session.php |
| 106 | MIGRATED | `web/system/services/services_table/get_table_content.php` | uses web/common/security/session.php |
| 107 | MIGRATED | `web/system/services/services_table/get_table_structure.php` | uses web/common/security/session.php |
| 108 | MIGRATED | `web/system/services/services_table/get_update.php` | uses web/common/security/session.php |
| 110 | MIGRATED | `web/system/logging/system_logging_table/get_delete_system_logging.php` | uses web/common/security/session.php |
| 111 | MIGRATED | `web/system/logging/system_logging_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 112 | MIGRATED | `web/system/logging/system_logging_table/get_table_content.php` | uses web/common/security/session.php |
| 113 | MIGRATED | `web/system/logging/system_logging_table/get_table_structure.php` | uses web/common/security/session.php |
| 114 | MIGRATED | `web/system/logging/system_logging_table/get_update_system_logging.php` | uses web/common/security/session.php |
| 115 | MIGRATED | `web/url_filter/url_filter_table/get_delete_url_filter.php` | uses web/common/security/session.php |
| 116 | MIGRATED | `web/url_filter/url_filter_table/get_file_data.php` | uses web/common/security/session.php |
| 117 | MIGRATED | `web/url_filter/url_filter_table/get_forms_from_table_url_filter.php` | uses web/common/security/session.php |
| 118 | MIGRATED | `web/url_filter/url_filter_table/get_save_file_data.php` | uses web/common/security/session.php |
| 119 | MIGRATED | `web/url_filter/url_filter_table/get_table_content_url_filter.php` | uses web/common/security/session.php |
| 120 | MIGRATED | `web/url_filter/url_filter_table/get_table_structure_url_filter.php` | uses web/common/security/session.php |
| 121 | MIGRATED | `web/url_filter/url_filter_table/get_update_policy_url_filter.php` | uses web/common/security/session.php |
| 122 | MIGRATED | `web/url_filter/url_filter_table/validation_url_filter.php` | uses web/common/security/session.php |
| 123 | MIGRATED | `web/url_filter/url_list.php` | uses web/common/security/session.php |
| 124 | MIGRATED | `web/url_filter/url_listen_ports.php` | uses web/common/security/session.php |
| 125 | MIGRATED | `web/url_filter/url_network_list.php` | uses web/common/security/session.php |
| 126 | MIGRATED | `web/url_filter/url_networks_list_profile.php` | uses web/common/security/session.php |
| 127 | MIGRATED | `web/url_filter/url_policies.php` | uses web/common/security/session.php |
| 128 | MIGRATED | `web/url_filter/url_port_profile.php` | uses web/common/security/session.php |
| 129 | MIGRATED | `web/url_filter/url_profile.php` | uses web/common/security/session.php |
| 130 | MIGRATED | `web/users/users.php` | uses web/common/security/session.php |
| 131 | MIGRATED | `web/users/users_table/get_delete_user.php` | uses web/common/security/session.php |
| 132 | MIGRATED | `web/users/users_table/get_forms_from_table.php` | uses web/common/security/session.php |
| 133 | MIGRATED | `web/users/users_table/get_table_content.php` | uses web/common/security/session.php |
| 134 | MIGRATED | `web/users/users_table/get_table_structure.php` | uses web/common/security/session.php |
| 135 | MIGRATED | `web/users/users_table/get_update_user.php` | uses web/common/security/session.php |
| 136 | MIGRATED | `web/users/users_table/validation_user.php` | uses web/common/security/session.php |

## Migration batch 20260627_213913

- Backup directory: `/home/ubuntu/praesidium_backups/session_centralization_20260627_213913`
- Backup tar: `/home/ubuntu/praesidium_backups/session_centralization_20260627_213913.tar.gz`
- Files migrated in this batch: 136



- Real module page: `web/system/logging/system_logging.php`.
