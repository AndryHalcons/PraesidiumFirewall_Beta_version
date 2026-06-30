#!/usr/bin/env python3
"""
Praesidium module installer.

ES:
    Instalador genérico para la estructura modular de Praesidium.

    Este script no usa una ruta absoluta fija hacia los módulos. La carpeta de
    módulos se resuelve siempre desde la raíz del repositorio:

        modern_format/modules

    Cada módulo debe declarar su instalación en:

        modern_format/modules/<modulo>/install/route_install.json

    Dentro de ese JSON, cada entrada de "install_route" significa:

        source      -> dónde están ahora los archivos dentro del repo modular
        destination -> dónde deben copiarse en el runtime de Praesidium

EN:
    Generic installer for Praesidium's modular layout.

    This script does not hardcode an absolute path to the modules directory. The
    modules directory is always resolved from the repository root:

        modern_format/modules

    Each module must declare its installation at:

        modern_format/modules/<module>/install/route_install.json

    Inside that JSON, each "install_route" entry means:

        source      -> where files currently live inside the modular repository
        destination -> where files must be copied in the Praesidium runtime

Principio de diseño / Design principle:
    El módulo declara; el instalador genérico ejecuta.
    The module declares; the generic installer executes.
"""

from __future__ import annotations

import argparse
import glob
import json
import shutil
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, List, Sequence


# ---------------------------------------------------------------------------
# Constantes / Constants
# ---------------------------------------------------------------------------

# ES: Ruta relativa a la raíz del repo. Importante: NO es "/modern_format/modules".
# EN: Path relative to the repository root. Important: it is NOT "/modern_format/modules".
MODULES_RELATIVE_PATH = Path("modern_format") / "modules"

# ES: Ubicación estándar del manifiesto dentro de cada módulo.
# EN: Standard manifest location inside every module.
MANIFEST_RELATIVE_PATH = Path("install") / "route_install.json"


@dataclass(frozen=True)
class InstallRoute:
    """
    ES: Una entrada declarativa source -> destination del route_install.json.
    EN: One declarative source -> destination entry from route_install.json.
    """

    source: str
    destination: str


@dataclass(frozen=True)
class ModuleManifest:
    """
    ES: Manifiesto validado de un módulo.
    EN: Validated manifest for one module.
    """

    module_name: str
    module_dir: Path
    manifest_path: Path
    install_routes: Sequence[InstallRoute]


# ---------------------------------------------------------------------------
# Descubrimiento de repositorio y módulos / Repository and module discovery
# ---------------------------------------------------------------------------

def infer_repo_root(script_path: Path) -> Path:
    """
    ES:
        Infiera la raíz del repositorio desde la ubicación del script.

        Este archivo está pensado para vivir en:

            <repo>/installation/praesidium_modules_installer.py

        Por tanto, la raíz del repo es el padre de la carpeta "installation".
        Así evitamos hardcodear rutas como /home/andres/... o /modern_format/...

    EN:
        Infer the repository root from this script location.

        This file is expected to live at:

            <repo>/installation/praesidium_modules_installer.py

        Therefore, the repo root is the parent of the "installation" directory.
        This avoids hardcoded paths such as /home/andres/... or /modern_format/...
    """

    return script_path.resolve().parent.parent


def get_modules_dir(repo_root: Path) -> Path:
    """
    ES: Construye la ruta modern_format/modules relativa al repo.
    EN: Build the modern_format/modules path relative to the repo.
    """

    return repo_root / MODULES_RELATIVE_PATH


def discover_module_dirs(modules_dir: Path) -> List[Path]:
    """
    ES:
        Lista todos los directorios directos dentro de modern_format/modules.

        Sólo se consideran carpetas de primer nivel. Si hubiera archivos sueltos,
        se ignoran porque un módulo debe ser una carpeta autocontenida.

    EN:
        List all direct directories inside modern_format/modules.

        Only first-level directories are considered. Loose files are ignored
        because a module must be a self-contained directory.
    """

    if not modules_dir.is_dir():
        raise FileNotFoundError(f"Modules directory not found: {modules_dir}")

    return sorted(path for path in modules_dir.iterdir() if path.is_dir())


