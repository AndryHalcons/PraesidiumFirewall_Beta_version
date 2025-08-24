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


function renderCommitButtons() {
  const container = document.getElementById("commit-table");

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

  container.appendChild(compareBtn);
  container.appendChild(applyBtn);
  container.appendChild(auditBtn);

  compareBtn.addEventListener("click", () => {
    console.log("Compare Commit clicked");
  });

  auditBtn.addEventListener("click", () => {
    console.log("Config Audit clicked");
  });

  applyBtn.addEventListener("click", buttonApplyCommit);
}

renderCommitButtons();
