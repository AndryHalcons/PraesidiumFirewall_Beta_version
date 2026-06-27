
// Devuelve el nombre real de una columna, sea string antiguo u objeto nuevo.
// Returns the real column name, whether it is an old string or a new object.
function genericColumnField(column) {
  // Mantiene compatibilidad hacia atrás: las tablas antiguas siguen enviando strings.
  // Keeps backward compatibility: old tables still send plain string columns.
  // Las columnas nuevas pueden enviar objetos JSON, pero siempre exponemos su field real.
  // New columns may send JSON objects, but we always expose their real field.
  return typeof column === "object" && column !== null ? column.field : column;
}

// Comprueba si una columna declarada por JSON debe pintarse como botón por fila.
// Checks whether a JSON-declared column must be rendered as a per-row button.
function genericIsButtonColumn(column) {
  // Solo las columnas que declaran explícitamente type="button" cambian de comportamiento.
  // Only columns explicitly declaring type="button" change behaviour.
  // Esto evita que el cambio afecte por accidente al resto de tablas del firewall.
  // This prevents the change from accidentally affecting the rest of the firewall tables.
  return typeof column === "object" && column !== null && column.type === "button";
}
function genericIsMultiSelectField(formConfig, key) {
  // El multiselect es opt-in por JSON y no altera los select existentes.
  // Multiselect is opt-in through JSON and does not alter existing select fields.
  return Array.isArray(formConfig?.multiselect?.[key]);
}

function genericIsObjectMultiSelectField(formConfig, key) {
  // El selector de objetos es opt-in y se usa para grupos grandes de objetos.
  // The object selector is opt-in and is used for large object groups.
  return Array.isArray(formConfig?.object_multiselect?.[key]);
}

function genericParseMultiSelectValue(value) {
  // El backend actual espera CSV; el modal lo presenta como chips editables.
  // The current backend expects CSV; the modal presents it as editable chips.
  if (Array.isArray(value)) {
    return value.map(item => String(item).trim()).filter(Boolean);
  }
  return String(value || "")
    .split(",")
    .map(item => item.trim())
    .filter(Boolean);
}

function genericRenderMultiSelectChips(container, selectedValues, onChange) {
  // Redibuja los chips seleccionados y permite quitarlos con la X.
  // Redraws selected chips and allows removing them with the X button.
  container.innerHTML = "";
  selectedValues.forEach(value => {
    const chip = document.createElement("span");
    chip.className = "multiselect-chip";
    chip.textContent = value;

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "multiselect-chip-remove";
    removeBtn.textContent = "×";
    removeBtn.onclick = () => {
      const idx = selectedValues.indexOf(value);
      if (idx !== -1) {
        selectedValues.splice(idx, 1);
        if (typeof onChange === "function") onChange();
        genericRenderMultiSelectChips(container, selectedValues, onChange);
      }
    };

    chip.appendChild(removeBtn);
    container.appendChild(chip);
  });
}

function genericCreateMultiSelectControl(options, currentValue) {
  // Crea un control compuesto: select + botón añadir + chips seleccionados.
  // Creates a compound control: select + add button + selected chips.
  const wrapper = document.createElement("div");
  wrapper.className = "modal-multiselect";
  const selectedValues = genericParseMultiSelectValue(currentValue);
  wrapper.dataset.values = selectedValues.join(",");

  const selectorRow = document.createElement("div");
  selectorRow.className = "modal-multiselect-row";

  const select = document.createElement("select");
  select.className = "modal-input modal-multiselect-select";
  options.forEach(opt => {
    const option = document.createElement("option");
    option.value = opt;
    option.textContent = opt === "" ? " --- " : opt;
    select.appendChild(option);
  });

  const addBtn = document.createElement("button");
  addBtn.type = "button";
  addBtn.className = "modal-button multiselect-add";
  addBtn.textContent = "+";

  const chips = document.createElement("div");
  chips.className = "modal-multiselect-chips";

  const syncChips = () => {
    wrapper.dataset.values = selectedValues.join(",");
    genericRenderMultiSelectChips(chips, selectedValues, syncChips);
  };

  addBtn.onclick = () => {
    const value = select.value.trim();
    if (!value || selectedValues.includes(value)) {
      return;
    }
    selectedValues.push(value);
    syncChips();
  };

  selectorRow.appendChild(select);
  selectorRow.appendChild(addBtn);
  wrapper.appendChild(selectorRow);
  wrapper.appendChild(chips);
  syncChips();
  return wrapper;
}

function genericReadMultiSelectControl(fieldWrapper) {
  // Lee el CSV que entiende el backend desde el control multiselect.
  // Reads the CSV understood by the backend from the multiselect control.
  const wrapper = fieldWrapper.querySelector(".modal-multiselect");
  return wrapper ? (wrapper.dataset.values || "") : "";
}

