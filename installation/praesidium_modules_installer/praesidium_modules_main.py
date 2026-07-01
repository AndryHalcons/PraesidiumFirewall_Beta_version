#!/usr/bin/env python3
"""
ES:
    Orquestador principal de instaladores modulares de Praesidium.

    Este script tiene un único trabajo: ejecutar, en orden, los otros scripts
    Python de la misma carpeta de instaladores modulares.

    Antes de ejecutar los instaladores hijos prepara los directorios runtime
    permitidos para que una reinstalación no deje restos antiguos.

    La lógica de copia/generación real vive en:
        - praesidium_modules_installer.py
        - praesidium_modules_lang_installer.py
        - praesidium_modules_web_installer.py

EN:
    Main orchestrator for Praesidium modular installers.

    This script has one single job: execute, in order, the other Python scripts
    from the same modular-installer folder.

    Before running child installers it prepares the allowed runtime directories
    so a reinstall cannot leave stale files behind.

    The real copy/generation logic lives in:
        - praesidium_modules_installer.py
        - praesidium_modules_lang_installer.py
        - praesidium_modules_web_installer.py
"""

from __future__ import annotations

import argparse
import shutil
import subprocess
import sys
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class InstallerStep:
    """
    ES:
        Define un script hijo que este orquestador debe ejecutar.
    EN:
        Define a child script that this orchestrator must execute.
    """

    label: str
    script_name: str


# ES:
#     Orden explícito de ejecución. Mantenerlo aquí evita descubrir scripts por
#     glob y ejecutar accidentalmente herramientas auxiliares o pruebas.
# EN:
#     Explicit execution order. Keeping it here avoids glob-discovering scripts
#     and accidentally running helper tools or tests.
INSTALLER_STEPS: tuple[InstallerStep, ...] = (
    InstallerStep(
        label="modules",
        script_name="praesidium_modules_installer.py",
    ),
    InstallerStep(
        label="language",
        script_name="praesidium_modules_lang_installer.py",
    ),
    InstallerStep(
        label="web",
        script_name="praesidium_modules_web_installer.py",
    ),
)


class MainInstallerError(RuntimeError):
    """
    ES: Error controlado del orquestador principal.
    EN: Controlled main-orchestrator error.
    """


# ES:
#     La carpeta de instaladores es siempre la carpeta donde vive este archivo.
#     No se usa ninguna ruta absoluta ni se asume la raíz del repositorio aquí.
# EN:
#     The installer folder is always the folder where this file lives.
#     No absolute path is used and the repository root is not assumed here.
def get_installer_dir() -> Path:
    return Path(__file__).resolve().parent


# ES:
#     Verifica que todos los scripts hijos existen antes de ejecutar nada.
#     Así evitamos instalaciones parciales por un archivo mal movido o renombrado.
# EN:
#     Verify all child scripts exist before executing anything.
#     This avoids partial installs caused by a moved or renamed file.
def validate_steps(installer_dir: Path, steps: tuple[InstallerStep, ...]) -> None:
    missing: list[str] = []
    for step in steps:
        script_path = installer_dir / step.script_name
        if not script_path.is_file():
            missing.append(step.script_name)

    if missing:
        joined = ", ".join(missing)
        raise MainInstallerError(f"missing installer script(s): {joined}")


# ES: Borra y recrea los directorios runtime controlados antes de reinstalar.
# EN: Delete and recreate controlled runtime directories before reinstalling.
def prepare_runtime_directories(dry_run: bool) -> None:
    # ES: Directorios runtime que deben quedar limpios antes de reinstalar.
    # EN: Runtime directories that must be clean before reinstalling.
    runtime_dirs = (
        Path("/var/www/html"),
        Path("/var/www/backend"),
        Path("/var/www/config"),
        Path("/var/www/config_running"),
        Path("/var/www/test"),
    )

    print("[PREPARE] runtime directories", flush=True)
    for runtime_dir in runtime_dirs:
        # ES: Protección simple: sólo permitimos borrar hijos directos de /var/www.
        # EN: Simple guard: only direct children of /var/www may be deleted.
        if runtime_dir.parent != Path("/var/www"):
            raise MainInstallerError(f"refusing unsafe runtime directory: {runtime_dir}")

        if dry_run:
            print(f"  DRY-RUN rm -rf {runtime_dir} && mkdir -p {runtime_dir}", flush=True)
            continue

        # ES: Equivalente a rm -rf <dir> && mkdir -p <dir>.
        # EN: Equivalent to rm -rf <dir> && mkdir -p <dir>.
        shutil.rmtree(runtime_dir, ignore_errors=True)
        runtime_dir.mkdir(parents=True, exist_ok=True)
        print(f"  recreated {runtime_dir}", flush=True)


