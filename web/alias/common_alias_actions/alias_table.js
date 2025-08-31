function renderTableFromAlias(aliasName) {
  const endpoint = "/alias/common_alias_actions/get_table_structure.php";
  const param = `table=${aliasName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById(`${aliasName}_table`);

      if (data.error) {
        if (container) {
          container.innerHTML = `<div class="error">${data.error}</div>`;
        }
        return;
      }

      const columns = data[aliasName];
      if (!container || !Array.isArray(columns)) {
        return;
      }

      container.innerHTML = "";

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      // 👉 Insertar primero la columna de acciones
      const actionsTh = document.createElement("th");
      actionsTh.textContent = typeof LANG !== "undefined" && LANG["actions"] ? LANG["actions"] : "Acciones";
      headerRow.appendChild(actionsTh);

      // Luego las columnas normales
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

      loadTableContent(aliasName);
    })
    .catch(error => {
      const container = document.getElementById(`${aliasName}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}


function loadTableContent(aliasName) {
  const endpoint = "/alias/common_alias_actions/get_table_content.php";
  const param = aliasName;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById(`${aliasName}_table`);
      if (!container) return;

      if (data.error) {
        container.innerHTML += `<div class="error">${data.error}</div>`;
        return;
      }

      const tbody = container.querySelector("tbody");
      if (!tbody || !Array.isArray(data[aliasName])) {
        console.error("Datos inválidos o tbody no encontrado");
        return;
      }

      tbody.innerHTML = "";

      data[aliasName].forEach(row => {
        const tr = document.createElement("tr");

        // Botones de acción
        const actionsTd = document.createElement("td");

        const editBtn = document.createElement("button");
        editBtn.textContent = LANG["edit"] || "Editar";

        const saveBtn = document.createElement("button");
        saveBtn.textContent = LANG["save"] || "Guardar";
        saveBtn.style.display = "none";

        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = LANG["delete"] || "Eliminar";

        editBtn.onclick = () => handleEdit(row, tr, editBtn, saveBtn);
        saveBtn.onclick = () => handleSave(row, tr, editBtn, saveBtn, aliasName);
        deleteBtn.onclick = () => handleDelete(row, tr, aliasName);

        actionsTd.appendChild(editBtn);
        actionsTd.appendChild(saveBtn);
        actionsTd.appendChild(deleteBtn);
        tr.appendChild(actionsTd);

        // Celdas de datos
        Object.entries(row).forEach(([key, value]) => {
          const td = document.createElement("td");
          td.textContent = value;

          // Marcar campos editables
          if (key === "content") {
            td.setAttribute("data-field", "content");
          } else if (key === "name") {
            td.setAttribute("data-field", "name");
          }

          tr.appendChild(td);
        });

        tbody.appendChild(tr);
      });
    })
    .catch(error => {
      console.error(`Error al cargar contenido de ${aliasName}:`, error);
      const container = document.getElementById(`${aliasName}_table`);
      if (container) {
        container.innerHTML += `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}




function handleEdit(row, tr, editBtn, saveBtn) {
  editBtn.style.display = "none";
  saveBtn.style.display = "inline-block";

  // Editar campo 'content'
  const contentCell = tr.querySelector('[data-field="content"]');
  const currentContent = contentCell.textContent;
  contentCell.innerHTML = "";

  const contentInput = document.createElement("input");
  contentInput.type = "text";
  contentInput.value = currentContent;
  contentCell.appendChild(contentInput);

  // Editar campo 'name'
  const nameCell = Array.from(tr.children).find(td => td.textContent === row.name);
  if (nameCell) {
    nameCell.innerHTML = "";

    const nameInput = document.createElement("input");
    nameInput.type = "text";
    nameInput.value = row.name;
    nameInput.setAttribute("data-field", "name");
    nameCell.appendChild(nameInput);
  }
}


function handleSave(row, tr, editBtn, saveBtn, aliasName) {
  // Obtener nuevo valor de 'content'
  const contentCell = tr.querySelector('[data-field="content"]');
  const contentInput = contentCell.querySelector("input");
  const newContent = contentInput.value;

  // Obtener nuevo valor de 'name'
  const nameCell = tr.querySelector('[data-field="name"]');
  const nameInput = nameCell.querySelector("input");
  const newName = nameInput.value;

  // ID no editable, se toma directamente
  const idCell = tr.querySelector('td:nth-child(2)');
  const idValue = idCell.textContent;

  // Construir JSON
  const payload = {
    [aliasName]: {
      id: idValue,
      name: newName,
      content: newContent
    }
  };
  

  // Enviar al servidor
  fetch("/alias/common_alias_actions/update_alias.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.json())
    .then(result => {
      if (!result.error) {
        // Solo si no hay error, actualizamos la vista
        contentCell.innerHTML = newContent;
        nameCell.innerHTML = newName;
        saveBtn.style.display = "none";
        editBtn.style.display = "inline-block";
        loadTableContent(aliasName); // Recargar tabla si todo va bien
      } else {
        console.error("Error del servidor:", result.error);
        // Opcional: mostrar mensaje de error en la fila
        const errorMsg = document.createElement("div");
        errorMsg.className = "error";
        errorMsg.textContent = result.error;
        tr.appendChild(errorMsg);
      }
    })
    .catch(error => {
      console.error("Error al guardar:", error);
    });
}



function handleDelete(row, tr, aliasName, ) {
  const confirmDelete = confirm("¿Seguro que deseas eliminar el registro?");
  if (!confirmDelete) return;

  // Obtener el valor del ID desde la celda (igual que en handleSave)
  const idCell = tr.querySelector('td:nth-child(2)');
  const idValue = idCell.textContent;

  const payload = {
    [aliasName]: {
      id: idValue
    }
  };
  console.log("Payload a enviar:", JSON.stringify(payload, null, 2));
  fetch("/alias/common_alias_actions/delete_alias.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.json())
    .then(result => {
      if (!result.error) {
        console.log("Registro eliminado correctamente");
        loadTableContent(aliasName); // Recargar la tabla
      } else {
        console.error("Error al eliminar:", result.error);
        alert(`Error al eliminar: ${result.error}`);
      }
    })
    .catch(error => {
      console.error("Error de conexión al eliminar:", error);
      alert("Error de conexión con el servidor");
    });
}




