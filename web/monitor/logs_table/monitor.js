function setMonitorStatus(message, type = "info") {
  const container = document.getElementById("tabla-monitorLogs");
  if (!container) return;

  container.innerHTML = "";
  const status = document.createElement("div");
  status.className = `monitor-status monitor-status-${type}`;
  status.textContent = message;
  container.appendChild(status);
}

function getMonitorSearchButton() {
  return document.querySelector("#tabla-monitorOptions .buscar-monitor");
}

function setMonitorSearchLoading(isLoading) {
  const button = getMonitorSearchButton();
  if (!button) return;

  button.disabled = isLoading;
  button.textContent = isLoading ? "Buscando..." : "Search";
}

let monitorLastLogRows = [];
let monitorLastLogColumns = [];
let monitorLastFilters = {};

function getMonitorExportButton() {
  return document.querySelector("#tabla-monitorOptions .export-monitor-csv");
}

function setMonitorExportAvailable(isAvailable) {
  const button = getMonitorExportButton();
  if (!button) return;

  button.disabled = !isAvailable;
  button.title = isAvailable
    ? "Exportar a CSV los logs que coinciden con el filtro actual"
    : "Busque logs antes de exportar";
}

function escapeCsvValue(value) {
  const text = String(value ?? "");
  const escaped = text.replace(/"/g, '""');
  return /[",\r\n]/.test(escaped) ? `"${escaped}"` : escaped;
}

function buildMonitorCsv() {
  const header = monitorLastLogColumns.map(escapeCsvValue).join(",");
  const rows = monitorLastLogRows.map(row =>
    monitorLastLogColumns.map(column => escapeCsvValue(row[column])).join(",")
  );

  const filterLines = [
    ["Praesidium Firewall - Traffic monitor export"],
    ["Exported_At", new Date().toISOString()],
    ["Filters_JSON", JSON.stringify(monitorLastFilters)],
    []
  ].map(line => line.map(escapeCsvValue).join(","));

  return `${filterLines.join("\r\n")}\r\n${[header, ...rows].join("\r\n")}\r\n`;
}

function exportMonitorLogsCsv() {
  if (!monitorLastLogRows.length || !monitorLastLogColumns.length) {
    setMonitorStatus("No hay logs filtrados para exportar. Pulse Search primero.", "error");
    return;
  }

  const csv = buildMonitorCsv();
  const blob = new Blob(["\ufeff", csv], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-");

  link.href = url;
  link.download = `praesidium-monitor-logs-${timestamp}.csv`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

function renderMonitorTableStructure() {
  const container = document.getElementById("tabla-monitorOptions");
  if (!container) return;

  container.innerHTML = "";
  setMonitorStatus("Cargando filtros del monitor...", "info");

  fetch("/monitor/logs_table/get_table_structure_monitor.php")
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then(data => {
      const columns = data["Search_Filter"];
      if (!Array.isArray(columns)) {
        throw new Error("Estructura de filtros inválida");
      }

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      // Añadir columnas de acciones al principio
      // Add action columns first
      const searchTh = document.createElement("th");
      searchTh.textContent = "Search";
      searchTh.dataset.key = "Search";
      headerRow.appendChild(searchTh);

      const exportTh = document.createElement("th");
      exportTh.textContent = "Export";
      exportTh.dataset.key = "Export";
      headerRow.appendChild(exportTh);

      columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = col;
        th.dataset.key = col;
        headerRow.appendChild(th);
      });

      thead.appendChild(headerRow);
      table.appendChild(thead);

      const tbody = document.createElement("tbody");

      // Crear fila con botón "Search"
      // Create row with the "Search" button
      const inputRow = document.createElement("tr");
      const searchTd = document.createElement("td");
      const searchBtn = document.createElement("button");
      searchBtn.type = "button";
      searchBtn.textContent = "Search";
      searchBtn.className = "buscar-monitor";
      searchBtn.disabled = true;
      searchBtn.title = "Espere a que se carguen los filtros";
      searchBtn.addEventListener("click", searchMonitorLogs);
      searchBtn.addEventListener("mouseup", searchMonitorLogs);
      searchTd.appendChild(searchBtn);
      inputRow.appendChild(searchTd);

      const exportTd = document.createElement("td");
      const exportBtn = document.createElement("button");
      exportBtn.type = "button";
      exportBtn.textContent = "Export CSV";
      exportBtn.className = "export-monitor-csv";
      exportBtn.disabled = true;
      exportBtn.title = "Busque logs antes de exportar";
      exportBtn.addEventListener("click", exportMonitorLogsCsv);
      exportTd.appendChild(exportBtn);
      inputRow.appendChild(exportTd);

      // Añadir celdas vacías para el resto de columnas con dataset.key
      // Add empty cells for the rest of columns with dataset.key
      columns.forEach(col => {
        const td = document.createElement("td");
        td.dataset.key = col;
        inputRow.appendChild(td);
      });

      tbody.appendChild(inputRow);
      table.appendChild(tbody);

      container.appendChild(table);

      // Cargar los controles y habilitar Search solo al terminar
      // Load controls and enable Search only when complete
      return renderMonitorTableContent(columns);
    })
    .then(() => {
      const searchBtn = getMonitorSearchButton();
      if (searchBtn) {
        searchBtn.disabled = false;
        searchBtn.title = "Buscar logs";
      }
      setMonitorExportAvailable(false);
      setMonitorStatus("Seleccione filtros y pulse Search para cargar logs.", "info");
    })
    .catch(err => {
      console.error("Error al cargar estructura de tabla:", err);
      setMonitorStatus(`Error al cargar filtros del monitor: ${err.message}`, "error");
    });
}

function renderMonitorTableContent(columns) {
  const container = document.getElementById("tabla-monitorOptions");
  if (!container) return Promise.resolve();

  const table = container.querySelector("table");
  if (!table) return Promise.resolve();

  const tbody = table.querySelector("tbody");
  if (!tbody) return Promise.resolve();

  const inputRow = tbody.querySelector("tr");
  if (!inputRow) return Promise.resolve();

  // 1. Obtener hora del servidor
  // 1. Get server time
  return fetch("/common_functions/get_system_time.php")
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then(serverTimeData => {
      const serverNow = new Date(`${serverTimeData.date}T${serverTimeData.time}`);
      const oneHourAgo = new Date(serverNow.getTime() - 60 * 60 * 1000);

      const formatDate = d => d.toISOString().slice(0, 10);
      const formatTime = d => d.toTimeString().slice(0, 5);

      // 2. Obtener contenido de la tabla
      // 2. Get table content metadata
      return fetch("/monitor/logs_table/get_table_content_monitor.php")
        .then(res => {
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          return res.json();
        })
        .then(data => {
          const { select = {}, date = {}, time = {} } = data;

          columns.forEach((key, index) => {
            const td = inputRow.children[index + 2];
            if (!td) return;

            let input;

            if (key in select) {
              input = document.createElement("select");
              input.className = "campo-resumen";
              select[key].forEach(optionVal => {
                const option = document.createElement("option");
                option.value = optionVal;
                option.textContent = optionVal;
                input.appendChild(option);
              });
            } else {
              input = document.createElement("input");
              input.className = "campo-resumen";

              if (key in date) {
                input.type = "date";
                if (key === "Start_Date") input.value = formatDate(oneHourAgo);
                if (key === "End_Date") input.value = formatDate(serverNow);
              } else if (key in time) {
                input.type = "time";
                if (key === "Start_Time") input.value = formatTime(oneHourAgo);
                if (key === "End_Time") input.value = formatTime(serverNow);
              } else if (["Source_Port", "Destination_Port"].includes(key)) {
                input.type = "number";
              } else {
                input.type = "text";
              }
            }

            td.innerHTML = "";
            td.appendChild(input);
          });
        });
    });
}

function view_logs_table_Structure(dataLogs) {
  const container = document.getElementById("tabla-monitorLogs");
  if (!container) return;

  container.innerHTML = "";

  if (dataLogs && typeof dataLogs === "object" && dataLogs.error) {
    monitorLastLogRows = [];
    monitorLastLogColumns = [];
    setMonitorExportAvailable(false);
    setMonitorStatus(`Error al buscar logs: ${dataLogs.error}`, "error");
    return;
  }

  if (dataLogs && typeof dataLogs === "object" && dataLogs.info) {
    monitorLastLogRows = [];
    monitorLastLogColumns = [];
    setMonitorExportAvailable(false);
    setMonitorStatus(dataLogs.info, "info");
    return;
  }

  fetch("/monitor/logs_table/get_table_structure_monitor_log.php")
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then(data => {
      const columns = data["Search_Filter"];
      if (!Array.isArray(columns)) {
        throw new Error("Estructura de resultados inválida");
      }

      const table = document.createElement("table");
      table.className = "interfaz";

      // Cabecera
      // Header
      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");
      columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = col;
        th.dataset.key = col;
        headerRow.appendChild(th);
      });
      thead.appendChild(headerRow);
      table.appendChild(thead);

      // Cuerpo
      // Body
      const tbody = document.createElement("tbody");
      const rows = dataLogs && typeof dataLogs === "object" ? Object.values(dataLogs) : [];
      monitorLastLogColumns = columns;
      monitorLastLogRows = rows;
      setMonitorExportAvailable(rows.length > 0);

      if (rows.length === 0) {
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = columns.length;
        td.textContent = "No hay logs para los filtros seleccionados.";
        tr.appendChild(td);
        tbody.appendChild(tr);
      } else {
        rows.forEach(row => {
          const tr = document.createElement("tr");
          columns.forEach(colName => {
            const td = document.createElement("td");
            td.textContent = row[colName] ?? "";
            tr.appendChild(td);
          });
          tbody.appendChild(tr);
        });
      }

      table.appendChild(tbody);
      container.appendChild(table);
    })
    .catch(err => {
      console.error("Error al cargar estructura de resultados:", err);
      setMonitorStatus(`Error al pintar logs: ${err.message}`, "error");
    });
}

