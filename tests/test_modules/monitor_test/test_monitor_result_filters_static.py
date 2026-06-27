#!/usr/bin/env python3
"""
Test: test_monitor_result_filters_static.py

Objetivo:
    Proteger el filtro secundario por columna en la tabla de resultados del Monitor.
    Debe filtrar sólo filas ya extraídas en cliente, sin reextraer logs ni tocar backend.

Goal:
    Protect the secondary per-column filter in the Monitor result table.
    It must filter already extracted rows client-side, without re-extracting logs or touching backend.
"""
from __future__ import annotations

from pathlib import Path
import sys

REPO_ROOT = Path(__file__).resolve().parents[3]
MONITOR_JS = REPO_ROOT / "web" / "monitor" / "logs_table" / "monitor.js"


def require(text: str, needle: str, label: str) -> None:
    if needle not in text:
        raise AssertionError(f"Missing {label}: {needle}")


def main() -> int:
    js = MONITOR_JS.read_text(encoding="utf-8")

    # Funciones nuevas acotadas al Monitor.
    # New functions scoped to Monitor.
    for fn in [
        "monitorLogFilterValue",
        "monitorLogRowMatchesFilters",
        "monitorApplyLogResultFilters",
        "monitorRenderLogRows",
        "monitorCreateLogFilterRow",
    ]:
        require(js, f"function {fn}", fn)

    # La segunda fila vive en thead y reutiliza el estilo del filtro genérico.
    # The second row lives in thead and reuses the generic filter style.
    require(js, "generic-table-filter-row monitor-log-filter-row", "filter row class")
    require(js, "generic-table-filter-input monitor-log-filter-input", "filter input class")
    require(js, "thead.appendChild(filterRow);", "filter row appended to thead")

    # El filtro es cliente y tipo %like% mediante includes().
    # Filtering is client-side and %like%-style via includes().
    require(js, "monitorLogFilterValue(row[column]).includes(expected)", "%like% includes filter")
    require(js, "return rows.filter(row => monitorLogRowMatchesFilters(row, columns, activeFilters));", "client-side rows filter")

    # No debe llamar a get_logs.php al teclear; sólo repintar.
    # It must not call get_logs.php while typing; only repaint.
    require(js, "input.addEventListener(\"input\", () =>", "input event")
    require(js, "repaintRows();", "input repaint")
    require(js, "Only repaints the visible table", "English repaint comment")
    require(js, "Sólo repinta la tabla visible", "Spanish repaint comment")

    # La extracción principal sigue centralizada en searchLogs().
    # Main extraction remains centralized in searchLogs().
    occurrences = js.count("/monitor/get_logs/get_logs.php")
    if occurrences != 1:
        raise AssertionError(f"Expected exactly one get_logs fetch, found {occurrences}")

    print("PASS: monitor result filters static contract")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except AssertionError as exc:
        print(f"FAIL: {exc}", file=sys.stderr)
        raise SystemExit(1)
