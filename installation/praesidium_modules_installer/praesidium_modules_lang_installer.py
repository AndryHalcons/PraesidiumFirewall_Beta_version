#!/usr/bin/env python3
"""
ES:
    Compilador/instalador de idiomas modular para Praesidium.

    Este script fusiona los JSON de idioma declarados por cada módulo con el
    JSON global de la WebGUI principal y genera los PHP runtime actuales:

        modern_format/system/web_gui/mainpage/lang/en.php
        modern_format/system/web_gui/mainpage/lang/es.php

    La fuente de verdad queda repartida así:

        modern_format/modules/<module>/lang/english.json
        modern_format/modules/<module>/lang/espanol.json
        modern_format/system/web_gui/mainpage/lang/english.json
        modern_format/system/web_gui/mainpage/lang/espanol.json

    Importante:
        - No usa rutas absolutas hardcodeadas.
        - Calcula la raíz del repo desde la ubicación del propio script.
        - Falla ante claves duplicadas con valores distintos.
        - Permite claves duplicadas si el valor es idéntico.
        - Conserva Unicode/emoticonos en el PHP generado.

EN:
    Modular language compiler/installer for Praesidium.

    This script merges language JSON files declared by each module with the
    main WebGUI global JSON file and generates the current runtime PHP files:

        modern_format/system/web_gui/mainpage/lang/en.php
        modern_format/system/web_gui/mainpage/lang/es.php

    The source of truth is distributed as:

        modern_format/modules/<module>/lang/english.json
        modern_format/modules/<module>/lang/espanol.json
        modern_format/system/web_gui/mainpage/lang/english.json
        modern_format/system/web_gui/mainpage/lang/espanol.json

    Important:
        - It does not use hardcoded absolute paths.
        - It derives the repository root from this script location.
        - It fails on duplicate keys with different values.
        - It allows duplicate keys when the value is identical.
        - It preserves Unicode/emojis in the generated PHP.
"""

from __future__ import annotations

import argparse
import json
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Any


# ES: Definición declarativa de un idioma a compilar.
# EN: Declarative definition of a language to compile.
@dataclass(frozen=True)
class LanguageSpec:
    json_name: str
    php_name: str
    label: str


LANGUAGES: tuple[LanguageSpec, ...] = (
    LanguageSpec(json_name="english.json", php_name="en.php", label="English"),
    LanguageSpec(json_name="espanol.json", php_name="es.php", label="Español"),
)


class LangInstallerError(RuntimeError):
    """
    ES: Error controlado del instalador de idiomas.
    EN: Controlled language-installer error.
    """


# ES: Calcula la raíz del repositorio sin hardcodear rutas absolutas.
# EN: Resolve the repository root without hardcoding absolute paths.
def resolve_repo_root() -> Path:
    """
    ES:
        El script puede vivir en installation/ o en una subcarpeta de instaladores.
        Subimos por los directorios padre hasta encontrar modern_format/modules,
        evitando depender de una profundidad fija como parents[1].

    EN:
        The script may live under installation/ or inside an installer subdirectory.
        Walk up parent directories until modern_format/modules is found, avoiding
        a fixed-depth dependency such as parents[1].
    """
    script_path = Path(__file__).resolve()
    for candidate in [script_path.parent, *script_path.parents]:
        if (candidate / "modern_format" / "modules").is_dir():
            return candidate

    raise LangInstallerError("repository root not found: modern_format/modules is missing in parent directories")


# ES: Escapa strings para un array PHP con comillas simples.
# EN: Escape strings for a single-quoted PHP array.
def php_single_quote(value: str) -> str:
    """
    ES:
        PHP con comillas simples sólo necesita escapar backslash y comilla simple.
        Los acentos y emoticonos se conservan tal cual en UTF-8.

    EN:
        Single-quoted PHP strings only need backslash and single quote escaping.
        Accents and emojis are preserved as UTF-8.
    """
    return value.replace("\\", "\\\\").replace("'", "\\'")


