function renderCommitButtons() {
  const container = document.getElementById("commit-table");

  // Botón: Compare Commit
  const compareBtn = document.createElement("button");
  compareBtn.id = "compare-btn";
  compareBtn.className = "save-btn";
  compareBtn.textContent = LANG.compare_commit;

  // Botón: Apply Commit
  const applyBtn = document.createElement("button");
  applyBtn.id = "apply-btn";
  applyBtn.className = "save-btn";
  applyBtn.textContent = LANG.apply_commit;

  // Botón: Config Audit
  const auditBtn = document.createElement("button");
  auditBtn.id = "audit-btn";
  auditBtn.className = "save-btn";
  auditBtn.textContent = LANG.config_audit;

  // Añadir botones al contenedor
  container.appendChild(compareBtn);
  container.appendChild(applyBtn);
  container.appendChild(auditBtn);

  // Eventos
  compareBtn.addEventListener("click", () => {
    console.log("Compare Commit clicked");
  });

  applyBtn.addEventListener("click", () => {
    console.log("Apply Commit clicked");
  });

  auditBtn.addEventListener("click", () => {
    console.log("Config Audit clicked");
  });
}

renderCommitButtons();