function genericObjectOptionsForField(formConfig, key) {
  // Normaliza las opciones declaradas para object_multiselect.
  // Normalizes options declared for object_multiselect.
  return (formConfig?.object_multiselect?.[key] || [])
    .map(item => String(item).trim())
    .filter(Boolean);
}

function genericCreateObjectMultiSelectControl(options, currentValue) {
  // Crea un selector buscable de objetos y permite valores manuales confirmados con coma.
  // Creates a searchable object selector and allows manual values confirmed with comma.
  const wrapper = document.createElement("div");
  wrapper.className = "modal-object-multiselect";
  const selectedValues = genericParseMultiSelectValue(currentValue);
  wrapper.dataset.values = selectedValues.join(",");

  const contentPane = document.createElement("div");
  contentPane.className = "object-multiselect-content-pane";

  const selectorPane = document.createElement("div");
  selectorPane.className = "object-multiselect-selector-pane";

  const search = document.createElement("input");
  search.type = "text";
  search.className = "modal-input object-multiselect-search";
  search.placeholder = typeof LANG !== "undefined" && LANG.search ? LANG.search : "Search";

  const dropdown = document.createElement("div");
  dropdown.className = "object-multiselect-dropdown";

  const chips = document.createElement("div");
  chips.className = "modal-multiselect-chips object-multiselect-selected";

  const syncChips = () => {
    wrapper.dataset.values = selectedValues.join(",");
    genericRenderMultiSelectChips(chips, selectedValues, syncChips);
  };

  const addValue = (value, clearSearch = true) => {
    const cleanValue = String(value || "").trim();
    if (!cleanValue || selectedValues.includes(cleanValue)) {
      return;
    }
    selectedValues.push(cleanValue);
    if (clearSearch) {
      search.value = "";
    }
    syncChips();
    renderOptions(clearSearch ? "" : search.value);
  };

  const addManualValuesFromInput = () => {
    // La coma confirma valores manuales sin validar formato; el backend valida al guardar.
    // Comma confirms manual values without format validation; backend validates on save.
    if (!search.value.includes(",")) {
      return false;
    }
    const parts = search.value.split(",");
    const pendingText = parts.pop();
    parts.forEach(part => addValue(part, false));
    search.value = pendingText;
    renderOptions(search.value);
    return true;
  };

  const renderOptions = term => {
    const cleanTerm = String(term || "").trim().toLowerCase();
    dropdown.innerHTML = "";
    if (cleanTerm.length < 3) {
      return;
    }
    const source = options.filter(item => item.toLowerCase().includes(cleanTerm));
    source
      .filter(item => !selectedValues.includes(item))
      .slice(0, 10)
      .forEach(item => {
        const option = document.createElement("button");
        option.type = "button";
        option.className = "object-multiselect-option";
        option.textContent = item;
        option.onclick = () => addValue(item);
        dropdown.appendChild(option);
      });
  };

  search.onfocus = () => renderOptions(search.value);
  search.onclick = () => renderOptions(search.value);
  search.oninput = () => {
    if (addManualValuesFromInput()) {
      return;
    }
    renderOptions(search.value);
  };

  contentPane.appendChild(chips);
  selectorPane.appendChild(search);
  selectorPane.appendChild(dropdown);
  wrapper.appendChild(contentPane);
  wrapper.appendChild(selectorPane);
  syncChips();
  renderOptions("");
  return wrapper;
}

function genericReadObjectMultiSelectControl(fieldWrapper) {
  // Lee el CSV del selector buscable de objetos.
  // Reads the CSV from the searchable object selector.
  const wrapper = fieldWrapper.querySelector(".modal-object-multiselect");
  return wrapper ? (wrapper.dataset.values || "") : "";
}


// Devuelve solo columnas de datos para formularios y envíos al backend.
// Returns only data columns for forms and backend submissions.
function genericDataColumns(columns) {
  // Los botones son acciones visuales por fila, no datos que deba editar/guardar el formulario.
  // Buttons are visual per-row actions, not data that the form should edit/save.
  // Por eso se excluyen de añadir/editar para no mandar campos falsos al backend.
  // They are excluded from add/edit so fake fields are not submitted to the backend.
  return columns.filter(column => !genericIsButtonColumn(column)).map(column => genericColumnField(column));
}


// Estado en memoria para filtros de tablas genéricas, separado por alias de tabla.
// In-memory state for generic table filters, separated by table alias.
window.PraesidiumGenericTableFilters = window.PraesidiumGenericTableFilters || {};

// Rutas backend cacheadas por tabla para que el repintado de filtros pueda reutilizar callbacks.
// Backend routes cached per table so filter repainting can reuse callbacks.
window.PraesidiumGenericTablePaths = window.PraesidiumGenericTablePaths || {};

