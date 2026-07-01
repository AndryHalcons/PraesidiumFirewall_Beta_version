#!/usr/bin/env python3
"""
ES:
    Generador del manifiesto Web modular de Praesidium.

    Este script recorre los módulos declarados en modern_format/modules,
    lee la sección mainpage_design de cada install/route_install.json y genera
    un único fichero modules_web.json en /var/www/html/.

    Objetivo único:
        - generar /var/www/html/modules_web.json

    Este script NO modifica mainpage.php, mainpage_pruebas.php ni ningún PHP.

EN:
    Praesidium modular Web manifest generator.

    This script scans the modules declared under modern_format/modules,
    reads the mainpage_design section from each install/route_install.json and
    generates a single modules_web.json file under /var/www/html/.

    Single purpose:
        - generate /var/www/html/modules_web.json

    This script does NOT modify mainpage.php, mainpage_pruebas.php or any PHP file.
"""

from __future__ import annotations

import argparse
import json
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Any


# ES:
#   Valores de menú admitidos por el manifiesto Web.
#   mainpage.php usa actualmente dos zonas: top-menu y sidebar.
# EN:
#   Menu values accepted by the Web manifest.
#   mainpage.php currently uses two areas: top-menu and sidebar.
VALID_MENUS = {"top-menu", "sidebar"}


@dataclass(frozen=True)
class SourceRef:
    """
    ES:
        Referencia de trazabilidad hacia el módulo/manifest que declaró una entrada.
    EN:
        Traceability reference to the module/manifest that declared an entry.
    """

    module: str
    manifest: str
    entry_index: int


class ManifestError(RuntimeError):
    """
    ES:
        Error controlado de validación de manifiestos.
    EN:
        Controlled manifest validation error.
    """


# ES:
#   Inferimos la raíz del repo desde la ubicación del propio script.
#   Si el script vive en repo/installation/praesidium_modules_web_installer.py,
#   parents[1] apunta a repo/.
#   No se usa /home//... hardcodeado.
# EN:
#   Infer the repository root from this script location.
#   If the script lives in repo/installation/praesidium_modules_web_installer.py,
#   parents[1] points to repo/.
#   No hardcoded /home//... path is used.
def get_repo_root() -> Path:
    script_path = Path(__file__).resolve()

    # ES:
    #     El script puede vivir directamente en installation/ o dentro de una
    #     subcarpeta de instaladores. Subimos hasta encontrar modern_format/modules
    #     para no depender de una profundidad fija ni de rutas absolutas.
    # EN:
    #     The script may live directly under installation/ or inside an installer
    #     subdirectory. Walk upward until modern_format/modules is found so we do
    #     not depend on a fixed depth or absolute paths.
    for candidate in [script_path.parent, *script_path.parents]:
        if (candidate / "modern_format" / "modules").is_dir():
            return candidate

    raise ManifestError("repository root not found: modern_format/modules is missing in parent directories")


# ES:
#   Convierte valores de posición declarados como string o número a entero.
#   Se exige entero para poder ordenar menús/páginas de forma estable.
# EN:
#   Convert position values declared as string or number to integer.
#   An integer is required so menus/pages can be sorted deterministically.
def parse_position(value: Any, *, field_name: str, context: str) -> int:
    try:
        position = int(str(value))
    except (TypeError, ValueError) as exc:
        raise ManifestError(f"{context}: {field_name} must be an integer-like value") from exc

    if position < 0:
        raise ManifestError(f"{context}: {field_name} must be >= 0")

    return position


# ES:
#   Carga un JSON de forma estricta y devuelve objeto Python.
#   Los errores incluyen la ruta relativa para facilitar auditoría.
# EN:
#   Strictly load a JSON file and return the Python object.
#   Errors include the relative path to make audits easier.
def load_json(path: Path, repo_root: Path) -> Any:
    try:
        with path.open("r", encoding="utf-8") as handle:
            return json.load(handle)
    except json.JSONDecodeError as exc:
        rel = path.relative_to(repo_root)
        raise ManifestError(f"{rel}: invalid JSON: {exc}") from exc


# ES:
#   Descubre módulos listando sólo directorios directos bajo modern_format/modules.
#   El orden alfabético hace que la salida sea reproducible.
# EN:
#   Discover modules by listing only direct directories under modern_format/modules.
#   Alphabetical order makes the output reproducible.
def discover_modules(modules_dir: Path) -> list[Path]:
    if not modules_dir.is_dir():
        raise ManifestError(f"modules directory not found: {modules_dir}")

    return sorted(path for path in modules_dir.iterdir() if path.is_dir())


