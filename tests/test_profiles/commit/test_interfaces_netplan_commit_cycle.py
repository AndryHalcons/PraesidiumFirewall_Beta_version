#!/usr/bin/env python3
"""
Commit/apply real en laboratorio para interfaces/netplan.

ES: Modifica candidate de forma controlada, ejecuta Commit vía WebGUI/API,
verifica estado del sistema y restaura. NO debe ejecutarse fuera de VM lab.
EN: Mutates candidate in a controlled way, runs Commit through WebGUI/API,
verifies OS state and restores. Must not run outside lab VM.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from report import pass_
from release_lab import CONFIG_DIR, commit_cycle, assert_command_ok, assert_service_active_or_known, load_json, save_json

require_lab_confirmation()


def mutate_candidate():
    path = CONFIG_DIR / 'interfaces.json'
    data = load_json(path)
    data.setdefault('_release_test_marker', 'interfaces_commit_cycle')
    save_json(path, data)


def verify_commit(payload):
    assert_command_ok('netplan generate', ['sudo', '-n', 'netplan', 'generate'], timeout=180)


commit_cycle('interfaces', mutate_candidate, verify_commit)
pass_('interfaces netplan commit cycle', 'commit/apply real verificado y restaurado')
