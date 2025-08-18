function cargarPolicies() {
  const hook = "BF_HOOK_XDP";

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
      mostrarTablaXDP(); // 🔥 Se dispara automáticamente
    })
    .catch(error => {
      const container = document.getElementById("rules-output");
      container.textContent = `Error: ${error.message}`;
    });
}

function mostrarTablaXDP() {
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

  const btnAdd = document.createElement("button");
  btnAdd.textContent = LANG.add_policy;
  btnAdd.className = "añadir-regla";
  btnAdd.style.marginBottom = "12px";
  btnAdd.onclick = () => addNewXDP();

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

  const tbody = document.createElement("tbody");

  data.forEach((rule, index) => {
    const row = document.createElement("tr");

    const actionCell = document.createElement("td");

    const btnEditar = document.createElement("button");
    btnEditar.textContent = LANG.edit;
    btnEditar.className = "editar";
    btnEditar.onclick = () => editarXDP(index, rule, row);

    const btnGuardar = document.createElement("button");
    btnGuardar.textContent = LANG.save;
    btnGuardar.className = "guardar";
    btnGuardar.onclick = () => guardarXDP(index, rule, row);

    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = LANG.delete;
    btnEliminar.className = "eliminar";
    btnEliminar.onclick = () => eliminarXDP(index, rule, row);

    [btnEditar, btnGuardar, btnEliminar].forEach(btn => {
      btn.style.marginRight = "4px";
      actionCell.appendChild(btn);
    });

    row.appendChild(actionCell);

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

function editarXDP(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1;

  const baseFields = ["id", "position", "name", "description", "action", "enabled"];

  baseFields.forEach(field => {
    const cell = cells[cellIndex];
    if (field === "id") {
      cell.textContent = rule.id ?? "";
    } else if (field === "enabled") {
      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.checked = rule.enabled ?? false;
      cell.innerHTML = "";
      cell.appendChild(checkbox);
    } else {
      const input = document.createElement("input");
      input.type = "text";
      input.value = rule[field] ?? "";
      cell.innerHTML = "";
      cell.appendChild(input);
    }
    cellIndex++;
  });

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

function guardarXDP(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1;

  const baseFields = ["id", "position", "name", "description", "action", "enabled"];

  baseFields.forEach(field => {
    const cell = cells[cellIndex];
    if (field === "id") {
      cell.textContent = rule.id ?? "";
    } else if (field === "enabled") {
      const checkbox = cell.querySelector("input[type='checkbox']");
      const checked = checkbox?.checked ?? false;
      cell.textContent = checked ? "✅" : "❌";
    } else {
      const input = cell.querySelector("input");
      cell.textContent = input?.value ?? "";
    }
    cellIndex++;
  });

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




function addNewXDP() {
  const params = new URLSearchParams();
  params.append("BF_HOOK_XDP", "BF_HOOK_XDP");
  params.append("new", "true");

  fetch("/policies/common_policy_actions/add_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: params.toString()
  })
  .then(res => res.ok ? res.text() : Promise.reject(res))
  .then(data => {
    console.log("✅ Regla añadida:", data);
    alert("Regla añadida correctamente.");
    cargarPolicies();
  })
  .catch(err => {
    console.error("❌ Error al añadir la regla:", err);
    alert("Error al añadir la regla.");
  });
}

function eliminarXDP(index, rule, row) {
  if (!confirm("¿Seguro que deseas eliminar esta regla?")) return;

  const hook = "BF_HOOK_XDP"; 
  const payload = {
    hook: hook,
    id: rule.id
  };

  //console.log("📤 Enviando a del_policies.php:", payload);

  fetch("/policies/common_policy_actions/del_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.text())
  .then(response => {
    //console.log("📥 Respuesta del servidor:", response);
    if (response.includes("OK")) {
      row.remove();
    } else {
      alert("Error al eliminar la regla: " + response);
    }
  })
  .catch(err => {
    //console.error("Error al eliminar la regla:", err);
    alert("Error de red al intentar eliminar la regla.");
  });
}


//  Ejecutar al cargar
cargarPolicies();
