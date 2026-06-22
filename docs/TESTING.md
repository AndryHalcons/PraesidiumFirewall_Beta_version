# Testing

Run non-destructive profiles from the repository root:

```bash
./tests/run_tests.sh safe
./tests/run_tests.sh validation
./tests/run_tests.sh web
./tests/run_tests.sh security
```

Module tests:

```bash
./tests/run_tests.sh module nftables
./tests/run_tests.sh module dnsmasq
./tests/run_tests.sh module services
```

Destructive lab profiles are blocked unless explicitly enabled:

```bash
PRAESIDIUM_ALLOW_DESTRUCTIVE=1 ./tests/run_tests.sh commit
PRAESIDIUM_ALLOW_DESTRUCTIVE=1 ./tests/run_tests.sh e2e
PRAESIDIUM_ALLOW_DESTRUCTIVE=1 ./tests/run_tests.sh installer
```

HTTP tests also need `PRAESIDIUM_TEST_BASE_URL`, `PRAESIDIUM_TEST_ADMIN_USER`, and `PRAESIDIUM_TEST_ADMIN_PASS`.