// Cache de datos ya descargados por tabla; permite filtrar sin repetir llamadas al backend.
// Cache of already downloaded table data; allows filtering without repeating backend calls.
window.PraesidiumGenericTableCache = window.PraesidiumGenericTableCache || {};

// Devuelve el contenedor de estado de filtros de una tabla y lo crea si falta.
// Returns the filter-state container for one table and creates it when missing.
function genericGetTableFilterState(currentAlias) {
  // Cada tabla conserva sus filtros por alias para que navegar/repintar no mezcle secciones.
  // Each table keeps filters by alias so navigation/repaint does not mix sections.
  if (!window.PraesidiumGenericTableFilters[currentAlias]) {
    window.PraesidiumGenericTableFilters[currentAlias] = {};
  }
  return window.PraesidiumGenericTableFilters[currentAlias];
}

// Convierte cualquier valor de celda en texto filtrable estable.
// Converts any cell value into stable text suitable for filtering.
function genericFilterableValue(value) {
  // Normalizamos arrays, nulos y booleanos para que checkbox/multiselect/select filtren igual.
  // Normalize arrays, nulls and booleans so checkbox/multiselect/select filter consistently.
  if (Array.isArray(value)) {
    return value.map(item => genericFilterableValue(item)).join(' ');
  }
  if (value === null || value === undefined) {
    return '';
  }
  if (typeof value === 'boolean') {
    return value ? 'true yes si sí on 1' : 'false no off 0';
  }
  return String(value).toLowerCase();
}

// Devuelve el texto filtrable de una celda con contexto de formulario.
// Returns the filterable text for one cell with form-context awareness.
function genericFilterableCellValue(value, formConfig, key) {
  // Para checkbox, el filtro debe representar el estado visual: true marcado, false desmarcado.
  // For checkboxes, the filter must represent visual state: true checked, false unchecked.
  // Cualquier valor que no sea exactamente el configured checked cuenta como false.
  // Any value not exactly matching configured checked counts as false.
  if (formConfig?.checkbox?.[key]) {
    return value === formConfig.checkbox[key].checked ? 'true' : 'false';
  }
  return genericFilterableValue(value);
}

// Comprueba si una fila cumple todos los filtros activos de su tabla.
// Checks whether one row matches every active filter for its table.
function genericRuleMatchesFilters(rule, columns, activeFilters, formConfig) {
  // La columna Acciones no participa: sólo se recorren columnas de datos visibles.
  // The Actions column is ignored: only visible data columns are evaluated.
  return columns.every(column => {
    if (genericIsButtonColumn(column)) {
      return true;
    }
    const key = genericColumnField(column);
    const expected = String(activeFilters[key] || '').trim().toLowerCase();
    if (!expected) {
      return true;
    }
    return genericFilterableCellValue(rule[key], formConfig, key).includes(expected);
  });
}

// Aplica los filtros activos sobre las filas descargadas del backend.
// Applies active filters to rows already downloaded from the backend.
function genericApplyTableFilters(currentAlias, rules, columns, formConfig) {
  // El filtrado es sólo cliente: no cambia JSON, endpoints ni estado candidato.
  // Filtering is client-side only: it does not change JSON, endpoints or candidate state.
  const activeFilters = genericGetTableFilterState(currentAlias);
  return rules.filter(rule => genericRuleMatchesFilters(rule, columns, activeFilters, formConfig));
}

// Construye la segunda fila de cabecera con un filtro por campo de datos.
// Builds the second header row with one filter per data field.
function genericCreateFilterRow(currentAlias, columns, repaintRows) {
  // Esta fila imita la cabecera: primera celda vacía para Acciones y luego inputs por columna.
  // This row mirrors the header: first cell is empty for Actions and then inputs per column.
  const filterState = genericGetTableFilterState(currentAlias);
  const filterRow = document.createElement('tr');
  filterRow.className = 'generic-table-filter-row';

  const actionsFilterTh = document.createElement('th');
  actionsFilterTh.className = 'generic-table-filter-actions';
  actionsFilterTh.setAttribute('aria-label', LANG['actions'] || 'Actions');
  filterRow.appendChild(actionsFilterTh);

  columns.forEach(column => {
    const th = document.createElement('th');
    th.className = 'generic-table-filter-cell';

    if (!genericIsButtonColumn(column)) {
      const key = genericColumnField(column);
      const input = document.createElement('input');
      input.type = 'search';
      input.className = 'generic-table-filter-input';
      input.placeholder = LANG['filter'] || 'Filter';
      input.value = filterState[key] || '';
      input.setAttribute('aria-label', `${LANG['filter'] || 'Filter'} ${genericColumnLabel(column)}`);

      input.addEventListener('input', () => {
        // Al teclear sólo repintamos el tbody con los datos ya cargados, sin llamada backend.
        // While typing we only repaint tbody with already loaded data, without backend calls.
        filterState[key] = input.value;
        if (typeof repaintRows === 'function') {
          repaintRows();
        }
      });

      th.appendChild(input);
    }

    filterRow.appendChild(th);
  });

  return filterRow;
}

