(function () {
    // Función independiente para el botón
    function reloadSystemRoutes() {
        fetch("/routing/update_routing/reload_system_routes_running.php")
            .then(response => {
                if (!response.ok) throw new Error("Error al ejecutar la recarga");
                return response.json();
            })
            .then(data => {
                console.log("Respuesta del servidor:", data);
                renderRoutingTable(); // Recarga la tabla después de ejecutar
            })
            .catch(error => {
                console.error("Error al recargar rutas:", error);
                alert("No se pudo recargar las rutas del sistema.");
            });
    }

    // Función para renderizar la tabla
    function renderRoutingTable() {
        const container = document.getElementById("routing-table");
        if (!container) return;

        container.innerHTML = "";

        // Crear botón de recarga con clase "boton-generic"
        const reloadButton = document.createElement("button");
        reloadButton.textContent = LANG.reload_routes;
        reloadButton.className = "boton-generic";
        reloadButton.onclick = reloadSystemRoutes;
        container.appendChild(reloadButton);

        // Separado: el botón se muestra antes de intentar cargar el JSON
        fetch("/routing/update_routing/get_routes.php")
            .then(response => {
                if (!response.ok) throw new Error("No se pudo cargar el archivo");
                return response.json();
            })
            .then(data => {
                // Si no hay rutas válidas, no hacemos nada más
                if (!data.routes || !Array.isArray(data.routes)) {
                    throw new Error("Formato de datos inválido");
                }

                const routeTable = document.createElement("table");
                routeTable.className = "interfaz";

                const thead = document.createElement("thead");
                const routeHeader = document.createElement("tr");
                [
                    LANG.table, LANG.ip_version, LANG.action, LANG.destination,
                    LANG.interface, LANG.gateway, LANG.metric,
                    LANG.proto, LANG.scope, LANG.src
                ].forEach(text => {
                    const th = document.createElement("th");
                    th.textContent = text;
                    routeHeader.appendChild(th);
                });
                thead.appendChild(routeHeader);
                routeTable.appendChild(thead);

                const tbody = document.createElement("tbody");
                data.routes.forEach(route => {
                    const row = document.createElement("tr");
                    [
                        "table", "ip_version", "action", "destination", "interface",
                        "gateway", "metric", "proto", "scope", "src"
                    ].forEach(key => {
                        const td = document.createElement("td");
                        td.textContent = route[key] ?? "-";
                        row.appendChild(td);
                    });
                    tbody.appendChild(row);
                });
                routeTable.appendChild(tbody);

                container.appendChild(document.createElement("h3")).textContent = LANG.routing_title;
                container.appendChild(routeTable);

                if (data.rules && Array.isArray(data.rules) && data.rules.length > 0) {
                    const ruleTable = document.createElement("table");
                    ruleTable.className = "interfaz";

                    const theadRules = document.createElement("thead");
                    const ruleHeader = document.createElement("tr");
                    [LANG.action, LANG.rule_from, LANG.rule_table].forEach(text => {
                        const th = document.createElement("th");
                        th.textContent = text;
                        ruleHeader.appendChild(th);
                    });
                    theadRules.appendChild(ruleHeader);
                    ruleTable.appendChild(theadRules);

                    const tbodyRules = document.createElement("tbody");
                    data.rules.forEach(rule => {
                        const row = document.createElement("tr");
                        ["action", "from", "table"].forEach(key => {
                            const td = document.createElement("td");
                            td.textContent = rule[key] ?? "-";
                            row.appendChild(td);
                        });
                        tbodyRules.appendChild(row);
                    });
                    ruleTable.appendChild(tbodyRules);

                    container.appendChild(document.createElement("h3")).textContent = LANG.rules_title;
                    container.appendChild(ruleTable);
                }
            })
            .catch(error => {
                const errorMsg = document.createElement("p");
                errorMsg.textContent = LANG.loading_routes + ": " + error.message;
                container.appendChild(errorMsg);
            });
    }

    renderRoutingTable();
})();
