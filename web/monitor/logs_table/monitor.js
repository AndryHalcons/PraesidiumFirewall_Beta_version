function tablaMonigorOptions() {
  const container = document.getElementById("tabla-monitorOptions");
  container.innerHTML = "";

  const table = document.createElement("table");
  table.className = "interfaz";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const columnas = [
    { key: "search", label: LANG.search },
    { key: "init_date", label: LANG.init_date },
    { key: "init_time", label: LANG.init_time },
    { key: "end_date", label: LANG.end_date },
    { key: "end_time", label: LANG.end_time },
    { key: "ip_addr", label: LANG.ip_addr },
    { key: "ip_dest", label: LANG.ip_dest },
    { key: "sport", label: LANG.sport },
    { key: "dport", label: LANG.dport },
    { key: "protocol", label: LANG.protocol },
    { key: "action", label: LANG.action }, // 👈 Nueva columna
    { key: "max_record", label: LANG.max_record }
  ];

  columnas.forEach(col => {
    const th = document.createElement("th");
    th.textContent = col.label;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  // Fila para introducir datos
  const filaInputs = document.createElement("tr");
  const inputs = [];

  columnas.forEach(col => {
    const td = document.createElement("td");

    if (col.key === "search") {
      const btn = document.createElement("button");
      btn.textContent = col.label;
      btn.className = "buscar-monitor";
      btn.onclick = () => buttonSearchMonitor();
      td.appendChild(btn);
    } else if (col.key === "protocol") {
      const select = document.createElement("select");
      select.className = "campo-resumen";

      ["", "TCP", "UDP", "ICMP" ].forEach(proto => {
        const option = document.createElement("option");
        option.value = proto;
        option.textContent = proto;
        select.appendChild(option);
      });

      td.appendChild(select);
      inputs.push({ col: col.key, input: select });
    } else if (col.key === "action") {
      const select = document.createElement("select");
      select.className = "campo-resumen";

      ["", "ACCEPT", "DROP"].forEach(val => {
        const option = document.createElement("option");
        option.value = val;
        option.textContent = val;
        select.appendChild(option);
      });

      td.appendChild(select);
      inputs.push({ col: col.key, input: select });
    } else if (col.key === "max_record") {
      const select = document.createElement("select");
      select.className = "campo-resumen";

      [100, 200, 500].forEach(val => {
        const option = document.createElement("option");
        option.value = val;
        option.textContent = val;
        select.appendChild(option);
      });

      td.appendChild(select);
      inputs.push({ col: col.key, input: select });
    } else {
      let inputType = "text";
      if (col.key.includes("date")) inputType = "date";
      if (col.key.includes("time")) inputType = "time";
      if (["sport", "dport"].includes(col.key)) inputType = "number";

      const input = document.createElement("input");
      input.type = inputType;
      input.className = "campo-resumen";
      td.appendChild(input);
      inputs.push({ col: col.key, input });
    }

    filaInputs.appendChild(td);
  });

  tbody.appendChild(filaInputs);

  // Fila vacía para mostrar resultados u otra información
  const filaDatos = document.createElement("tr");
  columnas.forEach(() => {
    const td = document.createElement("td");
    td.className = "dato-resumen";
    td.textContent = "";
    filaDatos.appendChild(td);
  });
  tbody.appendChild(filaDatos);

  table.appendChild(tbody);
  container.appendChild(table);

  // 🔧 Llamar al backend para obtener hora actual
  fetch("/common_functions/get_system_time.php")
    .then(res => res.json())
    .then(data => {
      const now = new Date(`${data.date}T${data.time}`);
      const oneHourBefore = new Date(now.getTime() - 60 * 60 * 1000);

      const formatDate = d => d.toISOString().slice(0, 10);
      const formatTime = d => d.toTimeString().slice(0, 5);

      inputs.forEach(({ col, input }) => {
        if (col === "init_date") input.value = formatDate(oneHourBefore);
        if (col === "init_time") input.value = formatTime(oneHourBefore);
        if (col === "end_date") input.value = formatDate(now);
        if (col === "end_time") input.value = formatTime(now);
      });
    })
    .catch(err => {
      console.error("Error al obtener la hora del sistema:", err);
    });
}

function mostrarMonitorRegistros(data) {
  const container = document.getElementById("tabla-monitorRegistros");
  container.innerHTML = "";

  const table = document.createElement("table");
  table.className = "interfaz";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const columnas = [
    { key: "date", label: LANG.date },
    { key: "time", label: LANG.time }, // 👈 asegúrate de tener esto en el archivo de idioma
    { key: "ip_addr", label: LANG.ip_addr },
    { key: "ip_dest", label: LANG.ip_dest },
    { key: "sport", label: LANG.sport },
    { key: "dport", label: LANG.dport },
    { key: "protocol", label: LANG.protocol },
    { key: "action", label: LANG.action } // 👈 también añade esto al archivo de idioma
  ];

  columnas.forEach(col => {
    const th = document.createElement("th");
    th.textContent = col.label;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  data.forEach(registro => {
    const fila = document.createElement("tr");

    columnas.forEach(col => {
      const td = document.createElement("td");
      td.textContent = registro[col.key] || "";
      fila.appendChild(td);
    });

    tbody.appendChild(fila);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}


function buttonSearchMonitor() {
  const inputs = document.querySelectorAll(".campo-resumen");
  const params = {};

  inputs.forEach(input => {
    const col = input.closest("td").cellIndex;
    const header = document.querySelectorAll("#tabla-monitorOptions thead th")[col];
    const key = Object.keys(LANG).find(k => LANG[k] === header.textContent);
    if (key) {
      params[key] = input.value;
    }
  });

  fetch("/monitor/get_logs/get_logs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(params)
  })
    .then(res => res.json())
    .then(data => {
      mostrarMonitorRegistros(data); // 👈 Aquí se renderiza la tabla de resultados
    })
    .catch(err => {
      console.error("Error al consultar registros:", err);
    });
}



tablaMonigorOptions();
