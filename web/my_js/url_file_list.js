// Función para generar el botón de subida y enviar el archivo con el alias
// Function to generate upload button and send file with alias
function upload_files_url(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete) {
    // Crear input de tipo file
    // Create file input
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.txt';
    fileInput.name = 'domain_file';

    // Crear botón de envío
    // Create submit button
    const submitBtn = document.createElement('button');
    submitBtn.textContent = 'Subir archivo';
    submitBtn.onclick = function () {
        const file = fileInput.files[0];
        if (!file) {
            alert('Selecciona un archivo .txt');
            return;
        }

        const formData = new FormData();
        formData.append('domain_file', file);
        formData.append('alias', currentAlias);

        fetch('/common_functions/upload_files.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json()) // Procesar respuesta como JSON
        .then(data => {
            // Mostrar mensaje del backend
            // Show backend message
            alert("El backend dice: " + data.message);
            fileInput.value = ''; // Limpiar el input de archivo
            renderTable_url_list(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete)
        })
        .catch(() => {
            // Mostrar error genérico
            // Show generic error
            alert('Error al subir el archivo');
            fileInput.value = ''; // Limpiar el input de archivo
        });
    };

    // Insertar elementos en el DOM
    // Insert elements into the DOM
    const container = document.getElementById('upload_container');
    container.innerHTML = ''; // Limpiar contenido anterior
    container.appendChild(fileInput);
    container.appendChild(submitBtn);
}
function renderTable_url_list(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete) {
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
      loadTableContent_url_list(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
    })
    .catch(error => {
      const container = document.getElementById(`${currentAlias}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}
function loadTableContent_url_list(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns) {
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


            editBtn.onclick = () => edit_Generic_url_list(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, rule, columns, tr, editBtn);
            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = LANG["delete"] || "Eliminar";
            deleteBtn.onclick = () => delete_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, rule, columns);

            actionsTd.appendChild(editBtn);
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
      const tbody = document.querySelector(`#${currentAlias}_table table tbody`);
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="error">Error de conexión con el servidor</td></tr>`;
      }
    });
}
function edit_Generic_url_list(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete, rule, columns, targetRow, editBtn) {
  const param = `table=${currentAlias}`;
  const fileName = rule["file"];
  if (!fileName) {
    alert("No se encontró el nombre del archivo en la fila.");
    return;
  }

  // Crear modal con clases correctas
  const modal = document.createElement("div");
  modal.className = "modal-overlay";

  const modalContent = document.createElement("div");
  modalContent.className = "modal-window";

  const title = document.createElement("h3");
  title.textContent = `Editando: ${fileName}`;
  modalContent.appendChild(title);

  const textarea = document.createElement("textarea");
  textarea.rows = 20;
  textarea.cols = 80;
  textarea.className = "modal-input";
  textarea.placeholder = "Cargando contenido...";
  modalContent.appendChild(textarea);

  const actions = document.createElement("div");
  actions.className = "modal-actions";

  const saveBtn = document.createElement("button");
  saveBtn.type = "button";
  saveBtn.textContent = "Guardar";
  saveBtn.className = "modal-button";

  const cancelBtn = document.createElement("button");
  cancelBtn.type = "button";
  cancelBtn.textContent = "Cancelar";
  cancelBtn.className = "modal-button cancel";

  actions.appendChild(saveBtn);
  actions.appendChild(cancelBtn);
  modalContent.appendChild(actions);
  modal.appendChild(modalContent);
  document.body.appendChild(modal);

  // Cargar contenido del archivo
  fetch(`/url_filter/url_filter_table/get_file_data.php?${param}&file=${encodeURIComponent(fileName)}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        textarea.value = `Error: ${data.error}`;
        textarea.disabled = true;
        saveBtn.disabled = true;
      } else {
        textarea.value = data.content || "";
      }
    })
    .catch(() => {
      textarea.value = "Error al cargar el archivo.";
      textarea.disabled = true;
      saveBtn.disabled = true;
    });

  // Acción guardar
  saveBtn.onclick = () => {
    const updatedContent = textarea.value;
    const formData = new FormData();
    formData.append("file", fileName);
    formData.append("table", currentAlias);
    formData.append("content", updatedContent);

    fetch("/url_filter/url_filter_table/get_save_file_data.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (!data.error) {
        alert(data.message || "Archivo guardado.");
        document.body.removeChild(modal);
        loadTableContent_url_list(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete, columns);
      } else {
        alert(data.error);
      }
    })
    .catch(() => {
      alert("Error al guardar el archivo.");
    });
  };

  // Acción cancelar
  cancelBtn.onclick = () => {
    document.body.removeChild(modal);
  };
}




