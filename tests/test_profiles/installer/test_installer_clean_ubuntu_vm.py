#!/usr/bin/env python3
"""
Installer release test: clean Ubuntu VM install.

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


cmd = f'''set -euo pipefail
rm -rf /tmp/praesidium-release-install
mkdir -p /tmp/praesidium-release-install
cd /tmp/praesidium-release-install
git clone {repo_url!r} repo
cd repo/installation
chmod +x installer.sh
sudo ./installer.sh
curl -fsS http://127.0.0.1/ >/dev/null
systemctl is-active apache2 >/dev/null
'''
res = ssh_command(host, cmd, timeout=3600)
if res.returncode != 0:
    fail('installer clean Ubuntu VM', [res.stdout[-4000:], res.stderr[-4000:]])
pass_('installer clean Ubuntu VM', 'instalacion limpia completada y WebGUI responde')

