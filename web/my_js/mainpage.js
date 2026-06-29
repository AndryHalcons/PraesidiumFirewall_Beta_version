/*
#############################################################################
   Redirige al login cuando una petición detecta sesión caducada
   Redirects to login when a request detects an expired session
#############################################################################
*/
function praesidiumRedirectToLogin(redirectUrl) {
    const target = redirectUrl || "/index.php";
    if (window.location.pathname !== target) {
        window.location.href = target;
    }
}

/*
#############################################################################
   Instala una guarda global para fetch sin tocar cada módulo de la WebGUI
   Installs a global fetch guard without editing every WebGUI module
#############################################################################
*/
(function installPraesidiumFetchAuthGuard() {
    if (window.praesidiumFetchAuthGuardInstalled || typeof window.fetch !== "function") {
        return;
    }

    window.praesidiumFetchAuthGuardInstalled = true;
    const nativeFetch = window.fetch.bind(window);

    window.fetch = async function praesidiumFetch(input, init = {}) {
        const options = { ...init };
        const headers = new Headers(options.headers || {});
        if (!headers.has("X-Requested-With")) {
            headers.set("X-Requested-With", "XMLHttpRequest");
        }
        options.headers = headers;

        const response = await nativeFetch(input, options);
        const redirectHeader = response.headers.get("X-Praesidium-Login-Redirect");

        if (response.status === 401 || redirectHeader || (response.redirected && response.url.includes("/index.php"))) {
            praesidiumRedirectToLogin(redirectHeader || "/index.php");
        }

        return response;
    };
})();

/*
#############################################################################
   Lee el token CSRF publicado por mainpage.php
   Reads the CSRF token published by mainpage.php
#############################################################################
*/
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
}

document.addEventListener("DOMContentLoaded", () => {
    const mainContent = document.getElementById("main-content");

    // Función reutilizable para cargar páginas
    function cargarPagina(page) {
        if (window.praesidiumDashboardCleanup) {
            window.praesidiumDashboardCleanup();
            window.praesidiumDashboardCleanup = null;
        }
        document.querySelectorAll('script[src="/dashboard/dashboard.js"]').forEach(script => script.remove());

        fetch(page)
            .then(res => {
                if (!res.ok) throw new Error(`Error al cargar ${page}`);
                return res.text();
            })
            .then(html => {
                mainContent.innerHTML = html;

                // Ejecutar scripts embebidos (pero NO dashboard.js)
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;

                tempDiv.querySelectorAll("script").forEach(script => {
                    const newScript = document.createElement("script");

                    if (script.src && !script.src.includes("dashboard.js")) {
                        newScript.src = script.src;
                    } else if (!script.src) {
                        newScript.textContent = script.textContent;
                    }

                    if (script.type) {
                        newScript.type = script.type;
                    }

                    document.body.appendChild(newScript);
                });

                // Cargar dashboard.js solo si estamos en dashboard.php
                if (page === "dashboard/dashboard.php") {
                    const dashboardScript = document.createElement("script");
                    dashboardScript.src = "/dashboard/dashboard.js";
                    dashboardScript.defer = true;
                    document.body.appendChild(dashboardScript);
                }
            })
            .catch(err => {
                mainContent.innerHTML = `<p style="color:red;">No se pudo cargar el contenido.</p>`;
                console.error(err);
            });
    }

    // Listeners para todos los enlaces
    document.querySelectorAll("a[data-page]").forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const page = link.getAttribute("data-page");
            cargarPagina(page);
        });
    });

    // Cargar dashboard.php por defecto al entrar
    cargarPagina("dashboard/dashboard.php");
});