// Pinta las filas visibles de una tabla genérica usando datos y configuración ya cargados.
// Renders visible rows for a generic table using already loaded data and configuration.
function genericRenderTableRows(currentAlias, tbody, rules, columns, formConfig, reloadRows) {
  // Esta función concentra el pintado para poder reutilizarlo al filtrar sin repetir fetch().
  // This function centralizes rendering so filtering can reuse it without repeating fetch().
  tbody.innerHTML = '';

  const filteredRules = genericApplyTableFilters(currentAlias, rules, columns, formConfig);
  if (!Array.isArray(filteredRules) || filteredRules.length === 0) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = columns.length + 1;
    td.className = 'generic-table-no-results';
    td.textContent = Array.isArray(rules) && rules.length > 0
      ? (LANG['filter_no_results'] || LANG['no_data'] || 'No results')
      : (LANG['no_data'] || 'No hay datos');
    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }

  filteredRules.forEach(rule => {
    const tr = document.createElement('tr');

    // Columna Acciones: se mantiene sin filtro y con los mismos botones existentes.
    // Actions column: remains unfiltered and keeps the same existing buttons.
    const actionsTd = document.createElement('td');

    const editBtn = document.createElement('button');
    editBtn.textContent = LANG['edit'] || 'Editar';
    editBtn.onclick = () => editModal_Generic(
      currentAlias,
      window.PraesidiumGenericTablePaths[currentAlias].forms,
      window.PraesidiumGenericTablePaths[currentAlias].update,
      rule,
      columns,
      reloadRows
    );

    actionsTd.appendChild(editBtn);

    if (!formConfig.disable_delete) {
      const deleteBtn = document.createElement('button');
      deleteBtn.textContent = LANG['delete'] || 'Eliminar';
      deleteBtn.onclick = () => delete_Generic(
        currentAlias,
        window.PraesidiumGenericTablePaths[currentAlias].structure,
        window.PraesidiumGenericTablePaths[currentAlias].content,
        window.PraesidiumGenericTablePaths[currentAlias].forms,
        window.PraesidiumGenericTablePaths[currentAlias].update,
        window.PraesidiumGenericTablePaths[currentAlias].delete,
        rule,
        columns
      );
      actionsTd.appendChild(deleteBtn);
    }

    tr.appendChild(actionsTd);

    // Columnas de datos: se respeta el render existente para botones, selects y checkboxes.
    // Data columns: preserve the existing renderer for buttons, selects and checkboxes.
    columns.forEach(column => {
      if (genericIsButtonColumn(column)) {
        tr.appendChild(genericRenderButtonCell(column, rule));
        return;
      }
      const key = genericColumnField(column);
      const td = document.createElement('td');
      const value = rule[key] !== undefined ? rule[key] : '';

      if (genericIsMultiSelectField(formConfig, key) || genericIsObjectMultiSelectField(formConfig, key)) {
        td.textContent = genericParseMultiSelectValue(value).join(', ');
      } else if (formConfig.select?.[key]) {
        const select = document.createElement('select');
        select.disabled = true;
        formConfig.select[key].forEach(opt => {
          const option = document.createElement('option');
          option.value = opt;
          option.textContent = opt;
          if (opt === value) option.selected = true;
          select.appendChild(option);
        });
        td.appendChild(select);
      } else if (formConfig.checkbox?.[key]) {
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
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
}

// Resuelve la etiqueta visible de una columna usando LANG si existe.
// Resolves the visible column label using LANG when available.
function genericColumnLabel(column) {
  // Obtiene la clave de idioma desde label si existe; si no, usa field/string.
  // Gets the language key from label when present; otherwise uses field/string.
  // Así los botones nuevos siguen usando web/lang igual que el resto de columnas.
  // This keeps new buttons using web/lang just like normal columns.
  const labelKey = typeof column === "object" && column !== null ? (column.label || column.field) : column;
  return typeof LANG !== "undefined" && LANG[labelKey] ? LANG[labelKey] : labelKey;
}

// Construye la URL de un botón por fila sin modificar el resto de tablas.
// Builds a per-row button URL without changing the rest of the tables.
function genericButtonUrl(column, rule) {
  // Lee de la fila el identificador declarado por value_field; no usa estado global.
  // Reads the declared value_field from this row; it does not use global state.
  // Esto garantiza que cada botón descargue/actúe solo sobre su propia fila.
  // This guarantees each button downloads/acts only on its own row.
  const paramName = column.param || "name";
  const valueField = column.value_field || paramName;
  const value = rule[valueField] !== undefined ? rule[valueField] : "";

  // Respeta endpoints que ya traen query string para no construir URLs inválidas.
  // Respects endpoints that already include a query string to avoid invalid URLs.
  const separator = column.endpoint.includes("?") ? "&" : "?";
  return `${column.endpoint}${separator}${encodeURIComponent(paramName)}=${encodeURIComponent(value)}`;
}

// Pinta un botón declarado en JSON y ligado únicamente a su fila.
// Renders a JSON-declared button bound only to its own row.
function genericRenderButtonCell(column, rule) {
  // Crea una celda normal de tabla para que el layout siga siendo genérico.
  // Creates a normal table cell so the layout remains generic.
  const td = document.createElement("td");

  // El botón se define por JSON, pero el texto sigue saliendo del sistema LANG.
  // The button is defined by JSON, but its text still comes from the LANG system.
  const button = document.createElement("button");
  button.type = "button";
  button.className = column.class || "table-action-button";
  button.textContent = genericColumnLabel(column);

  // Al pulsar, se calcula la URL con los datos de la fila actual.
  // On click, the URL is computed from the current row data.
  // No se mezclan filas ni se busca el cliente por posición visual de la tabla.
  // Rows are not mixed and the client is not inferred from the visual table position.
  button.onclick = () => {
    const url = genericButtonUrl(column, rule);
    if (column.target === "blank") {
      window.open(url, "_blank", "noopener");
    } else {
      window.location.href = url;
    }
  };
  td.appendChild(button);
  return td;
}

// Renderiza una tabla genérica desde estructura JSON y rutas backend configurables.
// Renders a generic table from JSON structure and configurable backend routes.
function renderTableGeneric(currentAlias, path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete) {
  // Fase 1: pedir al backend la estructura declarativa de la tabla.
  // Phase 1: ask the backend for the declarative table structure.
  // Importante: este script lo usa todo el firewall; los cambios deben ser mínimos y reversibles.
  // Important: the whole firewall uses this script; changes must be minimal and reversible.
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

      // Leer la configuración del formulario para respetar opciones comunes
      // Read form configuration to honor shared table options
      fetch(`${path_get_forms_from_table}?table=${currentAlias}`)
        .then(res => res.json())
        .then(formConfigForTable => {
          if (!formConfigForTable.disable_add) {
            // Insertar el botón "Agregar" antes del contenedor
            // Insert the shared "Add" button before the container
            const addBtn = document.createElement("button");
            addBtn.textContent = LANG["add"] || "Add";
            addBtn.onclick = () => add_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
            container.insertAdjacentElement("beforebegin", addBtn);
          }
        })
        .catch(error => console.error("Error al cargar opciones de formulario:", error));

      container.innerHTML = "";

      const table = document.createElement("table");
      table.className = "interfaz";

      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");

      // Columna de acciones
      const actionsTh = document.createElement("th");
      actionsTh.textContent = typeof LANG !== "undefined" && LANG["actions"] ? LANG["actions"] : "Acciones";
      headerRow.appendChild(actionsTh);

      // Columnas declaradas por JSON, compatibles con strings antiguos y objetos nuevos.
      // JSON-declared columns, compatible with old strings and new objects.
      columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = genericColumnLabel(col);
        headerRow.appendChild(th);
      });

      thead.appendChild(headerRow);

      // Segunda fila de cabecera: filtros por columna, sin filtro en Acciones.
      // Second header row: per-column filters, with no filter in Actions.
      const filterRow = genericCreateFilterRow(currentAlias, columns, () => {
        const tbody = document.querySelector(`#${currentAlias}_table table tbody`);
        const cached = window.PraesidiumGenericTableCache?.[currentAlias];
        if (tbody && cached) {
          genericRenderTableRows(currentAlias, tbody, cached.rules, columns, cached.formConfig, cached.reloadRows);
        }
      });
      thead.appendChild(filterRow);
      table.appendChild(thead);

      const tbody = document.createElement("tbody");
      table.appendChild(tbody);

      container.appendChild(table);

      // Guardar rutas para callbacks de editar/eliminar usados por el repintado filtrado.
      // Store routes for edit/delete callbacks used by filtered repainting.
      window.PraesidiumGenericTablePaths[currentAlias] = {
        structure: path_get_table_structure,
        content: path_get_table_content,
        forms: path_get_forms_from_table,
        update: path_get_update,
        delete: path_get_delete
      };

      // Cargar contenido de la tabla
      loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
    })
    .catch(error => {
      const container = document.getElementById(`${currentAlias}_table`);
      if (container) {
        container.innerHTML = `<div class="error">Error de conexión con el servidor</div>`;
      }
    });
}