# ---------------------------------------------------------------------------
# Lectura y validación de manifiestos / Manifest loading and validation
# ---------------------------------------------------------------------------

def load_json_file(path: Path) -> object:
    """
    ES: Carga un JSON y genera errores claros si está roto.
    EN: Load a JSON file and produce clear errors if it is broken.
    """

    try:
        with path.open("r", encoding="utf-8") as handle:
            return json.load(handle)
    except json.JSONDecodeError as exc:
        raise ValueError(
            f"Invalid JSON in {path}: line {exc.lineno}, column {exc.colno}: {exc.msg}"
        ) from exc


def parse_manifest(module_dir: Path) -> ModuleManifest | None:
    """
    ES:
        Busca y valida el manifiesto:

            <modulo>/install/route_install.json

        Si el módulo no tiene manifiesto, devuelve None y el instalador podrá
        saltarlo. No inventamos rutas ni aplicamos defaults ocultos.

        Validaciones mínimas:
            - la raíz del JSON debe ser un objeto
            - debe existir la clave "install_route"
            - "install_route" debe ser una lista
            - cada entrada debe tener "source" y "destination" como texto

        Esta función NO corrige rutas. Si source/destination están mal, deben
        revisarse en el manifiesto.

    EN:
        Find and validate the manifest:

            <module>/install/route_install.json

        If the module has no manifest, return None and the installer can skip it.
        We do not invent routes or apply hidden defaults.

        Minimum validations:
            - JSON root must be an object
            - the "install_route" key must exist
            - "install_route" must be a list
            - every entry must have "source" and "destination" as text

        This function does NOT fix paths. If source/destination are wrong, they
        must be reviewed in the manifest.
    """

    manifest_path = module_dir / MANIFEST_RELATIVE_PATH
    if not manifest_path.exists():
        return None

    raw = load_json_file(manifest_path)
    if not isinstance(raw, dict):
        raise ValueError(f"{manifest_path}: JSON root must be an object")

    install_route = raw.get("install_route")
    if not isinstance(install_route, list):
        raise ValueError(f"{manifest_path}: 'install_route' must be a list")

    routes: List[InstallRoute] = []
    for index, item in enumerate(install_route):
        if not isinstance(item, dict):
            raise ValueError(f"{manifest_path}: install_route[{index}] must be an object")

        source = item.get("source")
        destination = item.get("destination")

        if not isinstance(source, str) or not source.strip():
            raise ValueError(
                f"{manifest_path}: install_route[{index}].source must be a non-empty string"
            )
        if not isinstance(destination, str) or not destination.strip():
            raise ValueError(
                f"{manifest_path}: install_route[{index}].destination must be a non-empty string"
            )

        routes.append(InstallRoute(source=source.strip(), destination=destination.strip()))

    return ModuleManifest(
        module_name=module_dir.name,
        module_dir=module_dir,
        manifest_path=manifest_path,
        install_routes=routes,
    )


# ---------------------------------------------------------------------------
# Seguridad básica de rutas / Basic path safety
# ---------------------------------------------------------------------------

def ensure_source_is_relative(source: str, manifest_path: Path) -> None:
    """
    ES:
        El source debe ser relativo al repo.

        Se bloquean sources absolutos como:

            /modern_format/modules/...

        porque eso apunta a la raíz del sistema, no a la raíz del repositorio.

    EN:
        The source must be relative to the repo.

        Absolute sources such as:

            /modern_format/modules/...

        are blocked because they point to the filesystem root, not the repo root.
    """

    if Path(source).is_absolute():
        raise ValueError(f"{manifest_path}: source must be relative, got: {source}")


def ensure_destination_is_not_dangerous(destination: str, manifest_path: Path) -> None:
    """
    ES:
        Validación mínima de destino.

        En esta primera versión no imponemos aún una whitelist completa de
        destinos, porque Andrés quiere revisar rutas con cuidado. Sí bloqueamos
        un destino obviamente peligroso: '/'.

    EN:
        Minimum destination validation.

        In this first version we do not yet enforce a full destination whitelist,
        because Andrés wants to review routes carefully. We do block one clearly
        dangerous destination: '/'.
    """

    if str(Path(destination)) == "/":
        raise ValueError(f"{manifest_path}: refusing dangerous destination '/'" )