# ES: Carga y valida un JSON de idioma.
# EN: Load and validate a language JSON file.
def load_lang_json(path: Path) -> dict[str, str]:
    """
    ES:
        Cada JSON de idioma debe ser un objeto simple clave -> texto.
        No se aceptan listas, objetos anidados ni valores no escalares.

    EN:
        Each language JSON must be a simple object mapping key -> text.
        Lists, nested objects and non-scalar values are not accepted.
    """
    try:
        raw = path.read_text(encoding="utf-8")
    except FileNotFoundError as exc:
        raise LangInstallerError(f"Missing language JSON: {path}") from exc

    try:
        data: Any = json.loads(raw)
    except json.JSONDecodeError as exc:
        raise LangInstallerError(f"Invalid JSON in {path}: {exc}") from exc

    if not isinstance(data, dict):
        raise LangInstallerError(f"Language JSON root must be an object: {path}")

    normalized: dict[str, str] = {}
    for key, value in data.items():
        if not isinstance(key, str) or key == "":
            raise LangInstallerError(f"Invalid language key in {path}: {key!r}")
        if not isinstance(value, (str, int, float, bool)) and value is not None:
            raise LangInstallerError(
                f"Invalid value for key {key!r} in {path}: expected scalar text"
            )
        normalized[key] = "" if value is None else str(value)

    return normalized


# ES: Añade un diccionario al resultado fusionado comprobando conflictos.
# EN: Add a dictionary to the merged result while checking conflicts.
def merge_lang_dict(
    merged: dict[str, str],
    origins: dict[str, Path],
    incoming: dict[str, str],
    source_path: Path,
) -> None:
    """
    ES:
        Si una clave aparece dos veces con el mismo valor, se permite.
        Si aparece con valores distintos, se para para evitar pisados silenciosos.

    EN:
        If a key appears twice with the same value, it is allowed.
        If it appears with different values, stop to prevent silent overwrites.
    """
    for key, value in incoming.items():
        if key not in merged:
            merged[key] = value
            origins[key] = source_path
            continue

        if merged[key] != value:
            first_path = origins[key]
            raise LangInstallerError(
                "Language key conflict:\n"
                f"  key: {key}\n"
                f"  first: {first_path} -> {merged[key]!r}\n"
                f"  second: {source_path} -> {value!r}"
            )


# ES: Descubre módulos de forma estable y ordenada.
# EN: Discover modules in a stable sorted order.
def discover_modules(modules_dir: Path) -> list[Path]:
    """
    ES:
        Sólo se consideran directorios directos dentro de modern_format/modules.

    EN:
        Only direct directories inside modern_format/modules are considered.
    """
    if not modules_dir.is_dir():
        raise LangInstallerError(f"Modules directory not found: {modules_dir}")
    return sorted(path for path in modules_dir.iterdir() if path.is_dir())


# ES: Genera el contenido PHP final.
# EN: Generate the final PHP content.
def render_php_array(merged: dict[str, str], language_label: str) -> str:
    """
    ES:
        La salida se ordena por clave para que el diff sea estable.
        Se incluyen comentarios ES/EN explicando que el archivo es generado.

    EN:
        Output is sorted by key for stable diffs.
        ES/EN comments explain that this is a generated file.
    """
    lines: list[str] = [
        "<?php",
        "/**",
        " * ES: Archivo generado automáticamente por installation/praesidium_modules_lang_installer.py.",
        " *     No edites este PHP a mano; edita los JSON de idioma modulares.",
        " * EN: Automatically generated by installation/praesidium_modules_lang_installer.py.",
        " *     Do not edit this PHP manually; edit the modular language JSON files.",
        f" * Language: {language_label}",
        " */",
        "return [",
    ]

    max_key_len = max((len(key) for key in merged), default=0)
    for key in sorted(merged):
        escaped_key = php_single_quote(key)
        escaped_value = php_single_quote(merged[key])
        padding = " " * (max_key_len - len(key))
        lines.append(f"    '{escaped_key}'{padding} => '{escaped_value}',")

    lines.append("];\n")
    return "\n".join(lines)


