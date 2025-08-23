function cargarPoliciesNftablesPostrouting() {
const chain = "POSTROUTING";
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
    data = []; // ⚠️ Si no es JSON válido, asumimos que no hay reglas
  }
  const container = document.getElementById("nftablesrules-output-postrouting");
  container.textContent = JSON.stringify(data, null, 2);
  mostrarTablaNftablesPostrouting();
})
.catch(error => {
  const container = document.getElementById("nftablesrules-output-postrouting");
  container.textContent = JSON.stringify([], null, 2); // ⚠️ Tabla vacía pero funcional
  mostrarTablaNftablesPostrouting();
});
}





function mostrarTablaNftablesPostrouting() {
  const container = document.getElementById("nftablesrules-output-postrouting");

  const rawJson = container.textContent.trim();
  container.innerHTML = "";

  const btnAdd = document.createElement("button");
  btnAdd.textContent = LANG.add_policy;
  btnAdd.className = "añadir-regla";
  btnAdd.style.marginBottom = "12px";
  btnAdd.onclick = () => addNewNftablesPostrouting();
  container.appendChild(btnAdd);

  let data;
  try {
    data = JSON.parse(rawJson);
  } catch (e) {
    container.appendChild(document.createTextNode("Error al parsear el JSON."));
    return;
  }

  if (!Array.isArray(data)) {
    container.appendChild(document.createTextNode("El JSON no contiene una lista de reglas."));
    return;
  }

  const table = document.createElement("table");
  table.className = "interfaz";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const columnas = [
    "actions",
    "family", "table", "chain", "handle", "comment", "position",
    "ip.protocol",
    "ip.saddr", "sport", "ip.daddr", "dport",
    "meta.oifname", "ct.state",
    "packets", "bytes",
    "log.prefix", "log.group",
    "snat.addr"
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
    btnEditar.onclick = () => editarNftablesPostrouting(index, rule, fila, columnas);

    const btnGuardar = document.createElement("button");
    btnGuardar.textContent = LANG.save;
    btnGuardar.className = "guardar";
    btnGuardar.onclick = () => guardarNftablesPostrouting(index, rule, fila, columnas);

    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = LANG.delete;
    btnEliminar.className = "eliminar";
    btnEliminar.onclick = () => eliminarNftablesPostrouting(index, rule, fila);

    [btnEditar, btnGuardar, btnEliminar].forEach(btn => {
      btn.style.marginRight = "4px";
      actionCell.appendChild(btn);
    });

    fila.appendChild(actionCell);

    columnas.slice(1).forEach(col => {
      const td = document.createElement("td");
      let valor = "";
      let op = "==";

      if (["family", "table", "chain", "handle", "comment", "position"].includes(col)) {
        valor = rule[col] ?? "";
        td.innerHTML = `<span class="valor">${valor}</span>`;
      } else {
        (rule.expr || []).forEach(expr => {
          const tipo = Object.keys(expr)[0];
          const contenido = expr[tipo];

          if (tipo === "match") {
            const left = contenido.left;
            let campo = "";

            if (left?.meta?.key) campo = `meta.${left.meta.key}`;
            if (left?.payload) {
              const proto = left.payload.protocol;
              const field = left.payload.field;
              if (proto === "tcp" && field === "sport") campo = "sport";
              else if (proto === "tcp" && field === "dport") campo = "dport";
              else campo = `${proto}.${field}`;
            }
            if (left?.ct?.key) campo = `ct.${left.ct.key}`;

            if (campo === col) {
              op = contenido.op;
              const right = contenido.right;

              if (right?.prefix) {
                valor = `${right.prefix.addr}/${right.prefix.len}`;
              } else if (Array.isArray(right)) {
                valor = right.join(", ");
              } else if (right?.set) {
                valor = right.set.map(item => {
                  if (item.prefix) return `${item.prefix.addr}/${item.prefix.len}`;
                  return item;
                }).join(", ");
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

          if (tipo === "snat") {
            if (col === "snat.addr") valor = contenido.addr ?? "";
          }
        });

        if (col === "ip.protocol") {
          const select = document.createElement("select");
          select.disabled = true;

          ["tcp", "udp"].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt;
            if (opt === valor) option.selected = true;
            select.appendChild(option);
          });

          td.appendChild(select);
        } else if (["ip.saddr", "sport", "ip.daddr", "dport"].includes(col)) {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.disabled = true;
          checkbox.checked = op === "!=";
          checkbox.className = "negate";
          checkbox.title = "Negate (usa != en vez de ==)";

          const label = document.createElement("label");
          label.style.fontWeight = "bold";
          label.textContent = "negate";
          label.style.marginRight = "6px";

          const span = document.createElement("span");
          span.className = "valor";
          span.textContent = valor;

          td.appendChild(label);
          td.appendChild(checkbox);
          td.appendChild(document.createTextNode(" "));
          td.appendChild(span);
        } else {
          td.innerHTML = `<span class="valor">${valor}</span>`;
        }
      }

      fila.appendChild(td);
    });

    tbody.appendChild(fila);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}















function addNewNftablesPostrouting() {
  console.log("Añadir nueva regla");

  const formData = new FormData();
  formData.append("chain", "POSTROUTING");

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
    if (typeof cargarPoliciesNftablesPostrouting === "function") {
      cargarPoliciesNftablesPostrouting();
    }
  })
  .catch(error => {
    console.error("Error al añadir la regla:", error);
    alert("Hubo un error al añadir la regla.");
  });
}