def resolve_source_matches(repo_root: Path, source_pattern: str, manifest_path: Path) -> List[Path]:
    """
    ES:
        Resuelve el patrón source contra la raíz del repo.

        Ejemplo:
            source = modern_format/modules/alias/web/*

        Se resuelve como:
            <repo>/modern_format/modules/alias/web/*

        Si el patrón no encuentra archivos, NO se considera error. En Praesidium
        puede haber carpetas vacías por protocolo, por contrato de módulo o
        porque se espera añadir archivos en el futuro. En ese caso se devuelve
        una lista vacía y la ruta se omite con un aviso.

    EN:
        Resolve the source pattern against the repo root.

        Example:
            source = modern_format/modules/alias/web/*

        Resolves as:
            <repo>/modern_format/modules/alias/web/*

        If the pattern matches no files, it is NOT an error. Praesidium may keep
        empty directories by protocol, by module contract, or because files are
        expected to be added in the future. In that case an empty list is returned
        and the route is skipped with a warning.
    """

    ensure_source_is_relative(source_pattern, manifest_path)

    absolute_pattern = repo_root / source_pattern
    return sorted(Path(path) for path in glob.glob(str(absolute_pattern)))


# ---------------------------------------------------------------------------
# Copia de archivos / File copy operations
# ---------------------------------------------------------------------------

def copy_one_path(source: Path, destination_dir: Path, dry_run: bool) -> None:
    """
    ES:
        Copia un archivo o directorio dentro del directorio destino.

        - Si source es archivo:
              destination/source.name se copia con shutil.copy2.

        - Si source es directorio:
              destination/source.name se copia recursivamente con copytree.

        El directorio destino se crea si no existe.

        No se borra el destino completo antes de copiar. Esto reduce el riesgo
        de borrar datos ajenos en esta primera versión.

    EN:
        Copy one file or directory into the destination directory.

        - If source is a file:
              destination/source.name is copied with shutil.copy2.

        - If source is a directory:
              destination/source.name is recursively copied with copytree.

        The destination directory is created if missing.

        The whole destination is not deleted before copying. This reduces the
        risk of deleting unrelated data in this first version.
    """

    target = destination_dir / source.name

    if dry_run:
        print(f"      DRY-RUN copy {source} -> {target}")
        return

    destination_dir.mkdir(parents=True, exist_ok=True)

    if source.is_dir():
        shutil.copytree(source, target, dirs_exist_ok=True)
    else:
        shutil.copy2(source, target)


def install_route(repo_root: Path, manifest: ModuleManifest, route: InstallRoute, dry_run: bool) -> None:
    """
    ES:
        Ejecuta una entrada del manifiesto.

        El source se interpreta desde la raíz del repo. El destination se trata
        como directorio destino, que es el patrón actual de tus route_install.json.

    EN:
        Execute one manifest entry.

        The source is interpreted from the repo root. The destination is treated
        as a destination directory, which is the current route_install.json pattern.
    """

    ensure_destination_is_not_dangerous(route.destination, manifest.manifest_path)

    destination_dir = Path(route.destination)
    sources = resolve_source_matches(repo_root, route.source, manifest.manifest_path)

    print(f"    route: {route.source} -> {route.destination}")
    if not sources:
        print("      SKIP empty source: no files matched; continuing")
        return

    for source in sources:
        copy_one_path(source=source, destination_dir=destination_dir, dry_run=dry_run)


def install_module(repo_root: Path, manifest: ModuleManifest, dry_run: bool) -> None:
    """
    ES:
        Instala un módulo usando sólo su route_install.json.

        Esta función no sabe nada especial sobre alias, networking, policies,
        interfaces, etc. Esa información vive en el manifiesto del módulo.

    EN:
        Install a module using only its route_install.json.

        This function knows nothing special about alias, networking, policies,
        interfaces, etc. That information lives in the module manifest.
    """

    print(f"[MODULE] {manifest.module_name}")
    print(f"  manifest: {manifest.manifest_path.relative_to(repo_root)}")

    if not manifest.install_routes:
        print("  no install routes declared; nothing to copy")
        return

    for route in manifest.install_routes:
        install_route(repo_root=repo_root, manifest=manifest, route=route, dry_run=dry_run)


