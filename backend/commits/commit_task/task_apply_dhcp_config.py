import shutil
import subprocess
from pathlib import Path
from task_update_json import task_update_json

GENERATED = Path('/var/www/config_running/dnsmasq/praesidium-dhcp.conf')
ACTIVE = Path('/etc/dnsmasq.d/praesidium-dhcp.conf')


def backup_dnsmasq_config(date):
    backup = Path(f'/var/www/config_running/dnsmasq_rollback_{date}.conf')
    marker = Path(f'/var/www/config_running/dnsmasq_rollback_{date}.missing')
    try:
        if ACTIVE.exists():
            shutil.copy2(ACTIVE, backup)
            task_update_json(date, 'backup_dnsmasq_config', 'success')
            return backup
        marker.write_text('active config did not exist\n', encoding='utf-8')
        task_update_json(date, 'backup_dnsmasq_config', 'missing_active')
        return marker
    except OSError:
        task_update_json(date, 'backup_dnsmasq_config', 'fail')
        return None


def rollback_dnsmasq_config(date, backup_path):
    try:
        if backup_path and Path(backup_path).suffix == '.missing':
            subprocess.run(['sudo', 'rm', '-f', str(ACTIVE)], check=True)
        elif backup_path and Path(backup_path).exists():
            subprocess.run(['sudo', 'cp', str(backup_path), str(ACTIVE)], check=True)
        else:
            task_update_json(date, 'rollback_dnsmasq_config', 'missing_backup')
            return False
        subprocess.run(['sudo', 'systemctl', 'restart', 'dnsmasq'], check=True)
        task_update_json(date, 'rollback_dnsmasq_config', 'success')
        return True
    except subprocess.CalledProcessError:
        task_update_json(date, 'rollback_dnsmasq_config', 'fail')
        return False


def verify_dnsmasq_config(date):
    try:
        subprocess.run(['sudo', 'dnsmasq', '--test', f'--conf-file={GENERATED}'], check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        task_update_json(date, 'verify_dnsmasq_config_apply', 'success')
    except subprocess.CalledProcessError:
        task_update_json(date, 'verify_dnsmasq_config_apply', 'fail')
        raise SystemExit(1)


def apply_dhcp_config(user, date):
    if not GENERATED.exists():
        task_update_json(date, 'apply_dnsmasq_config', 'missing_generated')
        raise SystemExit(1)
    verify_dnsmasq_config(date)
    backup = backup_dnsmasq_config(date)
    try:
        subprocess.run(['sudo', 'cp', str(GENERATED), str(ACTIVE)], check=True)
        subprocess.run(['sudo', 'chown', 'root:root', str(ACTIVE)], check=True)
        subprocess.run(['sudo', 'chmod', '0644', str(ACTIVE)], check=True)
        task_update_json(date, 'apply_dnsmasq_config', 'success')
        subprocess.run(['sudo', 'systemctl', 'restart', 'dnsmasq'], check=True)
        subprocess.run(['systemctl', 'is-active', '--quiet', 'dnsmasq'], check=True)
        task_update_json(date, 'verify_dnsmasq_service', 'success')
    except subprocess.CalledProcessError:
        task_update_json(date, 'apply_dnsmasq_config', 'fail')
        rollback_dnsmasq_config(date, backup)
        raise SystemExit(1)
