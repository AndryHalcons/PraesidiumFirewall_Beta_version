function cargarPolicies() {
  const hook = "BF_HOOK_TC_EGRESS";

  fetch("/policies/common_policy_actions/get_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `hook=${encodeURIComponent(hook)}`
  })
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById("rules-output");
      container.textContent = JSON.stringify(data, null, 2);
      mostrarTablaDesdeJSON(); // 🔥 Se dispara automáticamente
    })
    .catch(error => {
      const container = document.getElementById("rules-output");
      container.textContent = `Error: ${error.message}`;
    });
}

function mostrarTablaDesdeJSON() {
  const container = document.getElementById("rules-output");
  let data;

  try {
    data = JSON.parse(container.textContent);
  } catch (e) {
    container.textContent = "Error al parsear el JSON.";
    return;
  }

  if (!Array.isArray(data)) {
    container.textContent = "El JSON no contiene una lista de reglas.";
    return;
  }

  container.innerHTML = "";

  // 🔘 Botón "Añadir regla"
  const btnAdd = document.createElement("button");
  btnAdd.textContent = LANG.add_policy;
  btnAdd.className = "añadir-regla";
  btnAdd.style.marginBottom = "12px";
  btnAdd.onclick = () => añadirNuevaRegla(); // lógica por definir

  container.appendChild(btnAdd);

  const table = document.createElement("table");
  table.className = "interfaz";

  const matchFields = [
    "iface", "l3_proto", "l4_proto",
    "ip4_saddr", "ip4_daddr", "ip4_snet", "ip4_dnet", "ip4_proto",
    "ip6_saddr", "ip6_daddr", "ip6_snet", "ip6_dnet", "ip6_nexthdr",
    "tcp_sport", "tcp_dport", "tcp_flags",
    "udp_sport", "udp_dport",
    "icmp_type", "icmp_code",
    "icmpv6_type", "icmpv6_code",
    "probability"
  ];

  // 🔧 Crear <thead>
  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");

  const baseFields = ["actions", "id", "position", "name", "description", "action", "enabled"];
  const allFields = baseFields.concat(matchFields);

  allFields.forEach(key => {
    const th = document.createElement("th");
    th.textContent = LANG[key] ?? key;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  // 🔧 Crear <tbody>
  const tbody = document.createElement("tbody");

  data.forEach((rule, index) => {
    const row = document.createElement("tr");

    // 🔘 Botones de acción en primera columna
    const actionCell = document.createElement("td");

    const btnEditar = document.createElement("button");
    btnEditar.textContent = LANG.edit;
    btnEditar.className = "editar";
    btnEditar.onclick = () => editarFila(index, rule, row);
    
    const btnGuardar = document.createElement("button");
    btnGuardar.textContent = LANG.save;
    btnGuardar.className = "guardar";
    btnGuardar.onclick = () => guardarFila(index, rule, row);
    
    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = LANG.delete;
    btnEliminar.className = "eliminar";
    btnEliminar.onclick = () => eliminarFila(index, rule, row);

    [btnEditar, btnGuardar, btnEliminar].forEach(btn => {
      btn.style.marginRight = "4px";
      actionCell.appendChild(btn);
    });

    row.appendChild(actionCell);

    // 🔢 Datos base
    const baseValues = [
      rule.id,
      rule.position,
      rule.name,
      rule.description,
      rule.action,
      rule.enabled ? "✅" : "❌"
    ];

    baseValues.forEach(value => {
      const cell = document.createElement("td");
      cell.textContent = value ?? "";
      row.appendChild(cell);
    });

    // 🎯 Campos de match (corregido)
    matchFields.forEach((field, i) => {
      const cell = document.createElement("td");

      if (typeof rule.match === "string") {
        cell.textContent = i === 0 ? rule.match : "";
      } else {
        cell.textContent = rule.match?.[field] ?? "";
      }

      row.appendChild(cell);
    });

    tbody.appendChild(row);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}


function editarFila(index, rule, row) {
  const cells = row.querySelectorAll("td");

  // Saltar la primera celda (botones)
  let cellIndex = 1;

  // Campos base (ID, position, name, description, action, enabled)
  const baseFields = ["id", "position", "name", "description", "action", "enabled"];

  baseFields.forEach((field, i) => {
    const cell = cells[cellIndex];

    if (field === "id") {
      // ID no editable
      cell.textContent = rule.id ?? "";
    } else if (field === "enabled") {
      // Campo booleano como checkbox
      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.checked = rule.enabled ?? false;
      cell.innerHTML = "";
      cell.appendChild(checkbox);
    } else {
      // Texto editable
      const input = document.createElement("input");
      input.type = "text";
      input.value = rule[field] ?? "";
      cell.innerHTML = "";
      cell.appendChild(input);
    }

    cellIndex++;
  });

  // Campos de match
  const matchFields = [
    "iface", "l3_proto", "l4_proto",
    "ip4_saddr", "ip4_daddr", "ip4_snet", "ip4_dnet", "ip4_proto",
    "ip6_saddr", "ip6_daddr", "ip6_snet", "ip6_dnet", "ip6_nexthdr",
    "tcp_sport", "tcp_dport", "tcp_flags",
    "udp_sport", "udp_dport",
    "icmp_type", "icmp_code",
    "icmpv6_type", "icmpv6_code",
    "probability"
  ];

  matchFields.forEach(field => {
    const cell = cells[cellIndex];
    const input = document.createElement("input");
    input.type = "text";
    input.value = rule.match?.[field] ?? "";
    cell.innerHTML = "";
    cell.appendChild(input);
    cellIndex++;
  });
}


function guardarFila(index, rule, row) {
  const cells = row.querySelectorAll("td");

  // Saltar la primera celda (botones)
  let cellIndex = 1;

  // Campos base (ID, position, name, description, action, enabled)
  const baseFields = ["id", "position", "name", "description", "action", "enabled"];

  baseFields.forEach((field, i) => {
    const cell = cells[cellIndex];

    if (field === "id") {
      // ID no editable, mantener como texto
      cell.textContent = rule.id ?? "";
    } else if (field === "enabled") {
      // Obtener valor del checkbox y mostrar como ✅❌
      const checkbox = cell.querySelector("input[type='checkbox']");
      const checked = checkbox?.checked ?? false;
      cell.textContent = checked ? "✅" : "❌";
    } else {
      // Obtener valor del input y mostrar como texto
      const input = cell.querySelector("input");
      cell.textContent = input?.value ?? "";
    }

    cellIndex++;
  });

  // Campos de match
  const matchFields = [
    "iface", "l3_proto", "l4_proto",
    "ip4_saddr", "ip4_daddr", "ip4_snet", "ip4_dnet", "ip4_proto",
    "ip6_saddr", "ip6_daddr", "ip6_snet", "ip6_dnet", "ip6_nexthdr",
    "tcp_sport", "tcp_dport", "tcp_flags",
    "udp_sport", "udp_dport",
    "icmp_type", "icmp_code",
    "icmpv6_type", "icmpv6_code",
    "probability"
  ];

  matchFields.forEach(field => {
    const cell = cells[cellIndex];
    const input = cell.querySelector("input");
    cell.textContent = input?.value ?? "";
    cellIndex++;
  });
}


function eliminarFila(index, rule, row) {
  console.log("Eliminar fila", index, rule);
  // Aquí irá la lógica para eliminar
}
//  Ejecutar al cargar
cargarPolicies();
