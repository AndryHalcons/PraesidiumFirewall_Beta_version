function renderTableFromNftables(nftName) {
  const endpoint = "/policies/common_policy_actions_nft/get_table_structure.php";
  const param = `table=${nftName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById(`${nftName}_table`);

      if (data.error) {
        if (container) {
          container.innerHTML = `<div class="error">${data.error}</div>`;
        }
        return;
      }

      const columns = data[nftName];
      if (!container || !Array.isArray(columns)) {
        return;
      }

      // Insertar el botón "Agregar política" antes del contenedor
      const addBtn = document.createElement("button");
      addBtn.textContent = LANG["add_policy"] || "Agregar política";
      addBtn.onclick = () => add_nft_policy(nftName, columns);
      container.insertAdjacentElement("beforebegin", addBtn);

      container.innerHTML = "";

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      // Columna de acciones
      const actionsTh = document.createElement("th");
      actionsTh.textContent = typeof LANG !== "undefined" && LANG["actions"] ? LANG["actions"] : "Acciones";
      headerRow.appendChild(actionsTh);

      // Columnas normales
      columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = typeof LANG !== "undefined" && LANG[col] ? LANG[col] : col;
        headerRow.appendChild(th);
      });

      thead.appendChild(headerRow);
      table.appendChild(thead);

      const tbody = document.createElement("tbody");
      table.appendChild(tbody);

      container.appendChild(table);

      // Cargar contenido de la tabla
      loadTableContentNftables(nftName, columns);
    })
    .catch(error => {
      const container = document.getElementById(`${nftName}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}


function loadTableContentNftables(nftName, columns) {
  const endpoint = "/policies/common_policy_actions_nft/get_table_content.php"; 
  const param = `table=${nftName}`;
  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const tbody = document.querySelector(`#${nftName}_table table tbody`);
      if (!tbody) return;

      tbody.innerHTML = "";

      if (data.error) {
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = columns.length + 1;
        td.className = "error";
        td.textContent = data.error;
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
      }

      const rules = data[nftName];
      if (!Array.isArray(rules) || rules.length === 0) {
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = columns.length + 1;
        td.textContent = LANG["no_data"] || "No hay datos";
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
      }

      const formEndpoint = "/policies/common_policy_actions_nft/get_forms_from_table.php";
      fetch(`${formEndpoint}?table=${nftName}`)
        .then(res => res.json())
        .then(formConfig => {
          rules.forEach(rule => {
            const tr = document.createElement("tr");

            // Columna de acciones
            const actionsTd = document.createElement("td");

            const editBtn = document.createElement("button");
            editBtn.textContent = LANG["edit"] || "Editar";

            const saveBtn = document.createElement("button");
            saveBtn.textContent = LANG["save"] || "Guardar";
            saveBtn.style.display = "none";

            editBtn.onclick = () => edit_nft_policy(nftName, rule, columns, tr, editBtn, saveBtn);
            saveBtn.onclick = () => save_nft_policy(nftName, rule, columns, tr, editBtn, saveBtn);

            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = LANG["delete"] || "Eliminar";
            deleteBtn.onclick = () => delete_nft_policy(nftName, rule);

            actionsTd.appendChild(editBtn);
            actionsTd.appendChild(saveBtn);
            actionsTd.appendChild(deleteBtn);
            tr.appendChild(actionsTd);

            // Rellenar columnas con inputs visuales
            columns.forEach(key => {
              const td = document.createElement("td");
              const value = rule[key] !== undefined ? rule[key] : "";

              if (formConfig.select?.[key]) {
                const select = document.createElement("select");
                select.disabled = true;
                formConfig.select[key].forEach(opt => {
                  const option = document.createElement("option");
                  option.value = opt;
                  option.textContent = opt;
                  if (opt === value) option.selected = true;
                  select.appendChild(option);
                });
                td.appendChild(select);
              } else if (formConfig.checkbox?.[key]) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.disabled = true;
                checkbox.checked = value === formConfig.checkbox[key].checked;
                td.appendChild(checkbox);
              } else {
                td.textContent = value;
              }

              tr.appendChild(td);
            });

            tbody.appendChild(tr);
          });
        })
        .catch(err => {
          console.error("Error al cargar configuración visual:", err);
        });
    })
    .catch(error => {
      const tbody = document.querySelector(`#${nftName}_table table tbody`);
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="error">Error de conexión con el servidor</td></tr>`;
      }
    });
}



function edit_nft_policy(nftName, rule, columns, targetRow, editBtn, saveBtn) {
  const endpoint = "/policies/common_policy_actions_nft/get_forms_from_table.php";
  const param = `table=${nftName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      editBtn.style.display = "none";
      saveBtn.style.display = "inline-block";

      const cells = targetRow.querySelectorAll("td");

      columns.forEach((key, i) => {
        const td = cells[i + 1];
        td.innerHTML = "";

        if (Object.keys(formConfig.not_editable).includes(key)) {
          td.textContent = rule[key] || "";
          return;
        }


        if (formConfig.select[key]) {
          const select = document.createElement("select");
          formConfig.select[key].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt;
            if (opt === rule[key]) option.selected = true;
            select.appendChild(option);
          });
          td.appendChild(select);
          return;
        }

        if (formConfig.checkbox[key]) {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.checked = rule[key] === formConfig.checkbox[key].checked;
          td.appendChild(checkbox);
          return;
        }

        const input = document.createElement("input");
        input.type = "text";
        input.value = rule[key] || "";
        td.appendChild(input);
      });
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}


