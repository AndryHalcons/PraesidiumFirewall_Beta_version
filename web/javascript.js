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

                // 👉 Cargar dashboard.js solo si estamos en dashboard.php
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

    // 🚀 Cargar dashboard.php por defecto al entrar
    cargarPagina("dashboard/dashboard.php");
});
