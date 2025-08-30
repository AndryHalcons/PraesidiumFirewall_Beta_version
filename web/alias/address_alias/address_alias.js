function renderTableFromAlias(aliasName) {
  const endpoint = "/alias/common_alias_actions/get_table_structure.php";
  const param = `table=${aliasName}`;

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(data => {
      const columns = data[aliasName];
      const container = document.getElementById(`${aliasName}-table`);

      if (!container || !Array.isArray(columns)) {
        console.error("Contenedor no encontrado o datos inválidos");
        return;
      }

      container.innerHTML = ""; // Limpiar contenido previo

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = col;
        headerRow.appendChild(th);
      });

      thead.appendChild(headerRow);
      table.appendChild(thead);

      const tbody = document.createElement("tbody");
      table.appendChild(tbody);

      container.appendChild(table);
    })
    .catch(error => {
      console.error(`Error al cargar la tabla ${aliasName}:`, error);
    });
}