# ES: Compila un idioma concreto.
# EN: Compile one specific language.
def compile_language(
    repo_root: Path,
    modules_dir: Path,
    global_lang_dir: Path,
    runtime_lang_dir: Path,
    spec: LanguageSpec,
    dry_run: bool,
) -> tuple[int, Path]:
    """
    ES:
        Orden de fusión:
          1. JSON global de mainpage/lang.
          2. JSON de cada módulo ordenado alfabéticamente.

        El orden no permite pisados: cualquier valor distinto en clave duplicada falla.

        La salida se escribe en el directorio runtime /var/www/html/lang para que
        el instalador genere los PHP finales después de copiar system y módulos.

    EN:
        Merge order:
          1. Global mainpage/lang JSON.
          2. Each module JSON in alphabetical order.

        The order does not allow overwrites: any different duplicate value fails.

        Output is written to the /var/www/html/lang runtime directory so the
        installer generates final PHP files after copying system and modules.
    """
    merged: dict[str, str] = {}
    origins: dict[str, Path] = {}

    global_json = global_lang_dir / spec.json_name
    merge_lang_dict(merged, origins, load_lang_json(global_json), global_json.relative_to(repo_root))

    for module_dir in discover_modules(modules_dir):
        module_json = module_dir / "lang" / spec.json_name
        if not module_json.exists():
            # ES: Un módulo puede no tener idioma todavía; se ignora con aviso.
            # EN: A module may not have language yet; ignore it with a warning.
            print(f"[WARN] Missing {module_json.relative_to(repo_root)}; skipping")
            continue
        merge_lang_dict(
            merged,
            origins,
            load_lang_json(module_json),
            module_json.relative_to(repo_root),
        )

    output_php = runtime_lang_dir / spec.php_name
    php_content = render_php_array(merged, spec.label)

    if dry_run:
        print(f"[DRY-RUN] Would write {output_php} with {len(merged)} keys")
    else:
        runtime_lang_dir.mkdir(parents=True, exist_ok=True)
        output_php.write_text(php_content, encoding="utf-8")
        print(f"[OK] Wrote {output_php} with {len(merged)} keys")

    return len(merged), output_php


# ES: Valida las rutas base esperadas.
# EN: Validate expected base paths.
def validate_layout(repo_root: Path) -> tuple[Path, Path]:
    """
    ES:
        Este script sólo conoce rutas relativas dentro del repo:
            modern_format/modules
            modern_format/system/web_gui/mainpage/lang

    EN:
        This script only knows repository-relative paths:
            modern_format/modules
            modern_format/system/web_gui/mainpage/lang
    """
    modules_dir = repo_root / "modern_format" / "modules"
    global_lang_dir = repo_root / "modern_format" / "system" / "web_gui" / "mainpage" / "lang"

    if not modules_dir.is_dir():
        raise LangInstallerError(f"Missing modules directory: {modules_dir}")
    if not global_lang_dir.is_dir():
        raise LangInstallerError(f"Missing global lang directory: {global_lang_dir}")

    return modules_dir, global_lang_dir


# ES: Punto de entrada principal.
# EN: Main entry point.
def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Compile Praesidium modular language JSON files into /var/www/html/lang PHP files."
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Validate and show what would be generated without writing runtime PHP files.",
    )
    args = parser.parse_args(argv)

    try:
        repo_root = resolve_repo_root()
        modules_dir, global_lang_dir = validate_layout(repo_root)

        print(f"[INFO] repo_root={repo_root}")
        print(f"[INFO] modules_dir={modules_dir.relative_to(repo_root)}")
        runtime_lang_dir = Path("/var/www/html/lang")

        print(f"[INFO] global_lang_dir={global_lang_dir.relative_to(repo_root)}")
        print(f"[INFO] output_lang_dir={runtime_lang_dir}")

        for spec in LANGUAGES:
            compile_language(
                repo_root=repo_root,
                modules_dir=modules_dir,
                global_lang_dir=global_lang_dir,
                runtime_lang_dir=runtime_lang_dir,
                spec=spec,
                dry_run=args.dry_run,
            )

    except LangInstallerError as exc:
        print(f"[ERROR] {exc}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
