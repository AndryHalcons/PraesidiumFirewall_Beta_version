// Función para generar el botón de subida y enviar el archivo con el alias
// Function to generate upload button and send file with alias
function upload_certs(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete) {
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
            headers: {
                "X-CSRF-Token": getCsrfToken()
            },
            body: formData
        })
        .then(res => res.json()) // Procesar respuesta como JSON
        .then(data => {
            // Mostrar mensaje del backend
            // Show backend message
            alert("El backend dice: " + data.message);
            fileInput.value = ''; // Limpiar el input de archivo
            renderTable_certs(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete)
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
function renderTable_certs(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, path_download_certificates) {
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
      loadTableContent_certs(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete,path_download_certificates, columns);
    })
    .catch(error => {
      const container = document.getElementById(`${currentAlias}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}
function loadTableContent_certs(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete,path_download_certificates, columns) {
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

            // Botón eliminar
            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = LANG["delete"] || "delete";
            deleteBtn.onclick = () => delete_certificate(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete, rule, columns);
            actionsTd.appendChild(deleteBtn);

            // Botón descargar
            const downloadBtn = document.createElement("button");
            downloadBtn.textContent = LANG["download"] || "Download";
            downloadBtn.onclick = () => download_certificate(path_download_certificates, rule.file_name, rule.name, currentAlias);
            actionsTd.appendChild(downloadBtn);

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
function delete_certificate(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, rule, columns) {
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
        loadTableContent_certs(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
      } else {
        alert(result.error || "Error al eliminar la política");
      }
    })
    .catch(error => {
      console.error("Error al eliminar la política:", error);
      alert("Error de conexión con el servidor");
    });
}
function download_certificate(path_download_certificates, fileName, name, currentAlias) {
  // Validar que se recibieron los parámetros necesarios
  // Validate that required parameters were received
  if (!fileName || !name || !currentAlias) {
    alert("Datos insuficientes para descargar el archivo");
    // Insufficient data to download the file
    return;
  }

  // Definir la URL del backend
  // Define the backend URL
  const url = path_download_certificates;
  // Construir el cuerpo de la solicitud
  // Build the request payload
  const payload = {
    table: currentAlias,
    fileName: fileName,
    name: name
  };

  console.log("📤 Enviando payload de descarga:", JSON.stringify(payload, null, 2));
  // Sending download payload to backend

  // Realizar la solicitud POST al backend
  // Make the POST request to the backend
  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": getCsrfToken()
    },
    body: JSON.stringify(payload)
  })
    .then(response => {
      if (!response.ok) throw new Error("Error al descargar el archivo");
      // Throw error if response is not OK
      return response.blob();
      // Convert response to binary blob
    })
    .then(blob => {
      // Crear enlace temporal para descargar el archivo
      // Create temporary link to download the file
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    })
    .catch(error => {
      console.error("❌ Error en la descarga:", error);
      // Error during download
      alert("No se pudo descargar el archivo");
      // Could not download the file
    });
}


