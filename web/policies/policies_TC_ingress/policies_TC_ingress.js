function cargarPolicies() {
  const hook = "BF_HOOK_TC_INGRESS";

  // 🧩 Paso 1: ordenar las reglas
  fetch("/policies/common_policy_actions/order_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `hook=${encodeURIComponent(hook)}`
  })
  .then(() => {
    // 🧩 Paso 2: reordenar posiciones
    return fetch("/policies/common_policy_actions/reorder_positions.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `hook=${encodeURIComponent(hook)}`
    });
  })
  .then(() => {
    // 🧩 Paso 3: obtener las reglas ordenadas
    return fetch("/policies/common_policy_actions/get_policies.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `hook=${encodeURIComponent(hook)}`
    });
  })
  .then(response => response.json())
  .then(data => {
    // 🧩 Paso 4: mostrar resultados
    const container = document.getElementById("rules-output");
    container.textContent = JSON.stringify(data, null, 2);
    mostrarTablaTC_INGRESS(); // 🔥 Se dispara automáticamente
  })
  .catch(error => {
    const container = document.getElementById("rules-output");
    container.textContent = `Error: ${error.message}`;
  });
}



function mostrarTablaTC_INGRESS() {
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
  btnAdd.onclick = () => addNewTC_INGRESS();

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
    btnEditar.onclick = () => editarTC_INGRESS(index, rule, row);

    const btnGuardar = document.createElement("button");
    btnGuardar.textContent = LANG.save;
    btnGuardar.className = "guardar";
    btnGuardar.onclick = () => guardarTC_INGRESS(index, rule, row);

    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = LANG.delete;
    btnEliminar.className = "eliminar";
    btnEliminar.onclick = () => eliminarTC_INGRESS(index, rule, row);

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


function addNewTC_INGRESS() {
  const params = new URLSearchParams();
  params.append("BF_HOOK_TC_INGRESS", "BF_HOOK_TC_INGRESS");
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

function eliminarTC_INGRESS(index, rule, row) {
  if (!confirm("¿Seguro que deseas eliminar esta regla?")) return;

  const hook = "BF_HOOK_TC_INGRESS"; 
  const payload = {
    hook: hook,
    id: rule.id
  };

  fetch("/policies/common_policy_actions/del_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.text())
  .then(response => {
    if (response.includes("OK")) {
      row.remove();          // 🧹 Elimina la fila visualmente
      cargarPolicies();      // 🔄 Refresca la tabla completa
    } else {
      alert("Error al eliminar la regla: " + response);
    }
  })
  .catch(err => {
    alert("Error de red al intentar eliminar la regla.");
  });
}

function editarTC_INGRESS(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1;

  const baseFields = ["id", "position", "name", "description", "action", "enabled"];
  /*
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
  */
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
		
		} else if (field === "action") {
			fetch("/policies/common_policy_forms/get_form_interface_bpfilter.php")
			.then(response => response.json())
			.then(formOptions => {
				const actions = formOptions.action ?? [];
				const select = document.createElement("select");
				actions.forEach(actionValue => {
				const option = document.createElement("option");
				option.value = actionValue;
				option.textContent = actionValue;
				if (rule.action === actionValue) {
					option.selected = true;
				}
				select.appendChild(option);
				});
				cell.innerHTML = "";
				cell.appendChild(select);
			})
			.catch(error => {
				console.error("Error loading action options:", error);
				cell.textContent = "Error loading actions";
			});
		
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

    if (field === "iface") {
      const select = document.createElement("select");
      cell.innerHTML = "";
      cell.appendChild(select);

      fetch("/policies/common_policy_forms/get_physical_interfaces.php")
        .then(response => response.json())
        .then(data => {
          const interfaces = data.physical_interfaces ?? [];
          interfaces.forEach(iface => {
            const option = document.createElement("option");
            option.value = iface.name;
            option.textContent = iface.name;
            if (rule.match?.iface === iface.name) {
              option.selected = true;
            }
            select.appendChild(option);
          });
        })
        .catch(error => {
          console.error("Error loading interfaces:", error);
          const fallback = document.createElement("option");
          fallback.textContent = "Error loading interfaces";
          select.appendChild(fallback);
        });

    } else {
      const input = document.createElement("input");
      input.type = "text";
      input.value = rule.match?.[field] ?? "";
      cell.innerHTML = "";
      cell.appendChild(input);
    }

    cellIndex++;
  });

  const fieldsWithOptions = [
    "l3_proto", "l4_proto", "ip4_proto", "ip6_nexthdr",
    "tcp_flags", "icmp_type", "icmp_code",
    "icmpv6_type", "icmpv6_code"
  ];

  fetch("/policies/common_policy_forms/get_form_interface_bpfilter.php")
    .then(response => response.json())
    .then(formOptions => {
      let cellIndex = baseFields.length + 1; // después de "actions" y baseFields

      matchFields.forEach(field => {
        if (field === "iface") {
          cellIndex++; // saltar iface, ya procesado
          return;
        }

        const cell = cells[cellIndex];
        if (!cell) {
          cellIndex++;
          return;
        }

        if (fieldsWithOptions.includes(field) && Array.isArray(formOptions[field])) {
          const select = document.createElement("select");
          cell.innerHTML = "";
          formOptions[field].forEach(optionValue => {
            const option = document.createElement("option");
            option.value = optionValue;
            option.textContent = optionValue;
            if (rule.match?.[field] == optionValue) {
              option.selected = true;
            }
            select.appendChild(option);
          });
          cell.appendChild(select);
        }

        cellIndex++;
      });
    })
    .catch(error => {
      console.error("Error loading bpfilter form options:", error);
    });
}


function guardarTC_INGRESS(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1;

  const baseFields = ["id", "position", "name", "description", "action", "enabled"];
  const updatedRule = {};

  baseFields.forEach(field => {
    const cell = cells[cellIndex];
    if (field === "enabled") {
      const checkbox = cell.querySelector("input[type='checkbox']");
      updatedRule.enabled = checkbox?.checked ?? false;
      cell.textContent = updatedRule.enabled ? "✔️" : "❌";
    } else if (field === "id") {
      updatedRule.id = cell.textContent.trim(); // Recoge el ID como texto
    } else {
      const input = cell.querySelector("input");
      updatedRule[field] = input?.value ?? "";
      cell.textContent = updatedRule[field];
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

  updatedRule.match = {};
  matchFields.forEach(field => {
    const cell = cells[cellIndex];
    const input = cell.querySelector("input");
    updatedRule.match[field] = input?.value ?? "";
    cell.textContent = updatedRule.match[field];
    cellIndex++;
  });

  const hook = "BF_HOOK_TC_INGRESS";
  const payload = {
    hook: hook,
    rule: updatedRule
  };

  fetch("/policies/common_policy_actions/update_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.text())
  .then(response => {
    if (response.includes("OK")) {
      alert("✅ Regla actualizada correctamente");
      cargarPolicies(); // 🔄 Refresca la tabla
    } else {
      alert("❌ Error al guardar la regla: " + response);
    }
  })
  .catch(err => {
    alert("⚠️ Error de red al intentar guardar la regla.");
  });
}

function guardarTC_INGRESS(index, rule, row) {
  const cells = row.querySelectorAll("td");
  let cellIndex = 1;

  const baseFields = ["id", "position", "name", "description", "action", "enabled"];
  const updatedRule = {};

  baseFields.forEach(field => {
    const cell = cells[cellIndex];

    if (field === "enabled") {
      const checkbox = cell.querySelector("input[type='checkbox']");
      updatedRule.enabled = checkbox?.checked ?? false;
    } else if (field === "id") {
      updatedRule.id = cell.textContent.trim();
    } else if (field === "action") {
      const select = cell.querySelector("select");
      updatedRule.action = select?.value ?? "";
    } else {
      const input = cell.querySelector("input");
      updatedRule[field] = input?.value ?? "";
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

  const fieldsWithOptions = [
    "l3_proto", "l4_proto", "ip4_proto", "ip6_nexthdr",
    "tcp_flags", "icmp_type", "icmp_code",
    "icmpv6_type", "icmpv6_code"
  ];

  updatedRule.match = {};
  matchFields.forEach(field => {
    const cell = cells[cellIndex];
    let value;

    if (field === "iface" || fieldsWithOptions.includes(field)) {
      const select = cell.querySelector("select");
      value = select?.value ?? "";
    } else {
      const input = cell.querySelector("input");
      value = input?.value ?? "";
    }

    updatedRule.match[field] = value;
    cellIndex++;
  });

  const hook = "BF_HOOK_TC_INGRESS";
  const payload = {
    hook: hook,
    rule: updatedRule
  };

  fetch("/policies/common_policy_actions/update_policies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.text())
  .then(response => {
    if (response.includes("OK")) {
      alert("✅ Regla actualizada correctamente");

      // 🧩 Solo ahora actualizamos visualmente la fila
      cellIndex = 1;
      baseFields.forEach(field => {
        const cell = cells[cellIndex];
        if (field === "enabled") {
          cell.textContent = updatedRule.enabled ? "✔️" : "❌";
        } else {
          cell.textContent = updatedRule[field];
        }
        cellIndex++;
      });

      matchFields.forEach(field => {
        const cell = cells[cellIndex];
        cell.textContent = updatedRule.match[field];
        cellIndex++;
      });

      cargarPolicies(); // Refresca la tabla
    } else {
      alert("❌ Error al guardar la regla: " + response);
      // La fila se queda editable, sin cambios visuales
    }
  })
  .catch(err => {
    alert("⚠️ Error de red al intentar guardar la regla.");
    // La fila se queda editable, sin cambios visuales
  });
}


//  Ejecutar al cargar
cargarPolicies();
