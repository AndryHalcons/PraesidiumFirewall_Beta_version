function renderMonitorTableStructure() {
  const container = document.getElementById("tabla-monitorOptions");
  if (!container) return;

  container.innerHTML = "";

  fetch("/monitor/logs_table/get_table_structure_monitor.php")
    .then(res => res.json())
    .then(data => {
      const columns = data["Search_Filter"];
      if (!Array.isArray(columns)) return;

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      // Añadir columna "Search" al principio
      const searchTh = document.createElement("th");
      searchTh.textContent = "Search";
      searchTh.dataset.key = "Search";
      headerRow.appendChild(searchTh);

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
      const inputRow = document.createElement("tr");
      const searchTd = document.createElement("td");
      const searchBtn = document.createElement("button");
      searchBtn.textContent = "Search";
      searchBtn.className = "buscar-monitor";
      searchBtn.addEventListener("click", searchMonitorLogs);
      searchTd.appendChild(searchBtn);
      inputRow.appendChild(searchTd);

      // Añadir celdas vacías para el resto de columnas con dataset.key
      columns.forEach(col => {
        const td = document.createElement("td");
        td.dataset.key = col; // clave para recoger valores después
        inputRow.appendChild(td);
      });

      tbody.appendChild(inputRow);
      table.appendChild(tbody);

      container.appendChild(table);

      // Llamar a la función de contenido pasando columns
      renderMonitorTableContent(columns);
    })
    .catch(err => {
      console.error("Error al cargar estructura de tabla:", err);
    });
}
function renderMonitorTableContent(columns) {
  const container = document.getElementById("tabla-monitorOptions");
  if (!container) return;

  const table = container.querySelector("table");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  if (!tbody) return;

  const inputRow = tbody.querySelector("tr");
  if (!inputRow) return;

  // 1. Obtener hora del servidor
  fetch("/common_functions/get_system_time.php")
    .then(res => res.json())
    .then(serverTimeData => {
      // Construir un Date a partir de la fecha y hora del servidor
      const serverNow = new Date(`${serverTimeData.date}T${serverTimeData.time}`);
      const oneHourAgo = new Date(serverNow.getTime() - 60 * 60 * 1000);

      const formatDate = d => d.toISOString().slice(0, 10);
      const formatTime = d => d.toTimeString().slice(0, 5);

      // 2. Obtener contenido de la tabla
      fetch("/monitor/logs_table/get_table_content_monitor.php")
        .then(res => res.json())
        .then(data => {
          const { select = {}, date = {}, time = {} } = data;

          columns.forEach((key, index) => {
            const td = inputRow.children[index + 1];
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
        })
        .catch(err => {
          console.error("Error al cargar contenido de tabla:", err);
        });
    })
    .catch(err => {
      console.error("Error al obtener hora del servidor:", err);
    });
}
function view_logs_table_Structure(dataLogs) {
  const container = document.getElementById("tabla-monitorLogs");
  if (!container) return;

  container.innerHTML = "";

  fetch("/monitor/logs_table/get_table_structure_monitor_log.php")
    .then(res => res.json())
    .then(data => {
      const columns = data["Search_Filter"];
      if (!Array.isArray(columns)) return;

      const table = document.createElement("table");
      table.className = "interfaz";

      // Cabecera
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
      const tbody = document.createElement("tbody");

      // Si recibimos datos, pintamos las filas aquí mismo
      if (dataLogs && typeof dataLogs === "object") {
        Object.values(dataLogs).forEach(row => {
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
      console.error("Error al cargar estructura de tabla:", err);
    });
}
function searchMonitorLogs() {
  const inputRow = document.querySelector("#tabla-monitorOptions table tbody tr");
  if (!inputRow) return;

  const filters = {};

  // Recorremos todos los inputs/selects y usamos el dataset.key del <td>
  inputRow.querySelectorAll("input, select").forEach(input => {
    const td = input.closest("td");
    const key = td ? td.dataset.key : null;
    if (key) {
      filters[key] = input.value;
    }
  });

  // Añadimos el usuario autenticado publicado por monitor.php.
  // Add the authenticated user published by monitor.php.
  filters.user = (typeof USERNAME !== "undefined" && USERNAME) ? USERNAME : "";

  // 🔍 Mostrar en consola lo que se va a enviar
  console.log("Enviando al backend:", filters);

  fetch("/monitor/get_logs/get_logs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(filters)
  })
    .then(res => res.json())
    .then(data => {
      view_logs_table_Structure(data);
    })
    .catch(err => {
      console.error("Error al buscar logs:", err);
    });
}



renderMonitorTableStructure();