function editarNftablesPostrouting(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1; // Saltamos la celda de acciones

  const camposNoEditables = ["family", "table", "chain", "handle"];
  const columnas = Array.from(document.querySelectorAll("table.interfaz thead th")).map(th => th.textContent);

  const exprMap = {};
  const opMap = {};

  (rule.expr || []).forEach(expr => {
    const tipo = Object.keys(expr)[0];
    const contenido = expr[tipo];

    if (tipo === "match") {
      const left = contenido.left;
      let campo = "";

      if (left.meta?.key) campo = `meta.${left.meta.key}`;
      if (left.payload) {
        const proto = left.payload.protocol;
        const field = left.payload.field;
        if (proto === "tcp" && field === "sport") campo = "sport";
        else if (proto === "tcp" && field === "dport") campo = "dport";
        else campo = `${proto}.${field}`; // ← mantiene ip.saddr, ip.daddr intactos
      }
      if (left.ct?.key) campo = `ct.${left.ct.key}`;

      exprMap[campo] = contenido.right;
      opMap[campo] = contenido.op;
    }

    if (tipo === "counter") {
      exprMap["packets"] = contenido.packets;
      exprMap["bytes"] = contenido.bytes;
    }

    if (tipo === "log") {
      exprMap["log.prefix"] = contenido.prefix;
      exprMap["log.group"] = contenido.group;
    }

    if (tipo === "snat") {
      exprMap["snat.addr"] = contenido.addr;
      exprMap["snat.port"] = contenido.port;
    }
  });

  columnas.slice(1).forEach(col => {
    const cell = cells[cellIndex];
    cell.innerHTML = "";

    if (camposNoEditables.includes(col)) {
      cell.innerHTML = `<span class="valor">${rule[col] ?? ""}</span>`;
    } else if (col === "ip.protocol") {
      const select = document.createElement("select");
      select.className = "valor-editable";

      ["tcp", "udp"].forEach(opt => {
        const option = document.createElement("option");
        option.value = opt;
        option.textContent = opt;
        if (opt === exprMap[col]) option.selected = true;
        select.appendChild(option);
      });

      cell.appendChild(select);
    } else {
      const input = document.createElement("input");
      input.type = "text";

      let valor = "";

      if (["comment", "position"].includes(col)) {
        valor = rule[col] ?? "";
      } else {
        const exprValor = exprMap[col];

        if (exprValor?.prefix) {
          valor = `${exprValor.prefix.addr}/${exprValor.prefix.len}`;
        } else if (Array.isArray(exprValor)) {
          valor = exprValor.join(", ");
        } else if (exprValor?.set) {
          valor = exprValor.set.map(item => {
            if (item.prefix) return `${item.prefix.addr}/${item.prefix.len}`;
            return item;
          }).join(", ");
        } else if (exprValor !== null && exprValor !== undefined) {
          valor = exprValor;
        }
      }

      input.value = valor;
      input.className = "valor-editable";

      if (["ip.saddr", "sport", "ip.daddr", "dport"].includes(col)) {
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.className = "negate";
        checkbox.checked = opMap[col] === "!=";

        const label = document.createElement("label");
        label.style.fontWeight = "bold";
        label.textContent = "negate";
        label.style.marginRight = "6px";

        cell.appendChild(label);
        cell.appendChild(checkbox);
        cell.appendChild(document.createTextNode(" "));
      }

      cell.appendChild(input);
    }

    cellIndex++;
  });
}









function eliminarNftablesPostrouting(index, rule, row) {
  console.log(`Eliminar regla en índice ${index}`, rule);

  const formData = new FormData();
  formData.append("chain", rule.chain);   // Cadena: POSTROUTING, POSTROUTING, input
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
    if (typeof cargarPoliciesNftablesPostrouting === "function") {
      cargarPoliciesNftablesPostrouting();
    }
  })
  .catch(error => {
    console.error("Error al eliminar la regla:", error);
    alert("Hubo un error al eliminar la regla.");
  });
}