function searchMonitorLogs(event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  if (window.__monitorSearchInFlight) {
    return;
  }

  const inputRow = document.querySelector("#tabla-monitorOptions table tbody tr");
  if (!inputRow) {
    setMonitorStatus("Los filtros del monitor aún no están cargados.", "error");
    return;
  }

  const filters = {};

  // Recorremos todos los inputs/selects y usamos el dataset.key del <td>
  // Iterate through all inputs/selects and use the parent td dataset.key
  inputRow.querySelectorAll("input, select").forEach(input => {
    const td = input.closest("td");
    const key = td ? td.dataset.key : null;
    if (key) {
      filters[key] = input.value;
    }
  });

  if (!filters.Start_Date || !filters.Start_Time || !filters.End_Date || !filters.End_Time) {
    setMonitorStatus("Los filtros de fecha/hora aún no están listos. Espere un segundo y vuelva a pulsar Search.", "error");
    return;
  }

  // Añadimos el usuario autenticado publicado por monitor.php.
  // Add the authenticated user published by monitor.php.
  filters.user = (typeof USERNAME !== "undefined" && USERNAME) ? USERNAME : "";

  monitorLastFilters = { ...filters };
  monitorLastLogRows = [];
  monitorLastLogColumns = [];
  setMonitorExportAvailable(false);
  window.__monitorSearchInFlight = true;
  setMonitorSearchLoading(true);
  setMonitorStatus("Buscando logs...", "info");

  fetch("/monitor/get_logs/get_logs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(filters)
  })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then(data => {
      view_logs_table_Structure(data);
    })
    .catch(err => {
      console.error("Error al buscar logs:", err);
      setMonitorStatus(`Error al buscar logs: ${err.message}`, "error");
    })
    .finally(() => {
      window.__monitorSearchInFlight = false;
      setMonitorSearchLoading(false);
    });
}

renderMonitorTableStructure();