// Carga filas de una tabla genérica y decide cómo pintar cada columna declarada.
// Loads rows for a generic table and decides how to render each declared column.
function loadTableContentGeneric(currentAlias, path_get_table_structure, path_get_table_content, path_get_forms_from_table, path_get_update, path_get_delete, columns) {
  const endpoint = path_get_table_content; 
  const param = `table=${currentAlias}`;
  console.log(" Enviando al backend:", `${endpoint}?${param}`);
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
      if (!Array.isArray(rules)) {
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
          // Cachear datos y configuración: los filtros repintan en cliente sin más fetch().
          // Cache data and configuration: filters repaint client-side without extra fetch().
          const reloadRows = () => loadTableContentGeneric(
            currentAlias,
            path_get_table_structure,
            path_get_table_content,
            path_get_forms_from_table,
            path_get_update,
            path_get_delete,
            columns
          );
          window.PraesidiumGenericTableCache[currentAlias] = { rules, formConfig, reloadRows };
          genericRenderTableRows(currentAlias, tbody, rules, columns, formConfig, reloadRows);
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


// Abre el modal de edición usando solo columnas persistentes de datos.
// Opens the edit modal using only persistent data columns.
function editModal_Generic(currentAlias, path_get_forms_from_table, path_get_update, rule, columns, onSuccess) {
  // Fase 3: abrir edición solo con columnas de datos persistentes.
  // Phase 3: open editing only with persistent data columns.
  // Las columnas botón se descartan aquí para no exponer acciones como campos editables.
  // Button columns are discarded here so actions are not exposed as editable fields.
  const endpoint = path_get_forms_from_table;
  const param = `table=${currentAlias}`;
  const dataColumns = genericDataColumns(columns);

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      const modal = document.createElement("div");
      modal.className = "modal-overlay";

      const modalContent = document.createElement("div");
      modalContent.className = "modal-window";

      const title = document.createElement("h3");
      title.textContent = `Editar política de ${currentAlias}`;
      modalContent.appendChild(title);

      const form = document.createElement("form");

      dataColumns.forEach(key => {
        const fieldWrapper = document.createElement("div");
        fieldWrapper.className = "modal-input-group";

        const label = document.createElement("label");
        label.textContent = typeof LANG !== "undefined" && LANG[key] ? LANG[key] : key;
        label.className = "modal-prefix";
        fieldWrapper.appendChild(label);

        if (formConfig.not_editable && Object.keys(formConfig.not_editable).includes(key)) {
          const span = document.createElement("span");
          span.textContent = rule[key] || "Auto";
          span.className = "modal-input";
          fieldWrapper.appendChild(span);
          form.appendChild(fieldWrapper);
          return;
        }

        if (genericIsMultiSelectField(formConfig, key)) {
          const multiselect = genericCreateMultiSelectControl(formConfig.multiselect[key], rule[key] || "");
          fieldWrapper.appendChild(multiselect);
          form.appendChild(fieldWrapper);
          return;
        }

        if (genericIsObjectMultiSelectField(formConfig, key)) {
          const objectMultiselect = genericCreateObjectMultiSelectControl(genericObjectOptionsForField(formConfig, key), rule[key] || "");
          fieldWrapper.appendChild(objectMultiselect);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.select?.[key]) {
          const select = document.createElement("select");
          select.className = "modal-input";
          formConfig.select[key].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt === "" ? " --- " : opt;
            if (opt === rule[key]) option.selected = true;
            select.appendChild(option);
          });
          fieldWrapper.appendChild(select);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.checkbox?.[key]) {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.checked = rule[key] === formConfig.checkbox[key].checked;
          fieldWrapper.appendChild(checkbox);
          form.appendChild(fieldWrapper);
          return;
        }

        const input = document.createElement("input");
        input.type = "text";
        input.name = key;
        input.value = rule[key] || "";
        input.className = "modal-input";
        fieldWrapper.appendChild(input);
        form.appendChild(fieldWrapper);
      });

      const errorBox = document.createElement("div");
      errorBox.className = "modal-save-error";
      errorBox.style.display = "none";
      form.appendChild(errorBox);

      const actions = document.createElement("div");
      actions.className = "modal-actions";

      const saveBtn = document.createElement("button");
      saveBtn.type = "button";
      saveBtn.textContent = "Guardar";
      saveBtn.className = "modal-button";
      saveBtn.onclick = () => {
        const updatedRule = {};

        dataColumns.forEach(key => {
          const fieldWrapper = form.querySelectorAll(".modal-input-group")[dataColumns.indexOf(key)];

          if (formConfig.not_editable?.[key]) {
            const span = fieldWrapper.querySelector("span");
            updatedRule[key] = span ? span.textContent : rule[key];
            return;
          }

          const el = fieldWrapper.querySelector("select") ||
                     fieldWrapper.querySelector("input[type='checkbox']") ||
                     fieldWrapper.querySelector("input[type='text']");

          let value = "";

          if (genericIsMultiSelectField(formConfig, key)) {
            value = genericReadMultiSelectControl(fieldWrapper);
          } else if (genericIsObjectMultiSelectField(formConfig, key)) {
            value = genericReadObjectMultiSelectControl(fieldWrapper);
          } else if (el && el.tagName === "SELECT") {
            value = el.value;
          } else if (el && el.type === "checkbox") {
            value = el.checked
              ? formConfig.checkbox[key].checked
              : formConfig.checkbox[key].unchecked;
          } else if (el && el.tagName === "INPUT") {
            value = el.value;
          }

          updatedRule[key] = value;
        });

        errorBox.style.display = "none";
        errorBox.textContent = "";
        send_Generic(currentAlias, path_get_update, updatedRule, columns, () => {
          document.body.removeChild(modal);
          if (typeof onSuccess === "function") onSuccess();
        }, message => genericShowModalSaveError(errorBox, message));
      };
      actions.appendChild(saveBtn);

      const closeBtn = document.createElement("button");
      closeBtn.type = "button";
      closeBtn.textContent = "Cancelar";
      closeBtn.className = "modal-button cancel";
      closeBtn.onclick = () => document.body.removeChild(modal);
      actions.appendChild(closeBtn);

      form.appendChild(actions);
      modalContent.appendChild(form);
      modal.appendChild(modalContent);
      document.body.appendChild(modal);
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}



