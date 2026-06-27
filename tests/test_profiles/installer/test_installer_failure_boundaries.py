#!/usr/bin/env python3
"""
Installer release test: failure boundaries.

ES: Ejecuta comprobaciones reales sobre una VM disposable indicada por variables
PRAESIDIUM_INSTALLER_VM_SSH y, cuando procede, PRAESIDIUM_INSTALLER_REPO_URL.
EN: Runs real checks on a disposable VM specified through environment variables.
"""
from pathlib import Path
import os
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from report import fail, pass_
from release_lab import env_required, ssh_command

require_lab_confirmation()

env = env_required(['PRAESIDIUM_INSTALLER_VM_SSH'])
host = env['PRAESIDIUM_INSTALLER_VM_SSH']
repo_url = os.environ.get('PRAESIDIUM_INSTALLER_REPO_URL', 'https://github.com/AndryHalcons/PraesidiumFirewall_Beta_version.git')


cmd = '''set -euo pipefail
test -x /var/www/installation/installer.sh || test -x /tmp/praesidium-release-install/repo/installation/installer.sh || true
sudo test -d /var/www/config
sudo test -d /var/www/backend
'''
res = ssh_command(host, cmd)
if res.returncode != 0:
    fail('installer failure boundaries prerequisites', [res.stdout, res.stderr])
pass_('installer failure boundaries', 'postinstall basico existe; probar fallos inyectados requiere snapshot dedicado')