function guardarNftablesPostrouting(index, rule, row, columnas) {
  const celdas = row.querySelectorAll("td");
  const nuevaExpr = [];

  // Primero obtenemos el protocolo seleccionado en ip.protocol
  let protocoloSeleccionado = "tcp"; // valor por defecto
  columnas.slice(1).forEach((col, i) => {
    if (col === "ip.protocol") {
      const celda = celdas[i + 1];
      const select = celda.querySelector("select.valor-editable");
      protocoloSeleccionado = select?.value.trim() || "tcp";
    }
  });

  columnas.slice(1).forEach((col, i) => {
    const celda = celdas[i + 1];
    const input = celda.querySelector("input.valor-editable");
    const select = celda.querySelector("select.valor-editable");
    const span = celda.querySelector(".valor");
    const checkbox = celda.querySelector("input.negate");

    const valor = input?.value.trim() ?? select?.value.trim() ?? span?.textContent.trim() ?? "";
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

    if (col === "snat.addr") {
      let snat = nuevaExpr.find(e => e.snat);
      if (!snat) {
        snat = { snat: {} };
        nuevaExpr.push(snat);
      }
      snat.snat.addr = valor;
      return;
    }

    if (limpio === "") return;

    const match = {
      match: {
        op: "==",
        left: {},
        right: null
      }
    };

    if (["ip.saddr", "sport", "ip.daddr", "dport"].includes(col)) {
      match.match.op = checkbox?.checked ? "!=" : "==";
    }

    if (col === "ip.protocol") {
      match.match.left.payload = { protocol: "ip", field: "protocol" };
      match.match.right = limpio;
      nuevaExpr.push(match);
      return;
    }

    if (col.startsWith("meta.")) {
      match.match.left.meta = { key: col.split(".")[1] };
      match.match.right = limpio;
    } else if (col.startsWith("ct.")) {
      match.match.left.ct = { key: col.split(".")[1] };
      const valores = limpio.split(",").map(v => v.trim()).filter(v => v !== "");
      match.match.right = { set: valores };
    } else if (col === "sport" || col === "dport") {
      match.match.left.payload = { protocol: protocoloSeleccionado, field: col };
      const valores = limpio.split(",").map(v => v.trim()).filter(v => v !== "");

      if (valores.length > 1) {
        match.match.right = {
          set: valores.map(v => {
            if (v.includes("/")) {
              const [addr, len] = v.split("/");
              return { prefix: { addr, len: parseInt(len) || 0 } };
            } else if (!isNaN(v)) {
              return parseInt(v);
            } else {
              return v;
            }
          })
        };
      } else {
        const v = valores[0];
        if (v?.includes("/")) {
          const [addr, len] = v.split("/");
          match.match.right = { prefix: { addr, len: parseInt(len) || 0 } };
        } else if (!isNaN(v)) {
          match.match.right = parseInt(v);
        } else {
          match.match.right = v;
        }
      }
    } else if (col.includes(".")) {
      const [proto, field] = col.split(".");
      match.match.left.payload = { protocol: proto, field: field };

      const valores = limpio.split(",").map(v => v.trim()).filter(v => v !== "");

      if (valores.length > 1) {
        match.match.right = {
          set: valores.map(v => {
            if (v.includes("/")) {
              const [addr, len] = v.split("/");
              return { prefix: { addr, len: parseInt(len) || 0 } };
            } else if (!isNaN(v)) {
              return parseInt(v);
            } else {
              return v;
            }
          })
        };
      } else {
        const v = valores[0];
        if (v?.includes("/")) {
          const [addr, len] = v.split("/");
          match.match.right = { prefix: { addr, len: parseInt(len) || 0 } };
        } else if (!isNaN(v)) {
          match.match.right = parseInt(v);
        } else {
          match.match.right = v;
        }
      }
    }

    nuevaExpr.push(match);
  });

  rule.expr = nuevaExpr;

  console.log("JSON generado para la regla:", JSON.stringify(rule, null, 2));

  fetch("/policies/common_policy_actions_nftables/update_policies_nftables.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      chain: "POSTROUTING",
      rule: rule
    })
  })
  .then(res => res.json())
  .then(data => {
    console.log("Respuesta del servidor:", data);
    cargarPoliciesNftablesPostrouting();
  })
  .catch(err => {
    console.error("Error al enviar la regla:", err);
  });
}
















cargarPoliciesNftablesPostrouting()


