

function renderCommitControls() {
  const container = document.getElementById("commit-table");

  const wrapper = document.createElement("div");
  wrapper.className = "commit-controls";

  const buttons = [
    { id: "btn-compare-commit", text: LANG.compare_commit, handler: handleCompareCommit },
    { id: "btn-apply-commit", text: LANG.apply_commit, handler: buttonApplyCommit },
    { id: "btn-audit-commit", text: LANG.config_audit, handler: () => console.log("Config Audit clicked") }
  ];

  buttons.forEach(({ id, text, handler }) => {
    const btn = document.createElement("button");
    btn.id = id;
    btn.className = "save-btn";
    btn.textContent = text;
    btn.addEventListener("click", handler);
    wrapper.appendChild(btn);
  });

  container.appendChild(wrapper);
}



function buttonApplyCommit() {
  const container = document.getElementById("commit-table");

  // Crear spinner con flechas grandes y texto
  const spinner = document.createElement("div");
  spinner.id = "commit-spinner";
  spinner.innerHTML = `
    <span class="spinner-icon">🔄</span>
    <span class="spinner-text">Aplicando commit</span>
  `;
  container.appendChild(spinner);

  fetch("/commits/check_commit/commit_apply/commit_apply.php", {
    method: "POST",
    headers: {
      "X-CSRF-Token": getCsrfToken()
    }
  })
    .then(res => res.json())
    .then(data => {
      // quitar spinner
      spinner.remove(); 

      const pre = document.createElement("pre");
      pre.textContent = JSON.stringify(data, null, 2);
      container.appendChild(pre);
    })
    .catch(err => {
      spinner.remove();
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




renderCommitControls();
