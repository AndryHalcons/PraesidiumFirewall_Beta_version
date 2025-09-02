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
      addBtn.onclick = () => Add_nft_policy(nftName);
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

  // Obtener configuración del formulario para aplicar lógica de checkbox
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
          td.innerHTML = value;
        } else if (el && el.type === "checkbox") {
          el.disabled = false;
          if (formConfig.checkbox?.[key]) {
            value = el.checked
              ? formConfig.checkbox[key].checked
              : formConfig.checkbox[key].unchecked;
          } else {
            value = el.checked ? "==" : "!="; // fallback
          }
          td.innerHTML = value;
        } else if (el && el.tagName === "INPUT") {
          el.disabled = false;
          value = el.value;
          td.innerHTML = value;
        }

        updatedRule[key] = value;
      });

      //  Mostrar el JSON que se va a enviar
      const payload = {
        table: nftName,
        rule: updatedRule
      };
      console.log("Enviando al backend:", JSON.stringify(payload, null, 2));

      // Enviar datos al backend
      fetch("/policies/common_policy_actions_nft/get_update_policy.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      })
        .then(response => response.text()) // ← leer como texto para ver la respuesta cruda
        .then(text => {
          console.log("🧾 Respuesta cruda del backend:", text);
          try {
            const result = JSON.parse(text); // ← intentar parsear manualmente
            console.log("✅ JSON parseado:", result);
            if (result.error) {
              console.error("Error al guardar en el backend:", result.error);
            } else {
              saveBtn.style.display = "none";
              editBtn.style.display = "inline-block";
              loadTableContentNftables(nftName, columns);
            }
          } catch (e) {
            console.error("❌ No se pudo parsear JSON:", e);
          }
        })
        .catch(error => {
          console.error("Error de conexión al guardar:", error);
        });
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}