# ES:
#     Construye el comando de cada script hijo.
#     --dry-run se propaga a todos porque los tres instaladores lo soportan.
#     --continue-on-error sólo se pasa al instalador de módulos, que es el que
#     declara esa opción para seguir con otros módulos si uno falla.
# EN:
#     Build each child-script command.
#     --dry-run is propagated to all scripts because all three support it.
#     --continue-on-error is passed only to the module installer, which declares
#     that option to continue with other modules if one fails.
def build_child_command(
    script_path: Path,
    step: InstallerStep,
    *,
    dry_run: bool,
    continue_on_error: bool,
) -> list[str]:
    command = [sys.executable, str(script_path)]

    if dry_run:
        command.append("--dry-run")

    if continue_on_error and step.script_name == "praesidium_modules_installer.py":
        command.append("--continue-on-error")

    return command


# ES:
#     Ejecuta un paso hijo y devuelve su código de salida.
#     Se usa subprocess.run sin shell para evitar problemas de quoting.
# EN:
#     Execute one child step and return its exit code.
#     subprocess.run is used without shell to avoid quoting issues.
def run_step(
    installer_dir: Path,
    step: InstallerStep,
    *,
    dry_run: bool,
    continue_on_error: bool,
) -> int:
    script_path = installer_dir / step.script_name
    command = build_child_command(
        script_path,
        step,
        dry_run=dry_run,
        continue_on_error=continue_on_error,
    )

    print(f"[STEP] {step.label}: {step.script_name}", flush=True)
    print("[CMD] " + " ".join(command), flush=True)

    completed = subprocess.run(command, cwd=str(installer_dir.parent.parent))
    print(f"[STEP-END] {step.label}: exit_code={completed.returncode}", flush=True)
    return completed.returncode


# ES:
#     Punto de entrada CLI del orquestador.
# EN:
#     CLI entry point for the orchestrator.
def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Run Praesidium modular installer scripts in order.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Pass --dry-run to child installers so no real install/output write is performed.",
    )
    parser.add_argument(
        "--continue-on-error",
        action="store_true",
        help="Continue running later installers if a child step fails; also passed to the module installer.",
    )
    parser.add_argument(
        "--list",
        action="store_true",
        help="List the child scripts that would be executed, then exit.",
    )
    args = parser.parse_args(argv)

    installer_dir = get_installer_dir()

    try:
        validate_steps(installer_dir, INSTALLER_STEPS)
    except MainInstallerError as exc:
        print(f"[ERROR] {exc}", file=sys.stderr)
        return 1

    print(f"[INFO] installer_dir={installer_dir}", flush=True)

    if args.list:
        for index, step in enumerate(INSTALLER_STEPS, start=1):
            print(f"{index}. {step.script_name}", flush=True)
        return 0

    try:
        prepare_runtime_directories(dry_run=args.dry_run)
    except MainInstallerError as exc:
        print(f"[ERROR] {exc}", file=sys.stderr)
        return 1

    failures: list[tuple[str, int]] = []
    for step in INSTALLER_STEPS:
        exit_code = run_step(
            installer_dir,
            step,
            dry_run=args.dry_run,
            continue_on_error=args.continue_on_error,
        )
        if exit_code != 0:
            failures.append((step.script_name, exit_code))
            if not args.continue_on_error:
                print(f"[ERROR] stopping after failed step: {step.script_name}", file=sys.stderr)
                return exit_code

    if failures:
        print("[ERROR] one or more installer steps failed:", file=sys.stderr)
        for script_name, exit_code in failures:
            print(f"  - {script_name}: exit_code={exit_code}", file=sys.stderr)
        return 1

    print("[OK] all modular installer steps completed", flush=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
