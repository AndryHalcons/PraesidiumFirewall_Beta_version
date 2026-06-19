(() => {
  let systemLoggingStructure = null;
  let systemLoggingForms = null;
  let systemLoggingConfig = null;

  function systemLoggingText(key, fallback) {
    if (typeof window.LANG !== "undefined" && window.LANG[key]) {
      return window.LANG[key];
    }
    return fallback;
  }

  function getCsrfTokenSafe() {
    if (typeof getCsrfToken === "function") {
      return getCsrfToken();
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  }

  function setSystemLoggingStatus(message, isError = false) {
    const status = document.getElementById("system-logging-status");
    if (!status) return;
    status.textContent = message;
    status.className = isError ? "settings-status error" : "settings-status success";
  }

  function splitSystemLoggingFieldId(fieldId) {
    const parts = String(fieldId).split(".");
    return { section: parts[0], key: parts.slice(1).join(".") };
  }

  function getSystemLoggingFieldValue(config, fieldId) {
    const { section, key } = splitSystemLoggingFieldId(fieldId);
    return config?.[section]?.[key];
  }

  function setSystemLoggingFieldValue(config, fieldId, value) {
    const { section, key } = splitSystemLoggingFieldId(fieldId);
    if (!config[section]) config[section] = {};
    config[section][key] = value;
  }

  function getSystemLoggingFieldType(fieldId) {
    if (systemLoggingForms?.select?.[fieldId]) return "select";
    if (systemLoggingForms?.checkbox?.[fieldId]) return "checkbox";
    if (systemLoggingForms?.number?.[fieldId]) return "number";
    return "text";
  }

  function createSystemLoggingInput(fieldId, value) {
    const type = getSystemLoggingFieldType(fieldId);
    let input;

    if (type === "select") {
      input = document.createElement("select");
      systemLoggingForms.select[fieldId].forEach(choice => {
        const option = document.createElement("option");
        option.value = choice;
        option.textContent = choice;
        if (choice === value) option.selected = true;
        input.appendChild(option);
      });
    } else if (type === "checkbox") {
      input = document.createElement("input");
      input.type = "checkbox";
      input.checked = value === systemLoggingForms.checkbox[fieldId].checked;
    } else if (type === "number") {
      input = document.createElement("input");
      input.type = "number";
      input.min = systemLoggingForms.number[fieldId].min;
      input.max = systemLoggingForms.number[fieldId].max;
      input.value = value;
    } else {
      input = document.createElement("input");
      input.type = "text";
      input.value = value ?? "";
    }

    input.dataset.fieldId = fieldId;
    input.className = "modal-input";
    return input;
  }

  function renderSystemLoggingForm(config) {
    const form = document.getElementById("system-logging-form");
    if (!form) return;
    form.innerHTML = "";

    const table = systemLoggingStructure?.system_logging;
    if (!table || !Array.isArray(table.groups) || typeof table.fields !== "object") {
      setSystemLoggingStatus("Invalid system logging structure JSON", true);
      return;
    }

    table.groups.forEach(group => {
      const fieldset = document.createElement("fieldset");
      fieldset.className = "settings-fieldset";

      const legend = document.createElement("legend");
      legend.textContent = systemLoggingText(group.labelKey, group.fallback || group.id);
      fieldset.appendChild(legend);

      (group.fields || []).forEach(fieldId => {
        const fieldMeta = table.fields[fieldId] || {};
        const wrapper = document.createElement("div");
        wrapper.className = "modal-input-group";

        const label = document.createElement("label");
        label.className = "modal-prefix";
        label.textContent = systemLoggingText(fieldMeta.labelKey, fieldMeta.fallback || fieldId);
        wrapper.appendChild(label);
        wrapper.appendChild(createSystemLoggingInput(fieldId, getSystemLoggingFieldValue(config, fieldId)));
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
      const fieldId = input.dataset.fieldId;
      if (!fieldId) return;

      const type = getSystemLoggingFieldType(fieldId);
      let value;
      if (type === "checkbox") {
        value = input.checked
          ? systemLoggingForms.checkbox[fieldId].checked
          : systemLoggingForms.checkbox[fieldId].unchecked;
      } else if (type === "number") {
        value = parseInt(input.value, 10);
      } else {
        value = input.value;
      }
      setSystemLoggingFieldValue(config, fieldId, value);
    });

    return config;
  }

  function fetchJsonOrThrow(url) {
    return fetch(url).then(response => {
      if (!response.ok) {
        throw new Error(`${url} returned ${response.status}`);
      }
      return response.json();
    });
  }

  function loadSystemLoggingConfig() {
    const form = document.getElementById("system-logging-form");
    if (!form) return;

    Promise.all([
      fetchJsonOrThrow("/system/logging/get_system_logging_structure.php"),
      fetchJsonOrThrow("/system/logging/get_system_logging_forms.php"),
      fetchJsonOrThrow("/system/logging/get_system_logging.php")
    ])
      .then(([structure, forms, configResponse]) => {
        if (structure.error) throw new Error(structure.error);
        if (forms.error) throw new Error(forms.error);
        if (configResponse.error) throw new Error(configResponse.error);

        systemLoggingStructure = structure;
        systemLoggingForms = forms;
        systemLoggingConfig = configResponse.config;
        renderSystemLoggingForm(systemLoggingConfig);
        setSystemLoggingStatus(systemLoggingText("system_logging_loaded", "Candidate configuration loaded."));
      })
      .catch(error => setSystemLoggingStatus(`${systemLoggingText("system_logging_load_error", "Error loading configuration")}: ${error}`, true));
  }

  function saveSystemLoggingConfig() {
    fetch("/system/logging/update_system_logging.php", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF-Token": getCsrfTokenSafe() },
      body: JSON.stringify({ config: collectSystemLoggingConfig() })
    })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          setSystemLoggingStatus(data.error, true);
          return;
        }
        systemLoggingConfig = data.config;
        renderSystemLoggingForm(systemLoggingConfig);
        setSystemLoggingStatus(systemLoggingText("system_logging_saved", "Configuration saved as candidate. Apply Commit to move it to running."));
      })
      .catch(error => setSystemLoggingStatus(`${systemLoggingText("system_logging_save_error", "Error saving configuration")}: ${error}`, true));
  }

  window.initSystemLoggingSettings = loadSystemLoggingConfig;
  loadSystemLoggingConfig();
})();