# ---------------------------------------------------------------------------
# Selección de módulos / Module selection
# ---------------------------------------------------------------------------

def iter_selected_modules(module_dirs: Iterable[Path], selected_names: Sequence[str] | None) -> Iterable[Path]:
    """
    ES:
        Si no se pasan módulos, procesa todos.
        Si se pasan nombres, procesa sólo esos y falla si alguno no existe.

    EN:
        If no modules are passed, process all of them.
        If names are passed, process only those and fail if any does not exist.
    """

    module_dirs = list(module_dirs)
    if not selected_names:
        yield from module_dirs
        return

    wanted = set(selected_names)
    available = {path.name for path in module_dirs}
    missing = sorted(wanted - available)
    if missing:
        raise ValueError(f"Requested module(s) not found: {', '.join(missing)}")

    for module_dir in module_dirs:
        if module_dir.name in wanted:
            yield module_dir


# ---------------------------------------------------------------------------
# CLI / Command line interface
# ---------------------------------------------------------------------------

def build_arg_parser() -> argparse.ArgumentParser:
    """
    ES:
        Define la interfaz CLI.

        --dry-run permite ver qué copiaría sin escribir en destino.
        --list permite listar módulos y manifiestos sin instalar.

    EN:
        Define the CLI interface.

        --dry-run shows what would be copied without writing destinations.
        --list lists modules and manifests without installing.
    """

    parser = argparse.ArgumentParser(
        description="Install Praesidium modules from modern_format/modules manifests."
    )
    parser.add_argument(
        "modules",
        nargs="*",
        help="Optional module names to install. If omitted, all modules are processed.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Print planned copy operations without writing destination files.",
    )
    parser.add_argument(
        "--list",
        action="store_true",
        help="List discovered modules and whether they have install/route_install.json, then exit.",
    )
    parser.add_argument(
        "--continue-on-error",
        action="store_true",
        help="Continue with the next module if one module fails.",
    )
    return parser


def list_modules(repo_root: Path, module_dirs: Sequence[Path]) -> None:
    """
    ES: Lista módulos detectados y si tienen manifiesto.
    EN: List discovered modules and whether they have a manifest.
    """

    print(f"Repository root: {repo_root}")
    print(f"Modules dir:     {repo_root / MODULES_RELATIVE_PATH}")
    for module_dir in module_dirs:
        manifest_path = module_dir / MANIFEST_RELATIVE_PATH
        status = "manifest=yes" if manifest_path.is_file() else "manifest=no"
        print(f"{module_dir.name:20} {status}")


def main(argv: Sequence[str] | None = None) -> int:
    """
    ES:
        Flujo principal:
            1. inferir raíz del repo
            2. entrar conceptualmente en modern_format/modules mediante ruta relativa
            3. listar directorios de módulos
            4. buscar install/route_install.json en cada módulo
            5. copiar cada source -> destination

    EN:
        Main flow:
            1. infer repo root
            2. conceptually enter modern_format/modules through a relative path
            3. list module directories
            4. find install/route_install.json in every module
            5. copy every source -> destination
    """

    args = build_arg_parser().parse_args(argv)

    repo_root = infer_repo_root(Path(__file__))
    modules_dir = get_modules_dir(repo_root)
    module_dirs = discover_module_dirs(modules_dir)

    if args.list:
        list_modules(repo_root, module_dirs)
        return 0

    selected_dirs = list(iter_selected_modules(module_dirs, args.modules))

    errors: List[str] = []
    for module_dir in selected_dirs:
        try:
            manifest = parse_manifest(module_dir)
            if manifest is None:
                print(f"[SKIP] {module_dir.name}: missing {MANIFEST_RELATIVE_PATH}")
                continue
            install_module(repo_root=repo_root, manifest=manifest, dry_run=args.dry_run)
        except Exception as exc:  # installer: report module context clearly
            message = f"[ERROR] {module_dir.name}: {exc}"
            print(message, file=sys.stderr)
            errors.append(message)
            if not args.continue_on_error:
                return 1

    if errors:
        print("\nCompleted with errors:", file=sys.stderr)
        for error in errors:
            print(f"  {error}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
