

function renderCommitTable() {
  const container = document.getElementById("commit-table");

  const table = document.createElement("table");
  table.border = "1";

  for (let i = 0; i < 3; i++) {
    const row = document.createElement("tr");

    for (let j = 0; j < 2; j++) {
      const cell = document.createElement("td");

      if (i === 0 && j === 0) {
        const compareBtn = document.createElement("button");
        compareBtn.id = "compare-btn";
        compareBtn.className = "save-btn";
        compareBtn.textContent = LANG.compare_commit;

        const applyBtn = document.createElement("button");
        applyBtn.id = "apply-btn";
        applyBtn.className = "save-btn";
        applyBtn.textContent = LANG.apply_commit;

        const auditBtn = document.createElement("button");
        auditBtn.id = "audit-btn";
        auditBtn.className = "save-btn";
        auditBtn.textContent = LANG.config_audit;

        cell.appendChild(compareBtn);
        cell.appendChild(applyBtn);
        cell.appendChild(auditBtn);

        compareBtn.addEventListener("click", handleCompareCommit);


        auditBtn.addEventListener("click", () => {
          console.log("Config Audit clicked");
        });

        applyBtn.addEventListener("click", buttonApplyCommit);
      }

      row.appendChild(cell);
    }

    table.appendChild(row);
  }

  container.appendChild(table);
}


function buttonApplyCommit() {
  fetch("/commits/check_commit/commit_apply/commit_apply.php")
  //fetch("/commits/check_commit/commit_common_actions/get_user.php")
    .then(res => res.json())
    .then(data => {
      console.log("Commit generado:");
      console.log(JSON.stringify(data, null, 2));
    })
    .catch(err => {
      console.error("Error al obtener el commit:", err);
    });
}

function handleCompareCommit() {
  // Abrir ventana emergente
  const win = window.open("", "Comparador", "width=1000,height=800");

  // Crear tabla básica
  win.document.write("<html><head><title>Comparador de Configuraciones</title></head><body>");
  win.document.write("<table border='1'><tr><th>Candidate Config</th><th>Running Config</th></tr>");
  win.document.write("<tr><td id='candidateCell'>Cargando...</td><td id='runningCell'>Cargando...</td></tr>");
  win.document.write("</table></body></html>");

  // Referencias a las celdas
  const candidateCell = win.document.getElementById("candidateCell");
  const runningCell = win.document.getElementById("runningCell");

  // Fetch candidate config
  fetch("/commits/check_commit/commit_common_actions/get_praesidium_config.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "mode=candidate"
  })
    .then(res => res.ok ? res.text() : Promise.reject("Error al obtener candidate"))
    .then(data => candidateCell.innerHTML = `<pre>${data}</pre>`)
    .catch(err => candidateCell.textContent = err);

  // Fetch running config
  fetch("/commits/check_commit/commit_common_actions/get_praesidium_config.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "mode=running"
  })
    .then(res => res.ok ? res.text() : Promise.reject("Error al obtener running"))
    .then(data => runningCell.innerHTML = `<pre>${data}</pre>`)
    .catch(err => runningCell.textContent = err);
}




renderCommitTable();
