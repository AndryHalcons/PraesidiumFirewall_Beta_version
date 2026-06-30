/*
###############################################################################
  Interacción cliente del Monitor de sesiones conntrack
  Client-side interaction for the conntrack session monitor

  Responsabilidades / Responsibilities:
    - Enviar la petición de refresco con CSRF al endpoint PHP.
      Send the refresh request with CSRF to the PHP endpoint.
    - Reemplazar la tabla HTML devuelta por el backend.
      Replace the HTML table returned by the backend.
    - Aplicar filtros cliente por columna, estilo generic_table.js pero local.
      Apply client-side per-column filters, generic_table.js style but local.

  Límites / Boundaries:
    - No toca generic_table.js.
      It does not touch generic_table.js.
    - No acepta comandos ni rutas desde el usuario.
      It does not accept commands or paths from the user.
    - No muestra salida interna del extractor en la UI final.
      It does not show internal extractor output in the final UI.
###############################################################################
*/
(function () {
    function lang(key, fallback) {
        return (typeof LANG === 'object' && LANG && LANG[key]) ? LANG[key] : fallback;
    }

    /*
    ###########################################################################
      Obtiene el token CSRF publicado por mainpage.php
      Gets the CSRF token published by mainpage.php
    ###########################################################################
    */
    function csrfToken() {
        // Preferimos el helper global si existe para mantener patrón Praesidium.
        // Prefer the global helper when present to keep the Praesidium pattern.
        if (typeof getCsrfToken === 'function') {
            return getCsrfToken();
        }

        // Fallback directo al meta tag para cargas parciales.
        // Direct meta tag fallback for partial loads.
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /*
    ###########################################################################
      Aplica filtros AND por columna sobre la tabla local de sesiones
      Applies AND per-column filters over the local sessions table
    ###########################################################################
    */
    function applySessionFilters() {
        const table = document.querySelector('#monitor-session-table-wrapper table.monitor-session-table');
        if (!table) return;

        // Recoge filtros activos y normaliza todo a minúsculas para búsqueda parcial.
        // Collect active filters and normalize everything to lowercase for partial search.
        const filters = Array.from(table.querySelectorAll('[data-monitor-session-filter-column]'))
            .map(input => ({
                column: Number(input.dataset.monitorSessionFilterColumn),
                value: (input.value || '').trim().toLowerCase()
            }))
            .filter(filter => filter.value !== '');

        // Las filas reales excluyen la fila auxiliar de "sin resultados".
        // Real rows exclude the auxiliary "no results" row.
        const rows = Array.from(table.querySelectorAll('tbody tr'))
            .filter(row => !row.classList.contains('monitor-session-no-results-row'));
        let visibleRows = 0;

        // Una fila debe cumplir todos los filtros activos.
        // A row must match all active filters.
        rows.forEach(row => {
            const cells = Array.from(row.cells);
            const matches = filters.every(filter => {
                const cell = cells[filter.column];
                return cell && (cell.textContent || '').toLowerCase().includes(filter.value);
            });

            row.style.display = matches ? '' : 'none';
            if (matches) visibleRows += 1;
        });

        // Crea o actualiza la fila auxiliar de "sin resultados".
        // Create or update the auxiliary "no results" row.
        let noResultsRow = table.querySelector('tbody tr.monitor-session-no-results-row');
        if (!noResultsRow) {
            noResultsRow = document.createElement('tr');
            noResultsRow.className = 'monitor-session-no-results-row';
            const cell = document.createElement('td');
            cell.colSpan = table.querySelectorAll('thead tr:first-child th').length || 1;
            cell.textContent = lang('monitor_sessions_filter_no_results', 'No results for the active filters');
            noResultsRow.appendChild(cell);
            table.querySelector('tbody').appendChild(noResultsRow);
        }
        noResultsRow.style.display = visibleRows === 0 ? '' : 'none';
    }

    /*
    ###########################################################################
      Enlaza filtros tras la carga inicial o tras refrescar la tabla
      Binds filters after initial load or after refreshing the table
    ###########################################################################
    */
    function bindSessionFilters() {
        document.querySelectorAll('[data-monitor-session-filter-column]').forEach(input => {
            // Evita registrar listeners duplicados al repintar la tabla.
            // Avoid registering duplicate listeners when repainting the table.
            if (input.dataset.monitorSessionFilterBound === '1') return;
            input.dataset.monitorSessionFilterBound = '1';
            input.addEventListener('input', applySessionFilters);
        });

        // Recalcula visibilidad inicial, por si hay valores conservados por navegador.
        // Recalculate initial visibility in case the browser preserved values.
        applySessionFilters();
    }

    /*
    ###########################################################################
      Refresca el snapshot conntrack y sustituye la tabla visible
      Refreshes the conntrack snapshot and replaces the visible table
    ###########################################################################
    */
    async function refreshSessions() {
        const tableWrapper = document.getElementById('monitor-session-table-wrapper');
        const refreshButton = document.getElementById('monitor-session-refresh');
        if (!tableWrapper || !refreshButton) return;

        // Feedback mínimo: no se muestra salida interna ni comando ejecutado.
        // Minimal feedback: do not show internal output or executed command.
        const originalText = refreshButton.textContent;
        refreshButton.disabled = true;
        refreshButton.textContent = lang('monitor_sessions_refreshing', 'Refreshing...');

        const body = new FormData();
        body.append('action', 'refresh');

        try {
            // POST protegido por CSRF; el usuario real lo decide PHP desde sesión.
            // CSRF-protected POST; PHP decides the real user from session.
            const res = await fetch('/monitor_session/monitor_session.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken()
                },
                body: body
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                alert(data.error || ('HTTP ' + res.status));
                return;
            }

            // El backend devuelve HTML ya escapado; se inserta como tabla completa.
            // Backend returns already-escaped HTML; insert it as a complete table.
            tableWrapper.innerHTML = data.table_html || '';
            bindSessionFilters();
        } catch (error) {
            alert(lang('monitor_sessions_reload_error', 'Could not reload sessions'));
        } finally {
            // Restaura el botón aunque el backend falle.
            // Restore the button even when the backend fails.
            refreshButton.disabled = false;
            refreshButton.textContent = originalText;
        }
    }

    // Enlace principal del botón de refresco si el usuario tiene rol admin.
    // Main refresh button binding when the user has admin role.
    const refreshButton = document.getElementById('monitor-session-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', refreshSessions);
    }

    // Activa filtros de una tabla ya existente al cargar la página.
    // Activate filters for an already existing table on page load.
    bindSessionFilters();
})();
