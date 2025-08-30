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
    { key: "proto", label: LANG.proto },
    { key: "action", label: LANG.action },
    { key: "firewall", label: LANG.firewall },
    { key: "max_record", label: LANG.max_record }
  ];

  columnas.forEach(col => {
    const th = document.createElement("th");
    th.textContent = col.label;
    th.dataset.key = col.key; // 
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

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
    } else if (col.key === "proto") {
      const select = document.createElement("select");
      select.className = "campo-resumen";
      ["", "TCP", "UDP", "ICMP"].forEach(proto => {
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
    } else if (col.key === "firewall") {
      const select = document.createElement("select");
      select.className = "campo-resumen";
      ["NFTABLES", "BPFILTER"].forEach(val => {
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
  if (!container) return;

  container.innerHTML = ""; // Limpiar contenido previo

  const table = document.createElement("table");
  table.className = "interfaz";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const columnas = [
    "fecha", "hora", "handle", "SRC", "SPT", "DST", "DPT", "PROTO", "IN", "OUT", "action"
  ];

  columnas.forEach(col => {
    const th = document.createElement("th");
    th.textContent = col;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  Object.entries(data)
    .sort(([a], [b]) => new Date(b) - new Date(a)) // Ordenar por timestamp descendente
    .forEach(([timestamp, registro]) => {
      const fila = document.createElement("tr");

      const fechaObj = new Date(timestamp);
      const fecha = fechaObj.toISOString().slice(0, 10);
      const hora = fechaObj.toTimeString().slice(0, 8);

      const valores = {
        fecha,
        hora,
        handle: registro.handle || "",
        SRC: registro.SRC || "",
        SPT: registro.SPT || "",
        DST: registro.DST || "",
        DPT: registro.DPT || "",
        PROTO: registro.PROTO || "",
        IN: registro.IN || "",
        OUT: registro.OUT || "",
        action: registro.action || ""
      };

      columnas.forEach(col => {
        const td = document.createElement("td");
        td.textContent = valores[col];
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
    const td = input.closest("td");
    const colIndex = td.cellIndex;
    const header = document.querySelectorAll("#tabla-monitorOptions thead th")[colIndex];
    const key = header.dataset.key;

    if (key) {
      params[key] = input.value;
    }
  });

  // Añadir el usuario de sesión al JSON
  params.user = USERNAME;

  // Mostrar el JSON final serializado
  console.log("📤 JSON enviado al backend:\n", JSON.stringify(params, null, 2));

  fetch("/monitor/get_logs/get_logs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(params)
  })
    .then(res => res.json())
    .then(data => {
      mostrarMonitorRegistros(data);
    })
    .catch(err => {
      console.error("Error al consultar registros:", err);
    });
}



tablaMonigorOptions();