# ES:
#   Normaliza y valida una página declarada dentro de mainpage_design.pages.
#   No comprueba que dataPage exista físicamente: este script sólo compila diseño.
# EN:
#   Normalize and validate a page declared inside mainpage_design.pages.
#   It does not check that dataPage exists on disk: this script only compiles design.
def normalize_page(raw_page: Any, *, context: str) -> dict[str, Any]:
    if not isinstance(raw_page, dict):
        raise ManifestError(f"{context}: page entry must be an object")

    data_page = raw_page.get("dataPage")
    key_lang = raw_page.get("keyLang")
    position_page = parse_position(
        raw_page.get("position_page"),
        field_name="position_page",
        context=context,
    )

    if not isinstance(data_page, str) or not data_page.strip():
        raise ManifestError(f"{context}: dataPage must be a non-empty string")

    if not isinstance(key_lang, str) or not key_lang.strip():
        raise ManifestError(f"{context}: keyLang must be a non-empty string")

    return {
        "position_page": position_page,
        "dataPage": data_page,
        "keyLang": key_lang,
    }


# ES:
#   Normaliza y valida una entrada mainpage_design de un módulo.
#   Una entrada representa una sección/grupo de menú o una contribución a uno existente.
# EN:
#   Normalize and validate one module mainpage_design entry.
#   An entry represents a menu section/group or a contribution to an existing one.
def normalize_design_entry(
    raw_entry: Any,
    *,
    source: SourceRef,
) -> dict[str, Any]:
    context = f"{source.manifest}:mainpage_design[{source.entry_index}]"

    if not isinstance(raw_entry, dict):
        raise ManifestError(f"{context}: entry must be an object")

    menu = raw_entry.get("menu")
    if menu not in VALID_MENUS:
        raise ManifestError(f"{context}: menu must be one of {sorted(VALID_MENUS)}, got {menu!r}")

    # ES:
    #   sidebar y top-menu no tienen el mismo contrato:
    #       - sidebar usa position_sidebar + summary para crear bloques <details>
    #       - top-menu es una lista plana de enlaces y sólo necesita position_page
    #   Por eso position_sidebar sólo es obligatorio para sidebar.
    # EN:
    #   sidebar and top-menu do not share the same contract:
    #       - sidebar uses position_sidebar + summary to create <details> blocks
    #       - top-menu is a flat link list and only needs position_page
    #   Therefore position_sidebar is required only for sidebar.
    if menu == "sidebar":
        position_sidebar = parse_position(
            raw_entry.get("position_sidebar"),
            field_name="position_sidebar",
            context=context,
        )
    else:
        position_sidebar = None

    summary = raw_entry.get("summary", "")
    if not isinstance(summary, str):
        raise ManifestError(f"{context}: summary must be a string")

    if menu == "sidebar" and not summary.strip():
        raise ManifestError(f"{context}: sidebar summary must be a non-empty string")

    raw_pages = raw_entry.get("pages")
    if not isinstance(raw_pages, list) or not raw_pages:
        raise ManifestError(f"{context}: pages must be a non-empty list")

    pages = [
        normalize_page(raw_page, context=f"{context}.pages[{page_index}]")
        for page_index, raw_page in enumerate(raw_pages)
    ]

    # ES:
    #   Ordenamos páginas por position_page y después por dataPage/keyLang para tener
    #   una salida estable aunque dos módulos declaren la misma posición.
    # EN:
    #   Sort pages by position_page and then by dataPage/keyLang to keep stable output
    #   even when two modules declare the same position.
    pages.sort(key=lambda page: (page["position_page"], page["dataPage"], page["keyLang"]))

    return {
        "menu": menu,
        "position_sidebar": position_sidebar,
        "summary": summary,
        "pages": pages,
        "source": {
            "module": source.module,
            "manifest": source.manifest,
            "entry_index": source.entry_index,
        },
    }


# ES:
#   Extrae todas las entradas mainpage_design declaradas por un módulo.
#   Si un módulo no declara mainpage_design, simplemente no aporta menú Web.
# EN:
#   Extract all mainpage_design entries declared by a module.
#   If a module does not declare mainpage_design, it simply contributes no Web menu.
def extract_module_design(module_dir: Path, repo_root: Path) -> list[dict[str, Any]]:
    manifest_path = module_dir / "install" / "route_install.json"
    if not manifest_path.is_file():
        return []

    manifest_rel = str(manifest_path.relative_to(repo_root))
    manifest = load_json(manifest_path, repo_root)
    if not isinstance(manifest, dict):
        raise ManifestError(f"{manifest_rel}: root JSON value must be an object")

    raw_design = manifest.get("mainpage_design", [])
    if raw_design is None:
        return []

    if not isinstance(raw_design, list):
        raise ManifestError(f"{manifest_rel}: mainpage_design must be a list")

    module_name = module_dir.name
    entries: list[dict[str, Any]] = []
    for entry_index, raw_entry in enumerate(raw_design):
        entries.append(
            normalize_design_entry(
                raw_entry,
                source=SourceRef(
                    module=module_name,
                    manifest=manifest_rel,
                    entry_index=entry_index,
                ),
            )
        )

    return entries


