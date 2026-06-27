#!/usr/bin/env python3
"""
ES: Verifica que la gestión de sesiones esté centralizada y use flags seguros.
EN: Verifies that session handling is centralized and uses secure cookie flags.
"""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[3]
WEB = ROOT / "web"
SESSION = WEB / "common/security/session.php"

errors = []

if not SESSION.exists():
    errors.append("missing web/common/security/session.php")
else:
    text = SESSION.read_text(errors="ignore")
    required = [
        "function praesidium_session_start",
        "session_set_cookie_params",
        "'httponly' => true",
        "'samesite' => 'Lax'",
        "ini_set('session.use_only_cookies', '1')",
        "ini_set('session.use_strict_mode', '1')",
        "session_start();",
    ]
    for marker in required:
        if marker not in text:
            errors.append(f"session.php missing marker: {marker}")

for path in sorted(WEB.rglob("*.php")):
    if not path.is_file() or path == SESSION:
        continue
    text = path.read_text(errors="ignore")
    if re.search(r"(?<!praesidium_)\bsession_start\s*\(\s*\)\s*;", text):
        errors.append(f"direct session_start outside session.php: {path.relative_to(ROOT)}")

if errors:
    print("FAIL: session centralization static contract")
    for error in errors:
        print(f" - {error}")
    sys.exit(1)

print("PASS: session centralization static contract")
print("central_helper=web/common/security/session.php")