// Abre el modal de creación usando la misma definición genérica de formularios.
// Opens the creation modal using the same generic form definition.
function add_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table,path_get_update,path_get_delete, columns) {
  // Fase 4: crear una entrada nueva con el mismo filtro de columnas de datos.
  // Phase 4: create a new entry with the same data-column filter.
  // Esto evita que un botón declarado en la tabla acabe guardado en el JSON candidate.
  // This prevents a declared table button from being saved into the candidate JSON.
  const endpoint = path_get_forms_from_table;
  const param = `table=${currentAlias}`;
  const dataColumns = genericDataColumns(columns);

  fetch(`${endpoint}?${param}`)
    .then(response => response.json())
    .then(formConfig => {
      const modal = document.createElement("div");
      modal.className = "modal-overlay";

      const modalContent = document.createElement("div");
      modalContent.className = "modal-window";

      const title = document.createElement("h3");
      title.textContent = `Agregar política a ${currentAlias}`;
      modalContent.appendChild(title);

      const form = document.createElement("form");

      dataColumns.forEach(key => {
        const fieldWrapper = document.createElement("div");
        fieldWrapper.className = "modal-input-group";

        const label = document.createElement("label");
        label.textContent = typeof LANG !== "undefined" && LANG[key] ? LANG[key] : key;
        label.className = "modal-prefix";
        fieldWrapper.appendChild(label);

        if (formConfig.not_editable && Object.keys(formConfig.not_editable).includes(key)) {
          const span = document.createElement("span");
          span.textContent = "Auto";
          span.className = "modal-input";
          fieldWrapper.appendChild(span);
          form.appendChild(fieldWrapper);
          return;
        }

        if (genericIsMultiSelectField(formConfig, key)) {
          const multiselect = genericCreateMultiSelectControl(formConfig.multiselect[key], "");
          fieldWrapper.appendChild(multiselect);
          form.appendChild(fieldWrapper);
          return;
        }

        if (genericIsObjectMultiSelectField(formConfig, key)) {
          const objectMultiselect = genericCreateObjectMultiSelectControl(genericObjectOptionsForField(formConfig, key), "");
          fieldWrapper.appendChild(objectMultiselect);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.select && Array.isArray(formConfig.select[key])) {
          const select = document.createElement("select");
          select.className = "modal-input";
          formConfig.select[key].forEach(opt => {
            const option = document.createElement("option");
            option.value = opt;
            option.textContent = opt === "" ? " --- " : opt;
            select.appendChild(option);
          });
          fieldWrapper.appendChild(select);
          form.appendChild(fieldWrapper);
          return;
        }

        if (formConfig.checkbox && typeof formConfig.checkbox[key] === "object") {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.checked = false;
          fieldWrapper.appendChild(checkbox);
          form.appendChild(fieldWrapper);
          return;
        }

        const input = document.createElement("input");
        input.type = "text";
        input.name = key;
        input.value = "";
        input.className = "modal-input";
        fieldWrapper.appendChild(input);
        form.appendChild(fieldWrapper);
      });

      const errorBox = document.createElement("div");
      errorBox.className = "modal-save-error";
      errorBox.style.display = "none";
      form.appendChild(errorBox);

      const actions = document.createElement("div");
      actions.className = "modal-actions";

      const saveBtn = document.createElement("button");
      saveBtn.type = "button";
      saveBtn.textContent = "Guardar";
      saveBtn.className = "modal-button";
      saveBtn.onclick = () => {
        const updatedRule = {};

        dataColumns.forEach(key => {
          const fieldWrapper = form.querySelectorAll(".modal-input-group")[dataColumns.indexOf(key)];
          const el = fieldWrapper.querySelector("select") || fieldWrapper.querySelector("input");

          let value = "";

          if (formConfig.not_editable && Object.keys(formConfig.not_editable).includes(key)) {
            return;
          }

          if (genericIsMultiSelectField(formConfig, key)) {
            value = genericReadMultiSelectControl(fieldWrapper);
          } else if (genericIsObjectMultiSelectField(formConfig, key)) {
            value = genericReadObjectMultiSelectControl(fieldWrapper);
          } else if (el && el.tagName === "SELECT") {
            value = el.value;
          } else if (el && el.type === "checkbox") {
            if (formConfig.checkbox?.[key]) {
              value = el.checked
                ? formConfig.checkbox[key].checked
                : formConfig.checkbox[key].unchecked;
            } else {
              value = el.checked ? "==" : "!=";
            }
          } else if (el && el.tagName === "INPUT") {
            value = el.value;
          }

          updatedRule[key] = value;
        });

        errorBox.style.display = "none";
        errorBox.textContent = "";
        send_Generic(currentAlias, path_get_update, updatedRule, columns, () => {
          document.body.removeChild(modal);
          loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
        }, message => genericShowModalSaveError(errorBox, message));
      };
      actions.appendChild(saveBtn);

      const closeBtn = document.createElement("button");
      closeBtn.type = "button";
      closeBtn.textContent = "Cancelar";
      closeBtn.className = "modal-button cancel";
      closeBtn.onclick = () => document.body.removeChild(modal);
      actions.appendChild(closeBtn);

      form.appendChild(actions);
      modalContent.appendChild(form);
      modal.appendChild(modalContent);
      document.body.appendChild(modal);
    })
    .catch(error => {
      console.error("Error al cargar configuración de formulario:", error);
    });
}

