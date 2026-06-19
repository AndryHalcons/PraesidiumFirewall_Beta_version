function renderTableGeneric(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete) {
  const endpoint = path_get_table_structure;
  const param = `table=${currentAlias}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById(`${currentAlias}_table`);

      if (data.error) {
        if (container) {
          container.innerHTML = `<div class="error">${data.error}</div>`;
        }
        return;
      }

      const columns = data[currentAlias];
      if (!container || !Array.isArray(columns)) {
        return;
      }

      // Leer la configuración del formulario para respetar opciones comunes
      // Read form configuration to honor shared table options
      fetch(`${path_get_forms_from_table}?table=${currentAlias}`)
        .then(res => res.json())
        .then(formConfigForTable => {
          if (!formConfigForTable.disable_add) {
            // Insertar el botón "Agregar" antes del contenedor
            // Insert the shared "Add" button before the container
            const addBtn = document.createElement("button");
            addBtn.textContent = LANG["add"] || "Add";
            addBtn.onclick = () => add_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
            container.insertAdjacentElement("beforebegin", addBtn);
          }
        })
        .catch(error => console.error("Error al cargar opciones de formulario:", error));

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
      loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
    })
    .catch(error => {
      const container = document.getElementById(`${currentAlias}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}


function loadTableContentGeneric(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete, columns) {
  const endpoint = path_get_table_content; 
  const param = `table=${currentAlias}`;
  console.log("📤 Enviando al backend:", `${endpoint}?${param}`);
  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const tbody = document.querySelector(`#${currentAlias}_table table tbody`);
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

      const rules = data[currentAlias];
      if (!Array.isArray(rules) || rules.length === 0) {
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = columns.length + 1;
        td.textContent = LANG["no_data"] || "No hay datos";
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
      }

      const formEndpoint = path_get_forms_from_table;
      fetch(`${formEndpoint}?table=${currentAlias}`)
        .then(res => res.json())
        .then(formConfig => {
          rules.forEach(rule => {
            const tr = document.createElement("tr");

            // Columna de acciones
            const actionsTd = document.createElement("td");

            const editBtn = document.createElement("button");
            editBtn.textContent = LANG["edit"] || "Editar";
            editBtn.onclick = () => editModal_Generic(
              
              currentAlias,
              path_get_forms_from_table,
              path_get_update,
              rule,
              columns,
              () => loadTableContentGeneric(
                currentAlias,
                path_get_table_structure,
                path_get_table_content,
                path_get_forms_from_table,
                path_get_update,
                path_get_delete,
                columns
              )
            );

            actionsTd.appendChild(editBtn);

            if (!formConfig.disable_delete) {
              const deleteBtn = document.createElement("button");
              deleteBtn.textContent = LANG["delete"] || "Eliminar";
              deleteBtn.onclick = () => delete_Generic(
                currentAlias,
                path_get_table_structure,
                path_get_table_content,
                path_get_forms_from_table,
                path_get_update,
                path_get_delete,
                rule,
                columns
              );
              actionsTd.appendChild(deleteBtn);
            }

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
      const tbody = document.querySelector(`#${currentAlias}_table table tbody`);
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="error">Error de conexión con el servidor</td></tr>`;
      }
    });
}


function editModal_Generic(currentAlias, path_get_forms_from_table, path_get_update, rule, columns, onSuccess) {
  const endpoint = path_get_forms_from_table;
  const param = `table=${currentAlias}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      const modal = document.createElement("div");
      modal.className = "modal-overlay";

      const modalContent = document.createElement("div");
      modalContent.className = "modal-window";

      const title = document.createElement("h3");
      title.textContent = `Editar política de ${currentAlias}`;
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
          span.textContent = rule[key] || "Auto";
          span.className = "modal-input";
          fieldWrapper.appendChild(span);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.select?.[key]) {
          const select = document.createElement("select");
          select.className = "modal-input";
          formConfig.select[key].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt === "" ? " --- " : opt;
            if (opt === rule[key]) option.selected = true;
            select.appendChild(option);
          });
          fieldWrapper.appendChild(select);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.checkbox?.[key]) {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.checked = rule[key] === formConfig.checkbox[key].checked;
          fieldWrapper.appendChild(checkbox);
          form.appendChild(fieldWrapper);
          return;
        }

        const input = document.createElement("input");
        input.type = "text";
        input.name = key;
        input.value = rule[key] || "";
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

          if (formConfig.not_editable?.[key]) {
            const span = fieldWrapper.querySelector("span");
            updatedRule[key] = span ? span.textContent : rule[key];
            return;
          }

          const el = fieldWrapper.querySelector("select") ||
                     fieldWrapper.querySelector("input[type='checkbox']") ||
                     fieldWrapper.querySelector("input[type='text']");

          let value = "";

          if (el && el.tagName === "SELECT") {
            value = el.value;
          } else if (el && el.type === "checkbox") {
            value = el.checked
              ? formConfig.checkbox[key].checked
              : formConfig.checkbox[key].unchecked;
          } else if (el && el.tagName === "INPUT") {
            value = el.value;
          }

          updatedRule[key] = value;
        });

        send_Generic(currentAlias, path_get_update, updatedRule, columns, () => {
          document.body.removeChild(modal);
          if (typeof onSuccess === "function") onSuccess();
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



function add_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table,path_get_update,path_get_delete, columns) {
  const endpoint = path_get_forms_from_table;
  const param = `table=${currentAlias}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      const modal = document.createElement("div");
      modal.className = "modal-overlay";

      const modalContent = document.createElement("div");
      modalContent.className = "modal-window";

      const title = document.createElement("h3");
      title.textContent = `Agregar política a ${currentAlias}`;
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

        send_Generic(currentAlias, path_get_update, updatedRule, columns, () => {
          document.body.removeChild(modal);
          loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
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

function send_Generic(currentAlias, path_get_update, updatedRule, columns, onSuccess) {
  const endpoint = path_get_update;
  const payload = {
    table: currentAlias,
    rule: updatedRule
  };

  console.log("Enviando al backend:", JSON.stringify(payload, null, 2));

  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": getCsrfToken()
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
          alert(JSON.stringify(result, null, 2)); // ✅ Ventana emergente con el JSON completo
        } else {
          if (typeof onSuccess === "function") {
            onSuccess();
          }
        }
      } catch (e) {
        console.error("❌ No se pudo parsear JSON:", e);
        alert("Error al parsear la respuesta del servidor:\n\n" + text); // ✅ Si no se puede parsear
      }
    })
    .catch(error => {
      console.error("Error de conexión al guardar:", error);
      alert("Error de conexión al guardar:\n\n" + error); // ✅ Si falla la conexión
    });
}

function delete_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, rule, columns) {
  if (!confirm("¿Estás seguro de que quieres eliminar esta entrada?")) {
    return; // El usuario canceló
  }

  const endpoint = path_get_delete;

  const payload = {
    table: currentAlias,
    id: rule.id,
    name: rule.name,
    file: rule.file,
    fileName : rule.file_name

  };
  console.log("📤 Enviando al backend:", JSON.stringify(payload, null, 2));
  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": getCsrfToken()
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
      } else {
        alert(result.error || "Error al eliminar la política");
      }
    })
    .catch(error => {
      console.error("Error al eliminar la política:", error);
      alert("Error de conexión con el servidor");
    });
}




    
