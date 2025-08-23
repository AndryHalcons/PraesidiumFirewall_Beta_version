function cargarPoliciesNftablesPrerouting() {
  const chain = "PREROUTING";

  fetch("/policies/common_policy_actions_nftables/order_policies_nftables.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `chain=${encodeURIComponent(chain)}`
  })
  .then(() => {
    return fetch("/policies/common_policy_actions_nftables/reorder_positions_nftables.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `chain=${encodeURIComponent(chain)}`
    });
  })
  .then(() => {
    return fetch("/policies/common_policy_actions_nftables/get_policies_nftables.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `chain=${encodeURIComponent(chain)}`
    });
  })
  .then(response => response.text())
  .then(text => {
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      // Si no es JSON válido, asumimos que no hay reglas
      data = [];
    }

    const container = document.getElementById("nftablesrules-output");
    container.textContent = JSON.stringify(data, null, 2);
    mostrarTablaNftablesPrerouting();
  })
  .catch(error => {
    const container = document.getElementById("nftablesrules-output");
    container.textContent = JSON.stringify([], null, 2); // ⚠️ Tabla vacía pero funcional
    mostrarTablaNftablesPrerouting();
  });
}




function mostrarTablaNftablesPrerouting() {
  const container = document.getElementById("nftablesrules-output");

  const rawJson = container.textContent.trim();
  container.innerHTML = "";

  // 🟢 Botón "Añadir regla" SIEMPRE visible
  const btnAdd = document.createElement("button");
  btnAdd.textContent = LANG.add_policy;
  btnAdd.className = "añadir-regla";
  btnAdd.style.marginBottom = "12px";
  btnAdd.onclick = () => addNewNftablesPrerouting();
  container.appendChild(btnAdd);

  let data;
  try {
    data = JSON.parse(rawJson);
  } catch (e) {
    container.appendChild(document.createTextNode("Error al parsear el JSON."));
    data = []; // ⚠️ Continuamos con tabla vacía
  }

  if (!Array.isArray(data)) {
    container.appendChild(document.createTextNode("El JSON no contiene una lista de reglas."));
    data = []; // ⚠️ Continuamos con tabla vacía
  }

  const table = document.createElement("table");
  table.className = "interfaz";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const columnas = [
    "actions",
    "family", "table", "chain", "handle", "comment", "position",
    "ip.saddr ==", "tcp.sport ==", "ip.daddr ==", "tcp.dport ==",
    "meta.iifname ==", "ct.state in",
    "packets", "bytes",
    "log.prefix", "log.group",
    "dnat.addr", "dnat.port"
  ];

  columnas.forEach(col => {
    const th = document.createElement("th");
    th.textContent = col;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  data.forEach((rule, index) => {
    const fila = document.createElement("tr");

    const actionCell = document.createElement("td");

    const btnEditar = document.createElement("button");
    btnEditar.textContent = LANG.edit;
    btnEditar.className = "editar";
    btnEditar.onclick = () => editarNftablesPrerouting(index, rule, fila, columnas);

    const btnGuardar = document.createElement("button");
    btnGuardar.textContent = LANG.save;
    btnGuardar.className = "guardar";
    btnGuardar.onclick = () => guardarNftablesPrerouting(index, rule, fila, columnas);

    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = LANG.delete;
    btnEliminar.className = "eliminar";
    btnEliminar.onclick = () => eliminarNftablesPrerouting(index, rule, fila);

    [btnEditar, btnGuardar, btnEliminar].forEach(btn => {
      btn.style.marginRight = "4px";
      actionCell.appendChild(btn);
    });

    fila.appendChild(actionCell);

    columnas.slice(1).forEach(col => {
      const td = document.createElement("td");
      let valor = "";

      if (["family", "table", "chain", "handle", "comment", "position"].includes(col)) {
        valor = rule[col] ?? "";
      } else {
        (rule.expr || []).forEach(expr => {
          const tipo = Object.keys(expr)[0];
          const contenido = expr[tipo];

          if (tipo === "match") {
            const left = contenido.left;
            let campo = "";

            if (left?.meta?.key) campo = `meta.${left.meta.key}`;
            if (left?.payload) campo = `${left.payload.protocol}.${left.payload.field}`;
            if (left?.ct?.key) campo = `ct.${left.ct.key}`;

            const etiqueta = `${campo} ${contenido.op}`;

            if (etiqueta === col) {
              const right = contenido.right;
              if (right && typeof right === "object" && right.prefix) {
                valor = `${right.prefix.addr}/${right.prefix.len}`;
              } else if (Array.isArray(right)) {
                valor = right.join(", ");
              } else if (right !== null && right !== undefined) {
                valor = right;
              }
            }
          }

          if (tipo === "counter") {
            if (col === "packets") valor = contenido.packets ?? "";
            if (col === "bytes") valor = contenido.bytes ?? "";
          }

          if (tipo === "log") {
            if (col === "log.prefix") valor = contenido.prefix ?? "";
            if (col === "log.group") valor = contenido.group ?? "";
          }

          if (tipo === "dnat") {
            if (col === "dnat.addr") valor = contenido.addr ?? "";
            if (col === "dnat.port") valor = contenido.port ?? "";
          }
        });
      }

      td.innerHTML = `<span class="valor">${valor}</span>`;
      fila.appendChild(td);
    });

    tbody.appendChild(fila);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}






function addNewNftablesPrerouting() {
  console.log("Añadir nueva regla");

  const formData = new FormData();
  formData.append("chain", "PREROUTING");

  fetch("/policies/common_policy_actions_nftables/add_policies_nftables.php", {
    method: "POST",
    body: formData,
    credentials: "include" // Para enviar cookies de sesión
  })
  .then(response => response.text())
  .then(result => {
    console.log("Respuesta del servidor:", result);
    alert(result);

    // ✅ Recargar correctamente el JSON actualizado
    if (typeof cargarPoliciesNftablesPrerouting === "function") {
      cargarPoliciesNftablesPrerouting();
    }
  })
  .catch(error => {
    console.error("Error al añadir la regla:", error);
    alert("Hubo un error al añadir la regla.");
  });
}


function editarNftablesPrerouting(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1; // Saltamos la celda de acciones

  const camposNoEditables = ["family", "table", "chain", "handle"];
  const columnas = Array.from(document.querySelectorAll("table.interfaz thead th")).map(th => th.textContent);

  const exprMap = {};

  (rule.expr || []).forEach(expr => {
    const tipo = Object.keys(expr)[0];
    const contenido = expr[tipo];

    if (tipo === "match") {
      const left = contenido.left;
      let campo = "";

      if (left.meta?.key) campo = `meta.${left.meta.key}`;
      if (left.payload) campo = `${left.payload.protocol}.${left.payload.field}`;
      if (left.ct?.key) campo = `ct.${left.ct.key}`;

      const etiqueta = `${campo} ${contenido.op}`;
      exprMap[etiqueta] = contenido.right;
    }

    if (tipo === "counter") {
      exprMap["packets"] = contenido.packets;
      exprMap["bytes"] = contenido.bytes;
    }

    if (tipo === "log") {
      exprMap["log.prefix"] = contenido.prefix;
      exprMap["log.group"] = contenido.group;
    }

    if (tipo === "dnat") {
      exprMap["dnat.addr"] = contenido.addr;
      exprMap["dnat.port"] = contenido.port;
    }
  });

  columnas.slice(1).forEach(col => {
    const cell = cells[cellIndex];
    cell.innerHTML = "";

    if (camposNoEditables.includes(col)) {
      cell.innerHTML = `<span class="valor">${rule[col] ?? ""}</span>`;
    } else {
      const input = document.createElement("input");
      input.type = "text";

      let valor = "";

      if (["comment", "position"].includes(col)) {
        valor = rule[col] ?? "";
      } else {
        const exprValor = exprMap[col];
        if (typeof exprValor === "object" && exprValor?.prefix) {
          valor = `${exprValor.prefix.addr}/${exprValor.prefix.len}`;
        } else if (Array.isArray(exprValor)) {
          valor = exprValor.join(", ");
        } else {
          valor = exprValor ?? "";
        }
      }

      input.value = valor;
      cell.appendChild(input);
    }

    cellIndex++;
  });
}






function eliminarNftablesPrerouting(index, rule, row) {
  console.log(`Eliminar regla en índice ${index}`, rule);

  const formData = new FormData();
  formData.append("chain", rule.chain);   // Cadena: PREROUTING, POSTROUTING, input
  formData.append("handle", rule.handle); // ID único de la regla

  fetch("/policies/common_policy_actions_nftables/del_policies_nftables.php", {
    method: "POST",
    body: formData,
    credentials: "include"
  })
  .then(response => response.text())
  .then(result => {
    console.log("Respuesta del servidor:", result);
    alert(result);

    // 🔄 Recargar tabla actualizada
    if (typeof cargarPoliciesNftablesPrerouting === "function") {
      cargarPoliciesNftablesPrerouting();
    }
  })
  .catch(error => {
    console.error("Error al eliminar la regla:", error);
    alert("Hubo un error al eliminar la regla.");
  });
}



function guardarNftablesPrerouting(index, rule, row, columnas) {
  const celdas = row.querySelectorAll("td");
  const nuevaExpr = [];

  columnas.slice(1).forEach((col, i) => {
    const celda = celdas[i + 1];
    const input = celda.querySelector("input");
    const span = celda.querySelector(".valor");
    const valor = input?.value.trim() ?? span?.textContent.trim() ?? "";
    const limpio = valor === "" ? "" : valor;

    if (["family", "table", "chain", "handle", "comment", "position"].includes(col)) {
      rule[col] = limpio;
      return;
    }

    if (col === "packets" || col === "bytes") {
      let counter = nuevaExpr.find(e => e.counter);
      if (!counter) {
        counter = { counter: { packets: 0, bytes: 0 } };
        nuevaExpr.push(counter);
      }
      counter.counter[col] = parseInt(valor) || 0;
      return;
    }

    if (col === "log.prefix" || col === "log.group") {
      let log = nuevaExpr.find(e => e.log);
      if (!log) {
        log = { log: {} };
        nuevaExpr.push(log);
      }
      log.log[col.split(".")[1]] = col === "log.group" ? parseInt(valor) || 0 : valor;
      return;
    }

    if (col === "dnat.addr" || col === "dnat.port") {
      let dnat = nuevaExpr.find(e => e.dnat);
      if (!dnat) {
        dnat = { dnat: {} };
        nuevaExpr.push(dnat);
      }
      dnat.dnat[col.split(".")[1]] = col === "dnat.port" ? parseInt(valor) || 0 : valor;
      return;
    }

    const match = {
      match: {
        op: col.split(" ").pop(),
        left: {},
        right: null
      }
    };

    const campo = col.replace(` ${match.match.op}`, "");

    if (campo.startsWith("meta.")) {
      match.match.left.meta = { key: campo.split(".")[1] };
      match.match.right = limpio;
    } else if (campo.startsWith("ct.")) {
      match.match.left.ct = { key: campo.split(".")[1] };
      match.match.right = limpio.split(",").map(v => v.trim());
    } else if (campo.includes(".")) {
      const [proto, field] = campo.split(".");
      match.match.left.payload = { protocol: proto, field: field };

      if (limpio.includes("/")) {
        const [addr, len] = limpio.split("/");
        match.match.right = { prefix: { addr, len: parseInt(len) || 0 } };
      } else if (limpio.includes(",")) {
        match.match.right = limpio.split(",").map(v => v.trim());
      } else if (!isNaN(limpio)) {
        match.match.right = parseInt(limpio);
      } else {
        match.match.right = limpio;
      }
    }

    nuevaExpr.push(match);
  });

  rule.expr = nuevaExpr;

  // 🖥️ Mostrar el JSON generado en consola
  console.log("JSON generado para la regla:", JSON.stringify(rule, null, 2));

  // 📤 Enviar al backend
  fetch("/policies/common_policy_actions_nftables/update_policies_nftables.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      chain: "PREROUTING",
      rule: rule
    })
  })
  .then(res => res.json())
  .then(data => {
    console.log("Respuesta del servidor:", data);
    cargarPoliciesNftablesPrerouting(); // Recargar tabla si todo va bien
  })
  .catch(err => {
    console.error("Error al enviar la regla:", err);
  });
}











cargarPoliciesNftablesPrerouting()