// Muestra errores de guardado dentro del modal sin cerrarlo.
// Shows save errors inside the modal without closing it.
function genericShowModalSaveError(errorBox, backendMessage) {
  if (!errorBox) {
    return;
  }
  const detail = String(backendMessage || "").trim();
  errorBox.textContent = detail
    ? `Datos inválidos, no se puede guardar. ${detail}`
    : "Datos inválidos, no se puede guardar.";
  errorBox.style.display = "block";
}

// Envía al backend una regla creada o editada desde la tabla genérica.
// Sends a created or edited rule from the generic table to the backend.
function send_Generic(currentAlias, path_get_update, updatedRule, columns, onSuccess, onError) {
  const endpoint = path_get_update;
  const payload = {
    table: currentAlias,
    rule: updatedRule
  };

  console.log("Enviando al backend:", JSON.stringify(payload, null, 2));

  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": getCsrfToken()
    },
    body: JSON.stringify(payload)
  })
    .then(response => response.text())
    .then(text => {
      console.log(" Respuesta cruda del backend:", text);
      try {
        const result = JSON.parse(text);
        console.log(" JSON parseado:", result);

        if (result.error) {
          console.error(" Error al guardar en el backend:", result.error);
          if (typeof onError === "function") {
            onError(result.error);
          } else {
            alert("Datos inválidos, no se puede guardar.\n\n" + result.error);
          }
          return;
        }

        if (result.success === true) {
          if (typeof onSuccess === "function") {
            onSuccess();
          }
          return;
        }

        const unexpectedMessage = "Respuesta inesperada del backend";
        console.error(unexpectedMessage, result);
        if (typeof onError === "function") {
          onError(unexpectedMessage);
        } else {
          alert(unexpectedMessage);
        }
      } catch (e) {
        console.error(" No se pudo parsear JSON:", e);
        const parseMessage = "Error al parsear la respuesta del servidor";
        if (typeof onError === "function") {
          onError(parseMessage);
        } else {
          alert(parseMessage + ":\n\n" + text);
        }
      }
    })
    .catch(error => {
      console.error("Error de conexión al guardar:", error);
      const connectionMessage = "Error de conexión al guardar";
      if (typeof onError === "function") {
        onError(connectionMessage);
      } else {
        alert(connectionMessage + ":\n\n" + error);
      }
    });
}

// Borra una fila tras confirmación y recarga la tabla afectada.
// Deletes a row after confirmation and reloads the affected table.
function delete_Generic(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, rule, columns) {
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
  console.log(" Enviando al backend:", JSON.stringify(payload, null, 2));
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
        loadTableContentGeneric(currentAlias,path_get_table_structure,path_get_table_content,path_get_forms_from_table, path_get_update,path_get_delete, columns);
      } else {
        alert(result.error || "Error al eliminar la política");
      }
    })
    .catch(error => {
      console.error("Error al eliminar la política:", error);
      alert("Error de conexión con el servidor");
    });
}




    
