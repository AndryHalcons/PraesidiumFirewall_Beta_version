/*
#############################################################################
   Wrapper mínimo de Servicios sobre renderTableGeneric
   Minimal Services wrapper around renderTableGeneric

   La tabla se renderiza con el patrón genérico del firewall. Este archivo solo
   añade el botón de refresco de runtime_status sin tocar generic_table.js.

   The table is rendered with the firewall generic pattern. This file only adds
   the runtime_status refresh button without touching generic_table.js.
#############################################################################
*/
(function () {
  // Devuelve una traducción con fallback local para mensajes de la sección.
  // Returns a translation with local fallback for section messages.
  function t(key, fallback) {
    return (window.LANG && window.LANG[key]) ? window.LANG[key] : fallback;
  }

  // Pinta mensajes cortos junto al botón de refresco runtime.
  // Renders short messages next to the runtime refresh button.
  function setMessage(text) {
    const message = document.getElementById("services-runtime-message");
    if (message) {
      message.textContent = text;
    }
  }

  // Delega el renderizado en renderTableGeneric, manteniendo el patrón común.
  // Delegates rendering to renderTableGeneric, preserving the common pattern.
  function renderGeneric(config) {
    renderTableGeneric(
      config.currentAlias,
      config.endpoints.structure,
      config.endpoints.content,
      config.endpoints.forms,
      config.endpoints.update,
      config.endpoints.delete
    );
  }

  // Pide al backend un check runtime nuevo y re-renderiza la tabla.
  // Requests a fresh backend runtime check and re-renders the table.
  function refreshRuntime(config) {
    setMessage(t("services_refreshing", "Refreshing..."));
    fetch(`${config.endpoints.runtime}?table=${encodeURIComponent(config.currentAlias)}`)
      .then(response => response.json())
      .then(result => {
        if (!result.success) {
          throw new Error(result.error || "runtime refresh failed");
        }
        renderGeneric(config);
        setMessage(t("services_runtime_updated", "Status updated"));
      })
      .catch(error => {
        console.error("runtime refresh failed", error);
        setMessage(t("services_runtime_error", "Could not refresh status"));
      });
  }

  // Enlaza el botón de Actualizar estado de forma idempotente.
  // Binds the Refresh status button idempotently.
  function bindRefresh(config) {
    const button = document.getElementById("services-refresh-runtime");
    if (!button) {
      return;
    }
    button.onclick = () => refreshRuntime(config);
  }

  // API pública usada por services.php cuando el parcial termina de cargar.
  // Public API used by services.php when the partial finishes loading.
  window.PraesidiumServices = {
    render(config) {
      bindRefresh(config);
      renderGeneric(config);
    }
  };
})();