function save_nft_policy(nftName, rule, columns, targetRow, editBtn, saveBtn) {
  const cells = targetRow.querySelectorAll("td");
  const updatedRule = {};

  const endpoint = "/policies/common_policy_actions_nft/get_forms_from_table.php";
  const param = `table=${nftName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      columns.forEach((key, i) => {
        const td = cells[i + 1];
        const el = td.firstChild;

        let value = rule[key];

        if (el && el.tagName === "SELECT") {
          el.disabled = false;
          value = el.value;
        } else if (el && el.type === "checkbox") {
          el.disabled = false;
          if (formConfig.checkbox?.[key]) {
            value = el.checked
              ? formConfig.checkbox[key].checked
              : formConfig.checkbox[key].unchecked;
          } else {
            value = el.checked ? "==" : "!=";
          }
        } else if (el && el.tagName === "INPUT") {
          el.disabled = false;
          value = el.value;
        }

        updatedRule[key] = value;
      });

      // Esperamos al backend antes de modificar la UI
      sendNftPolicy(nftName, updatedRule, columns, () => {
        columns.forEach((key, i) => {
          const td = cells[i + 1];
          td.innerHTML = updatedRule[key]; // Solo ahora convertimos a texto plano
        });

        saveBtn.style.display = "none";
        editBtn.style.display = "inline-block";
      });
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}




function add_nft_policy(nftName, columns) {
  const endpoint = "/policies/common_policy_actions_nft/get_forms_from_table.php";
  const param = `table=${nftName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      const modal = document.createElement("div");
      modal.className = "modal-overlay";

      const modalContent = document.createElement("div");
      modalContent.className = "modal-window";

      const title = document.createElement("h3");
      title.textContent = `Agregar política a ${nftName}`;
      modalContent.appendChild(title);

      const form = document.createElement("form");

      columns.forEach(key => {
        const fieldWrapper = document.createElement("div");
        fieldWrapper.className = "modal-input-group";

        const label = document.createElement("label");
        label.textContent = typeof LANG !== "undefined" && LANG[key] ? LANG[key] : key;
        label.className = "modal-prefix";
        fieldWrapper.appendChild(label);

        if (formConfig.not_editable && Object.keys(formConfig.not_editable).includes(key)) {
          const span = document.createElement("span");
          span.textContent = "Auto";
          span.className = "modal-input";
          fieldWrapper.appendChild(span);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.select && Array.isArray(formConfig.select[key])) {
          const select = document.createElement("select");
          select.className = "modal-input";
          formConfig.select[key].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt === "" ? " --- " : opt;
            select.appendChild(option);
          });
          fieldWrapper.appendChild(select);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.checkbox && typeof formConfig.checkbox[key] === "object") {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.checked = false;
          fieldWrapper.appendChild(checkbox);
          form.appendChild(fieldWrapper);
          return;
        }

        const input = document.createElement("input");
        input.type = "text";
        input.name = key;
        input.value = "";
        input.className = "modal-input";
        fieldWrapper.appendChild(input);
        form.appendChild(fieldWrapper);
      });

      const actions = document.createElement("div");
      actions.className = "modal-actions";

      const saveBtn = document.createElement("button");
      saveBtn.type = "button";
      saveBtn.textContent = "Guardar";
      saveBtn.className = "modal-button";
      saveBtn.onclick = () => {
        const updatedRule = {};

        columns.forEach(key => {
          const fieldWrapper = form.querySelectorAll(".modal-input-group")[columns.indexOf(key)];
          const el = fieldWrapper.querySelector("select") || fieldWrapper.querySelector("input");

          let value = "";

          if (formConfig.not_editable && Object.keys(formConfig.not_editable).includes(key)) {
            return;
          }

          if (el && el.tagName === "SELECT") {
            value = el.value;
          } else if (el && el.type === "checkbox") {
            if (formConfig.checkbox?.[key]) {
              value = el.checked
                ? formConfig.checkbox[key].checked
                : formConfig.checkbox[key].unchecked;
            } else {
              value = el.checked ? "==" : "!=";
            }
          } else if (el && el.tagName === "INPUT") {
            value = el.value;
          }

          updatedRule[key] = value;
        });

        sendNftPolicy(nftName, updatedRule, columns, () => {
          document.body.removeChild(modal);
          loadTableContentNftables(nftName, columns);
        });
      };
      actions.appendChild(saveBtn);

      const closeBtn = document.createElement("button");
      closeBtn.type = "button";
      closeBtn.textContent = "Cancelar";
      closeBtn.className = "modal-button cancel";
      closeBtn.onclick = () => document.body.removeChild(modal);
      actions.appendChild(closeBtn);

      form.appendChild(actions);
      modalContent.appendChild(form);
      modal.appendChild(modalContent);
      document.body.appendChild(modal);
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}



