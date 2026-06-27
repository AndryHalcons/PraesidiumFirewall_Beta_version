#!/usr/bin/env python3
"""
Installer release test: post install permissions.

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
sudo test -d /var/www/config
sudo test -d /var/www/backend
sudo -n true
stat -c '%U:%G %a %n' /var/www/config /var/www/backend
sudo grep -R "commit_apply.py" /etc/sudoers /etc/sudoers.d 2>/dev/null
'''
res = ssh_command(host, cmd)
if res.returncode != 0:
    fail('installer post-install permissions', [res.stdout, res.stderr])
pass_('installer post-install permissions', res.stdout[-1200:])

