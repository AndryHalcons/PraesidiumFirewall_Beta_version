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
  console.log("Solicitando datos NFTables:", `${endpoint}?${param}`);

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      console.log("JSON recibido del backend:", data);
      const tbody = document.querySelector(`#${nftName}_table table tbody`);
      if (!tbody) return;

      tbody.innerHTML = ""; // limpiar contenido previo

      if (data.error) {
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = columns.length + 1; // +1 por la columna de acciones
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

      rules.forEach(rule => {
        const tr = document.createElement("tr");

        // Columna de acciones
        const actionsTd = document.createElement("td");
        const editBtn = document.createElement("button");
        editBtn.textContent = LANG["edit"] || "Editar";
        editBtn.onclick = () => Edit_nft_policy(nftName, rule);
        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = LANG["delete"] || "Eliminar";
        deleteBtn.onclick = () => Delete_nft_policy(nftName, rule);
        actionsTd.appendChild(editBtn);
        actionsTd.appendChild(deleteBtn);
        tr.appendChild(actionsTd);

        // Rellenar columnas dinámicamente
        columns.forEach(key => {
          const td = document.createElement("td");
          td.textContent = rule[key] !== undefined ? rule[key] : "";
          tr.appendChild(td);
        });

        tbody.appendChild(tr);
      });
    })
    .catch(error => {
      const tbody = document.querySelector(`#${nftName}_table table tbody`);
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="error">Error de conexión con el servidor</td></tr>`;
      }
    });
}