# ES:
#   Compone la estructura de menús tal como existe hoy en mainpage.php:
#
#       menus.top-menu -> lista plana de enlaces <a>
#       menus.sidebar  -> lista de secciones <details> con summary + pages
#
#   Importante:
#       - top-menu NO se agrupa como sidebar; no tiene summary ni details.
#       - sidebar SÍ se agrupa por position_sidebar + summary.
#       - varios módulos pueden aportar páginas a la misma sección sidebar.
#         Ejemplo: networking y routing dentro de sidebar_networking.
#
# EN:
#   Compose the menu structure as it exists today in mainpage.php:
#
#       menus.top-menu -> flat list of <a> links
#       menus.sidebar  -> list of <details> sections with summary + pages
#
#   Important:
#       - top-menu is NOT grouped like sidebar; it has no summary/details.
#       - sidebar IS grouped by position_sidebar + summary.
#       - several modules may contribute pages to the same sidebar section.
#         Example: networking and routing inside sidebar_networking.
def group_design_entries(entries: list[dict[str, Any]]) -> dict[str, list[dict[str, Any]]]:
    top_menu: list[dict[str, Any]] = []
    sidebar_groups: dict[tuple[int, str], dict[str, Any]] = {}

    for entry in entries:
        if entry["menu"] == "top-menu":
            # ES:
            #   mainpage.php pinta top-menu como enlaces directos, no como secciones.
            #   Por eso aplanamos pages y conservamos la trazabilidad de origen.
            # EN:
            #   mainpage.php renders top-menu as direct links, not as sections.
            #   Therefore pages are flattened while preserving source traceability.
            for page in entry["pages"]:
                top_menu.append(
                    {
                        "position": page["position_page"],
                        "dataPage": page["dataPage"],
                        "keyLang": page["keyLang"],
                        "source": entry["source"],
                    }
                )
            continue

        # ES:
        #   Sidebar representa bloques <details>. Si dos módulos declaran la misma
        #   posición y summary, sus páginas se fusionan en el mismo bloque.
        # EN:
        #   Sidebar represents <details> blocks. If two modules declare the same
        #   position and summary, their pages are merged into the same block.
        key = (entry["position_sidebar"], entry["summary"])
        group = sidebar_groups.setdefault(
            key,
            {
                "position": entry["position_sidebar"],
                "summary": entry["summary"],
                "pages": [],
                "sources": [],
            },
        )
        group["pages"].extend(entry["pages"])
        group["sources"].append(entry["source"])

    top_menu.sort(key=lambda item: (item["position"], item["dataPage"], item["keyLang"]))

    sidebar: list[dict[str, Any]] = []
    for group in sidebar_groups.values():
        group["pages"].sort(key=lambda page: (page["position_page"], page["dataPage"], page["keyLang"]))
        group["sources"].sort(key=lambda source: (source["module"], source["entry_index"]))
        sidebar.append(group)

    sidebar.sort(key=lambda section: (section["position"], section["summary"]))

    return {
        "top-menu": top_menu,
        "sidebar": sidebar,
    }


# ES:
#   Construye la estructura final que se escribirá en modules_web.json.
# EN:
#   Build the final structure that will be written to modules_web.json.
def build_modules_web(repo_root: Path) -> dict[str, Any]:
    modules_dir = repo_root / "modern_format" / "modules"
    module_dirs = discover_modules(modules_dir)

    entries: list[dict[str, Any]] = []
    for module_dir in module_dirs:
        entries.extend(extract_module_design(module_dir, repo_root))

    grouped = group_design_entries(entries)

    return {
        "schema_version": 1,
        "generated_from": "modern_format/modules/*/install/route_install.json:mainpage_design",
        "menus": grouped,
    }


# ES:
#   Escribe JSON con Unicode visible para preservar acentos y emoticonos.
#   Añade salto final para diffs limpios.
# EN:
#   Write JSON with visible Unicode to preserve accents and emoji.
#   Add a final newline for clean diffs.
def write_json(path: Path, payload: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )


# ES:
#   Punto de entrada CLI.
#   Por defecto genera modules_web.json. Con --dry-run sólo muestra resumen.
# EN:
#   CLI entry point.
#   By default it generates modules_web.json. With --dry-run it only prints a summary.
def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Generate /var/www/html/modules_web.json from module mainpage_design manifests.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Validate and show what would be generated without writing modules_web.json.",
    )
    args = parser.parse_args(argv)

    repo_root = get_repo_root()
    output_path = Path("/var/www/html/modules_web.json")

    try:
        payload = build_modules_web(repo_root)
    except ManifestError as exc:
        print(f"[ERROR] {exc}", file=sys.stderr)
        return 1

    top_count = len(payload["menus"].get("top-menu", []))
    sidebar_count = len(payload["menus"].get("sidebar", []))
    page_count = top_count + sum(
        len(section.get("pages", []))
        for section in payload["menus"].get("sidebar", [])
    )

    print(f"[INFO] repo_root={repo_root}")
    print("[INFO] modules_dir=modern_format/modules")
    print("[INFO] output=/var/www/html/modules_web.json")
    print(f"[INFO] top-menu links={top_count}")
    print(f"[INFO] sidebar sections={sidebar_count}")
    print(f"[INFO] total pages={page_count}")

    if args.dry_run:
        print("[DRY-RUN] modules_web.json not written")
        return 0

    write_json(output_path, payload)
    print("[OK] wrote /var/www/html/modules_web.json")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
