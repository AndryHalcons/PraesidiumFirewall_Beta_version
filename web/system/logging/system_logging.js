function systemLoggingText(key, fallback) {
  if (typeof LANG !== "undefined" && LANG[key]) {
    return LANG[key];
  }
  return fallback;
}

const SYSTEM_LOGGING_CHOICES = {
  sizes: ["10M", "25M", "50M", "100M", "250M", "500M", "1G", "2G"],
  retention: ["1day", "3day", "7day", "14day", "30day"],
  rotation: ["daily", "weekly"]
};

const SYSTEM_LOGGING_FIELDS = [
  { section: "journald", key: "system_max_use", labelKey: "system_logging_journald_system_max_use", fallback: "Journal: persistent maximum size", type: "select", choices: "sizes" },
  { section: "journald", key: "system_keep_free", labelKey: "system_logging_journald_system_keep_free", fallback: "Journal: protected free space", type: "select", choices: "sizes" },
  { section: "journald", key: "runtime_max_use", labelKey: "system_logging_journald_runtime_max_use", fallback: "Journal: runtime maximum size", type: "select", choices: "sizes" },
  { section: "journald", key: "max_retention_sec", labelKey: "system_logging_journald_max_retention_sec", fallback: "Journal: maximum retention", type: "select", choices: "retention" },
  { section: "journald", key: "compress", labelKey: "system_logging_journald_compress", fallback: "Journal: compress", type: "checkbox" },
  { section: "system_logs", key: "enabled", labelKey: "system_logging_system_logs_enabled", fallback: "Ubuntu logs: apply rotation", type: "checkbox" },
  { section: "system_logs", key: "rotation", labelKey: "system_logging_system_logs_rotation", fallback: "Ubuntu logs: frequency", type: "select", choices: "rotation" },
  { section: "system_logs", key: "rotate", labelKey: "system_logging_system_logs_rotate", fallback: "Ubuntu logs: rotations", type: "number", min: 1, max: 30 },
  { section: "system_logs", key: "maxsize", labelKey: "system_logging_system_logs_maxsize", fallback: "Ubuntu logs: maximum size", type: "select", choices: "sizes" },
  { section: "system_logs", key: "compress", labelKey: "system_logging_system_logs_compress", fallback: "Ubuntu logs: compress", type: "checkbox" },
  { section: "system_logs", key: "delaycompress", labelKey: "system_logging_system_logs_delaycompress", fallback: "Ubuntu logs: delay compression", type: "checkbox" },
  { section: "nftables_logs", key: "enabled", labelKey: "system_logging_nftables_logs_enabled", fallback: "nftables logs: enable dedicated file", type: "checkbox" },
  { section: "nftables_logs", key: "size", labelKey: "system_logging_nftables_logs_size", fallback: "nftables logs: maximum size", type: "select", choices: "sizes" },
  { section: "nftables_logs", key: "rotate", labelKey: "system_logging_nftables_logs_rotate", fallback: "nftables logs: rotations", type: "number", min: 1, max: 30 },
  { section: "nftables_logs", key: "compress", labelKey: "system_logging_nftables_logs_compress", fallback: "nftables logs: compress", type: "checkbox" },
  { section: "nftables_logs", key: "delaycompress", labelKey: "system_logging_nftables_logs_delaycompress", fallback: "nftables logs: delay compression", type: "checkbox" }
];

function setSystemLoggingStatus(message, isError = false) {
  const status = document.getElementById("system-logging-status");
  if (!status) return;
  status.textContent = message;
  status.className = isError ? "settings-status error" : "settings-status success";
}

function createSystemLoggingInput(field, value) {
  let input;
  if (field.type === "select") {
    input = document.createElement("select");
    SYSTEM_LOGGING_CHOICES[field.choices].forEach(choice => {
      const option = document.createElement("option");
      option.value = choice;
      option.textContent = choice;
      if (choice === value) option.selected = true;
      input.appendChild(option);
    });
  } else if (field.type === "checkbox") {
    input = document.createElement("input");
    input.type = "checkbox";
    input.checked = Boolean(value);
  } else {
    input = document.createElement("input");
    input.type = "number";
    input.min = field.min;
    input.max = field.max;
    input.value = value;
  }
  input.dataset.section = field.section;
  input.dataset.key = field.key;
  input.className = "modal-input";
  return input;
}

function renderSystemLoggingForm(config) {
  const form = document.getElementById("system-logging-form");
  form.innerHTML = "";
  const groups = {
    journald: systemLoggingText("system_logging_group_journald", "systemd journal"),
    system_logs: systemLoggingText("system_logging_group_system_logs", "Classic Ubuntu logs"),
    nftables_logs: systemLoggingText("system_logging_group_nftables_logs", "Praesidium nftables logs")
  };
  Object.entries(groups).forEach(([section, title]) => {
    const fieldset = document.createElement("fieldset");
    fieldset.className = "settings-fieldset";
    const legend = document.createElement("legend");
    legend.textContent = title;
    fieldset.appendChild(legend);
    SYSTEM_LOGGING_FIELDS.filter(field => field.section === section).forEach(field => {
      const wrapper = document.createElement("div");
      wrapper.className = "modal-input-group";
      const label = document.createElement("label");
      label.className = "modal-prefix";
      label.textContent = systemLoggingText(field.labelKey, field.fallback);
      wrapper.appendChild(label);
      wrapper.appendChild(createSystemLoggingInput(field, config[field.section][field.key]));
      fieldset.appendChild(wrapper);
    });
    form.appendChild(fieldset);
  });
  const save = document.createElement("button");
  save.type = "button";
  save.className = "save-btn";
  save.textContent = systemLoggingText("system_logging_save_candidate", "Save candidate");
  save.addEventListener("click", saveSystemLoggingConfig);
  form.appendChild(save);
}

function collectSystemLoggingConfig() {
  const config = { journald: {}, system_logs: {}, nftables_logs: {} };
  document.querySelectorAll("#system-logging-form input, #system-logging-form select").forEach(input => {
    const section = input.dataset.section;
    const key = input.dataset.key;
    if (!section || !key) return;
    if (input.type === "checkbox") config[section][key] = input.checked;
    else if (input.type === "number") config[section][key] = parseInt(input.value, 10);
    else config[section][key] = input.value;
  });
  return config;
}

function loadSystemLoggingConfig() {
  fetch("/system/logging/get_system_logging.php")
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        setSystemLoggingStatus(data.error, true);
        return;
      }
      renderSystemLoggingForm(data.config);
      setSystemLoggingStatus(systemLoggingText("system_logging_loaded", "Candidate configuration loaded."));
    })
    .catch(error => setSystemLoggingStatus(`${systemLoggingText("system_logging_load_error", "Error loading configuration")}: ${error}`, true));
}

function saveSystemLoggingConfig() {
  fetch("/system/logging/update_system_logging.php", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-Token": getCsrfToken() },
    body: JSON.stringify({ config: collectSystemLoggingConfig() })
  })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        setSystemLoggingStatus(data.error, true);
        return;
      }
      renderSystemLoggingForm(data.config);
      setSystemLoggingStatus(systemLoggingText("system_logging_saved", "Configuration saved as candidate. Apply Commit to move it to running."));
    })
    .catch(error => setSystemLoggingStatus(`${systemLoggingText("system_logging_save_error", "Error saving configuration")}: ${error}`, true));
}

loadSystemLoggingConfig();
