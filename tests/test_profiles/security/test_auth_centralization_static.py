#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[3]
WEB = ROOT / "web"
AUTH = WEB / "common/security/auth.php"

errors = []
text = AUTH.read_text(errors="ignore") if AUTH.exists() else ""
for marker in [
    "function require_login_json",
    "function require_admin_json",
    "function require_login_page",
    "function require_admin_page",
    "function require_login_text",
    "function require_admin_text",
    "function require_login_download",
    "function require_admin_download",
]:
    if marker not in text:
        errors.append(f"auth.php missing marker: {marker}")

allowed = {
    "web/common/security/auth.php",
    "web/login.php",
    "web/index.php",
    "web/logout.php",
}

def has_manual_username_auth(src: str) -> bool:
    pos = 0
    while True:
        i = src.find("if", pos)
        if i < 0:
            return False
        # token boundary
        before = src[i-1] if i > 0 else " "
        after = src[i+2] if i + 2 < len(src) else " "
        if before.isalnum() or before == "_" or after.isalnum() or after == "_":
            pos = i + 2
            continue
        open_paren = src.find("(", i + 2)
        open_brace = src.find("{", i + 2)
        if open_paren < 0 or open_brace < 0:
            return False
        if open_brace < open_paren:
            pos = i + 2
            continue
        cond = src[open_paren:open_brace]
        if ("isset" in cond or "empty" in cond) and ("$_SESSION['username']" in cond or '$_SESSION["username"]' in cond):
            return True
        pos = i + 2

for path in sorted(WEB.rglob("*.php")):
    if not path.is_file():
        continue
    rel = str(path.relative_to(ROOT))
    if rel in allowed:
        continue
    src = path.read_text(errors="ignore")
    if has_manual_username_auth(src):
        errors.append(f"manual username auth check outside auth.php: {rel}")

if errors:
    print("FAIL: auth centralization static contract")
    for error in errors:
        print(f" - {error}")
    sys.exit(1)

print("PASS: auth centralization static contract")
print("central_helper=web/common/security/auth.php")