function sendNftPolicy(nftName, updatedRule, columns, onSuccess) {
  const payload = {
    table: nftName,
    rule: updatedRule
  };

  console.log("Enviando al backend:", JSON.stringify(payload, null, 2));

  fetch("/policies/common_policy_actions_nft/get_update_policy.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.text())
    .then(text => {
      console.log("🧾 Respuesta cruda del backend:", text);
      try {
        const result = JSON.parse(text);
        console.log("✅ JSON parseado:", result);

        if (result.error) {
          console.error("❌ Error al guardar en el backend:", result.error);
          alert(JSON.stringify(result, null, 2)); // 👈 Aquí está el puto alert con el JSON completo
        } else {
          if (typeof onSuccess === "function") {
            onSuccess();
          }
          loadTableContentNftables(nftName, columns);
        }
      } catch (e) {
        console.error("❌ No se pudo parsear JSON:", e);
        alert("Error al parsear la respuesta del servidor:\n\n" + text); // 👈 También mostramos el texto crudo si no se puede parsear
      }
    })
    .catch(error => {
      console.error("Error de conexión al guardar:", error);
      alert("Error de conexión al guardar:\n\n" + error); // 👈 Y si falla la conexión, también lo mostramos
    });
}





function delete_nft_policy(nftName, rule) {
  if (!confirm("¿Estás seguro de que quieres eliminar esta política?")) {
    return; // El usuario canceló
  }

  const endpoint = "/policies/common_policy_actions_nft/get_delete_policy.php";

  const payload = {
    table: nftName,
    id: rule.id
  };

  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        loadTableContentNftables(nftName, Object.keys(rule));
      } else {
        alert(result.error || "Error al eliminar la política");
      }
    })
    .catch(error => {
      console.error("Error al eliminar la política:", error);
      alert("Error de conexión con el servidor");
    });
}



