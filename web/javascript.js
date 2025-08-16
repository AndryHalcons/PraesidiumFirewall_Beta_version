document.addEventListener("DOMContentLoaded", () => {
    const mainContent = document.getElementById("main-content");

    // Función reutilizable para cargar páginas
    function cargarPagina(page) {
        fetch(page)
            .then(res => {
                if (!res.ok) throw new Error(`Error al cargar ${page}`);
                return res.text();
            })
            .then(html => {
                mainContent.innerHTML = html;

                // Crear un contenedor temporal para extraer scripts
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;

                // Ejecutar scripts embebidos y externos
                tempDiv.querySelectorAll("script").forEach(script => {
                    const newScript = document.createElement("script");

                    // Copiar atributos relevantes
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }

                    // Si el script tiene tipo (por ejemplo, module), conservarlo
                    if (script.type) {
                        newScript.type = script.type;
                    }

                    // Añadir el script al documento
                    document.body.appendChild(newScript);
                });
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
