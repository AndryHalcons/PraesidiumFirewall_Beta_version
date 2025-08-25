function loadInterfaceTable() {
    fetch('/interfaces/table_interfaces/get_interfaces.php')
        .then(response => response.json())
        .then(data => {
            renderInterfaceTable(data);  // Para ethernets
            renderBondTable(data);       // Para bonds ✅
            renderBridgeTable(data);     // Para bridges
        })
        .catch(err => {
            console.error('Error al cargar el JSON:', err);
        });
}



function renderInterfaceTable(interfaces) {
    const container = document.getElementById('tabla-interfaces');
    if (!container || !interfaces.network) return;

    const grupoEthernet = interfaces.network.ethernets || {};
    const resultado = [];

    Object.entries(grupoEthernet).forEach(([nombre, config]) => {
        const iface = {
            name: nombre,
            type: 'ethernet',
            ...config
        };

        if (config.nameservers?.addresses) {
            iface['nameservers'] = config.nameservers.addresses.join(', ');
        }
        if (config.nameservers?.search) {
            iface['dns-search'] = config.nameservers.search.join(', ');
        }
        if (config.addresses) {
            iface['address'] = config.addresses.join(', ');
        }
        if (config.gateway4) {
            iface['gateway'] = config.gateway4;
        }
        if (config.gateway6) {
            iface['gateway'] = config.gateway6;
        }
        if (config.macaddress) {
            iface['hwaddress'] = config.macaddress;
        }
        if (config.routes) {
            iface['routes'] = config.routes.map(r => {
                const parts = [`to: ${r.to}`, `via: ${r.via}`];
                if (r.metric !== undefined) parts.push(`metric: ${r.metric}`);
                if (r['on-link']) parts.push(`on-link: true`);
                return parts.join(', ');
            }).join(' | ');
        }

        resultado.push(iface);
    });

    interfaces = resultado;

    const camposPrioritarios = [
        'name', 'type', 'dhcp4', 'dhcp6', 'address', 'gateway', 'mtu', 'hwaddress'
    ];

    const camposAvanzados = [
        'nameservers', 'dns-search', 'optional', 'link-local', 'accept-ra', 'critical', 'wakeonlan',
        'routes',
        'ipv6-address-generation', 'ipv6-mtu', 'ipv6-privacy',
        'dhcp-identifier', 'dhcp4-overrides', 'dhcp6-overrides',
        'match', 'set-name', 'renderer', 'description'
    ];

    const camposRestantes = camposAvanzados.filter(c => !camposPrioritarios.includes(c));
    const todosLosCampos = [...camposPrioritarios, ...camposRestantes];

    const total = todosLosCampos.length;
    const tercio = Math.ceil(total / 3);

    const grupo1 = todosLosCampos.slice(0, tercio);
    const grupo2 = todosLosCampos.slice(tercio, tercio * 2);
    const grupo3 = todosLosCampos.slice(tercio * 2);

    let html = `<table class="interfaz">`;

    interfaces.forEach((iface, index) => {
        const nombre = iface.name || `iface_${index}`;

        html += `<thead>
                    <tr style="background-color: #f0f0f0;">
                        <th colspan="${tercio + 1}">Interfaz: ${nombre}</th>
                    </tr>
                </thead>
                <tbody id="${nombre}">`;

        [grupo1, grupo2, grupo3].forEach((grupo, filaIndex) => {
            html += `<tr>`;
            html += `<td>${filaIndex === 0 ? `<strong>${nombre}</strong>` :
                        filaIndex === 1 ? `
                            <button onclick="editarInterfaz('${nombre}')">${lang.edit}</button>
                            <button onclick="guardarInterfaz('${nombre}')">${lang.save}</button>` : ''}</td>`;

            grupo.forEach(campo => {
                let valor = iface[campo];
                if (Array.isArray(valor)) {
                    valor = valor.join(', ');
                } else if (typeof valor === 'object' && valor !== null) {
                    valor = JSON.stringify(valor);
                }
                const mostrarValor = (valor !== undefined && valor !== null && valor !== '') ? valor : '';
                html += `<td data-campo="${campo}">
                            <strong>${campo}:</strong> 
                            <span contenteditable="false" class="valor">${mostrarValor}</span>
                         </td>`;
            });

            html += `</tr>`;
        });

        html += `</tbody>`;
    });

    html += `</table>`;

    // 👇 Se ha eliminado el bloque <tfoot> con los botones
    container.innerHTML = html;
}

function renderBondTable(interfaces) {
    const container = document.getElementById('tabla-bonds');
    if (!container || !interfaces.network) return;

    const grupoBonds = interfaces.network.bonds || {};
    const resultado = [];

    Object.entries(grupoBonds).forEach(([nombre, config]) => {
        const bond = {
            name: nombre,
            type: 'bonds',
            ...config
        };

        if (config.interfaces) {
            bond['interfaces'] = config.interfaces.join(', ');
        }

        resultado.push(bond);
    });

    interfaces = resultado;

    const camposPrioritarios = ['name', 'type', 'interfaces'];
    const camposAvanzados = [
        'dhcp4', 'dhcp6', 'addresses', 'gateway4', 'mtu', 'macaddress',
        'parameters', 'optional', 'link-local', 'accept-ra', 'critical', 'wakeonlan',
        'routes', 'ipv6-address-generation', 'ipv6-mtu', 'ipv6-privacy',
        'dhcp-identifier', 'dhcp4-overrides', 'dhcp6-overrides',
        'match', 'set-name', 'renderer', 'description'
    ];

    const camposRestantes = camposAvanzados.filter(c => !camposPrioritarios.includes(c));
    const todosLosCampos = [...camposPrioritarios, ...camposRestantes];

    const total = todosLosCampos.length;
    const tercio = Math.ceil(total / 3);

    const grupo1 = todosLosCampos.slice(0, tercio);
    const grupo2 = todosLosCampos.slice(tercio, tercio * 2);
    const grupo3 = todosLosCampos.slice(tercio * 2);

    const posiblesParametrosBond = [
        'mode', 'primary', 'mii-monitor-interval', 'up-delay', 'down-delay',
        'lacp-rate', 'min-links', 'transmit-hash-policy', 'ad-select',
        'fail-over-mac', 'arp-interval', 'arp-ip-target', 'arp-validation', 'resend-igmp'
    ];

    const opcionesModoBond = [
        '', 'balance-rr', 'active-backup', 'balance-xor',
        'broadcast', '802.3ad', 'balance-tlb', 'balance-alb'
    ];

    let html = `<table class="interfaz">`;

    interfaces.forEach((bond, index) => {
        const nombre = bond.name || `bond_${index}`;

        html += `<thead>
                    <tr style="background-color: #ffe0b2;">
                        <th colspan="${tercio + 1}">Bond: ${nombre}</th>
                    </tr>
                </thead>
                <tbody id="${nombre}">`;

        [grupo1, grupo2, grupo3].forEach((grupo, filaIndex) => {
            html += `<tr>`;
            html += `<td>${filaIndex === 0 ? `<strong>${nombre}</strong>` :
                        filaIndex === 1 ? `
                            <button onclick="editarInterfaz('${nombre}')">${lang.edit}</button>
                            <button onclick="guardarInterfaz('${nombre}')">${lang.save}</button>` : ''}</td>`;

            grupo.forEach(campo => {
                if (campo === 'parameters') return;

                let valor = bond[campo];
                let mostrarValor = '';

                if (Array.isArray(valor)) {
                    mostrarValor = valor.join(', ');
                } else if (typeof valor === 'object' && valor !== null) {
                    mostrarValor = JSON.stringify(valor);
                } else {
                    mostrarValor = (valor !== undefined && valor !== null && valor !== '') ? valor : '';
                }

                html += `<td data-campo="${campo}">
                            <strong>${campo}:</strong> 
                            <span contenteditable="false" class="valor">${mostrarValor}</span>
                         </td>`;
            });

            html += `</tr>`;

            if (filaIndex === 2) {
                html += `<tr><td colspan="${tercio + 1}">
                            <strong>parameters:</strong>
                            <table class="subtabla"><tr>`;

                posiblesParametrosBond.forEach((param, i) => {
                    const val = (bond.parameters && bond.parameters[param] !== undefined) ? bond.parameters[param] : '';

                    let inputHTML = `<span contenteditable="false" class="valor">${val}</span>`;
                    if (param === 'mode') {
                        inputHTML = `<select class="valor" disabled>`;
                        opcionesModoBond.forEach(opcion => {
                            const selected = opcion === val ? 'selected' : '';
                            inputHTML += `<option value="${opcion}" ${selected}>${opcion}</option>`;
                        });
                        inputHTML += `</select>`;
                    }

                    html += `
                        <td>
                            <strong>${param}:</strong><br>
                            ${inputHTML}
                        </td>`;

                    if ((i + 1) % 4 === 0 && i !== posiblesParametrosBond.length - 1) {
                        html += `</tr><tr>`;
                    }
                });

                html += `</tr></table></td></tr>`;
            }
        });

        html += `</tbody>`;
    });

    html += `</table>`;

    html += `
        <table class="interfaz">
            <tfoot>
                <tr>
                    <td colspan="${tercio + 1}">
                        <button onclick="crearBond()">${lang.create_bond}</button>
                        <button onclick="eliminarInterfazGlobal()">${lang.delete_interface}</button>
                    </td>
                </tr>
            </tfoot>
        </table>
    `;

    container.innerHTML = html;
}





function renderBridgeTable(interfaces) {
    const container = document.getElementById('tabla-bridges');
    if (!container || !interfaces.network) return;

    const grupoBridges = interfaces.network.bridges || {};
    const resultado = [];

    Object.entries(grupoBridges).forEach(([nombre, config]) => {
        const bridge = {
            name: nombre,
            type: 'bridge',
            ...config
        };

        if (config.interfaces) {
            bridge['interfaces'] = config.interfaces.join(', ');
        }

        resultado.push(bridge);
    });

    interfaces = resultado;

    const camposPrioritarios = ['name', 'type', 'interfaces'];
    const camposAvanzados = [
        'dhcp4', 'dhcp6', 'address', 'gateway', 'mtu', 'hwaddress',
        'nameservers', 'dns-search', 'optional', 'link-local', 'accept-ra', 'critical', 'wakeonlan',
        'routes', 'ipv6-address-generation', 'ipv6-mtu', 'ipv6-privacy',
        'dhcp-identifier', 'dhcp4-overrides', 'dhcp6-overrides',
        'match', 'set-name', 'renderer', 'description'
    ];

    const camposRestantes = camposAvanzados.filter(c => !camposPrioritarios.includes(c));
    const todosLosCampos = [...camposPrioritarios, ...camposRestantes];

    const total = todosLosCampos.length;
    const tercio = Math.ceil(total / 3);

    const grupo1 = todosLosCampos.slice(0, tercio);
    const grupo2 = todosLosCampos.slice(tercio, tercio * 2);
    const grupo3 = todosLosCampos.slice(tercio * 2);

    let html = `<table class="interfaz">`;

    interfaces.forEach((bridge, index) => {
        const nombre = bridge.name || `bridge_${index}`;

        html += `<thead>
                    <tr style="background-color: #e0f7fa;">
                        <th colspan="${tercio + 1}">Bridge: ${nombre}</th>
                    </tr>
                </thead>
                <tbody id="${nombre}">`;

        [grupo1, grupo2, grupo3].forEach((grupo, filaIndex) => {
            html += `<tr>`;
            html += `<td>${filaIndex === 0 ? `<strong>${nombre}</strong>` :
                        filaIndex === 1 ? `
                            <button onclick="editarInterfaz('${nombre}')">${lang.edit}</button>
                            <button onclick="guardarInterfaz('${nombre}')">${lang.save}</button>` : ''}</td>`;

            grupo.forEach(campo => {
                let valor = bridge[campo];
                if (Array.isArray(valor)) {
                    valor = valor.join(', ');
                } else if (typeof valor === 'object' && valor !== null) {
                    valor = JSON.stringify(valor);
                }
                const mostrarValor = (valor !== undefined && valor !== null && valor !== '') ? valor : '';
                html += `<td data-campo="${campo}">
                            <strong>${campo}:</strong> 
                            <span contenteditable="false" class="valor">${mostrarValor}</span>
                         </td>`;
            });

            html += `</tr>`;
        });

        html += `</tbody>`;
    });

    html += `</table>`;

    html += `
        <table class="interfaz">
            <tfoot>
                <tr>
                    <td colspan="${tercio + 1}">
                        <button onclick="crearBridge()">${lang.create_bridge}</button>
                        <button onclick="eliminarInterfazGlobal()">${lang.delete_interface}</button>
                    </td>
                </tr>
            </tfoot>
        </table>
    `;

    container.innerHTML = html;
}

function editarInterfaz(nombre) {
    const tbody = document.getElementById(nombre);
    if (!tbody) return;

    const spans = tbody.querySelectorAll('span.valor');
    spans.forEach(span => {
        const td = span.closest('td');
        const campo = td?.getAttribute('data-campo');
        if (campo === 'name' || campo === 'type') return;

        span.setAttribute('contenteditable', 'true');
        span.style.backgroundColor = '#ffffcc';
    });

    const selects = tbody.querySelectorAll('select.valor');
    selects.forEach(select => {
        select.disabled = false;
        select.style.backgroundColor = '#ffffcc';
    });
}


function guardarInterfaz(nombre) {
    const tbody = document.getElementById(nombre);
    if (!tbody) return;

    const datos = { name: nombre };

    // 🔒 Desactivar y recoger campos principales
    const celdas = tbody.querySelectorAll('td[data-campo]');
    celdas.forEach(td => {
        const campo = td.getAttribute('data-campo');
        if (campo === 'name' || campo === 'type') return;

        const span = td.querySelector('span.valor');
        const select = td.querySelector('select.valor');

        let valor = '';
        if (select) {
            valor = select.value?.trim() || '';
            select.disabled = true;
            select.style.backgroundColor = '';
        } else if (span) {
            valor = span.innerText?.trim() || '';
            span.setAttribute('contenteditable', 'false');
            span.style.backgroundColor = '';
        }

        if (valor !== '') {
            datos[campo] = valor;
        }
    });

    // 🔧 Desactivar y recoger parámetros si existen
    const parameters = {};
    const subtabla = tbody.querySelector('.subtabla');
    if (subtabla) {
        const celdasParametros = subtabla.querySelectorAll('td');

        celdasParametros.forEach(td => {
            const strong = td.querySelector('strong');
            if (!strong) return;

            const clave = strong.textContent.replace(':', '').trim();
            const span = td.querySelector('span.valor');
            const select = td.querySelector('select.valor');

            let valor = '';
            if (select) {
                valor = select.value?.trim() || '';
                select.disabled = true;
                select.style.backgroundColor = '';
            } else if (span) {
                valor = span.innerText?.trim() || '';
                span.setAttribute('contenteditable', 'false');
                span.style.backgroundColor = '';
            }

            if (clave && valor !== '') {
                parameters[clave] = valor;
            }
        });

        if (Object.keys(parameters).length > 0) {
            datos.parameters = parameters;
        }
    }

    // 📤 Enviar al servidor
    console.log('📦 JSON enviado al servidor:', datos);

    fetch('/interfaces/table_interfaces/update_interfaces.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(response => {
        console.log('✅ Guardado:', response);
        alert(`Interfaz "${nombre}" guardada correctamente.`);
    })
    .catch(err => {
        console.error('❌ Error al guardar:', err);
        alert(`Error al guardar la interfaz "${nombre}".`);
    });
}





// Invocar la tabla al cargar
loadInterfaceTable();






function crearBridge() {
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";

    const modal = document.createElement("div");
    modal.className = "modal-window";

    modal.innerHTML = `
        <h3>${lang["create_bridge"]}</h3>
        <label for="bridge-suffix">${lang["enter_interface_name"]}</label>
        <div class="modal-input-group">
            <span class="modal-prefix">br</span>
            <input type="text" id="bridge-suffix" class="modal-input" placeholder="001">
        </div>
        <div class="modal-actions">
            <button id="confirm-bridge" class="modal-button">${lang["ok"]}</button>
            <button id="cancel-bridge" class="modal-button cancel">${lang["cancel"]}</button>
        </div>
    `;

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    document.getElementById("cancel-bridge").onclick = () => {
        document.body.removeChild(overlay);
    };

    document.getElementById("confirm-bridge").onclick = () => {
        const suffix = document.getElementById("bridge-suffix").value.trim();
        if (!suffix) {
            alert(lang["invalid_name"]);
            return;
        }

        const name = "br" + suffix;

        const data = {
            name: name,
            auto: true,
            family: "inet",
            method: "static",
            options: []
        };


        fetch("/interfaces/table_interfaces/create_bridge.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
            alert(response.mensaje || lang["ok"]);
            document.body.removeChild(overlay);
            loadInterfaceTable(); // 🔄 Recarga la tabla
        })
        .catch(err => {
            console.error("Error al crear bridge:", err);
            alert(lang["connection_error"]);
            document.body.removeChild(overlay);
        });
    };
}



function crearBond() {
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";

    const modal = document.createElement("div");
    modal.className = "modal-window";

    modal.innerHTML = `
        <h3>${lang["create_bond"]}</h3>
        <label for="bond-suffix">${lang["enter_interface_name"]}</label>
        <div class="modal-input-group">
            <span class="modal-prefix">bond</span>
            <input type="text" id="bond-suffix" class="modal-input" placeholder="001">
        </div>
        <div class="modal-actions">
            <button id="confirm-bond" class="modal-button">${lang["ok"]}</button>
            <button id="cancel-bond" class="modal-button cancel">${lang["cancel"]}</button>
        </div>
    `;

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    document.getElementById("cancel-bond").onclick = () => {
        document.body.removeChild(overlay);
    };

    document.getElementById("confirm-bond").onclick = () => {
        const suffix = document.getElementById("bond-suffix").value.trim();
        if (!suffix) {
            alert(lang["invalid_name"]);
            return;
        }

        const name = "bond" + suffix;

        const data = {
            name: name,
            auto: true,
            family: "inet",
            method: "static",
            options: []
        };

        fetch("/interfaces/table_interfaces/create_bond.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
            alert(response.mensaje || lang["ok"]);
            document.body.removeChild(overlay);
            loadInterfaceTable(); // 🔄 Recarga la tabla
        })
        .catch(err => {
            console.error("Error al crear bond:", err);
            alert(lang["connection_error"]);
            document.body.removeChild(overlay);
        });
    };
}









function eliminarInterfazGlobal() {
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";

    const modal = document.createElement("div");
    modal.className = "modal-window";

    modal.innerHTML = `
        <h3>${lang["delete_interface"]}</h3>
        <label for="interface-name">${lang["enter_interface_name"]}</label>
        <input type="text" id="interface-name" class="modal-input" placeholder="br0 / bond1">
        <div class="modal-actions">
            <button id="confirm-delete" class="modal-button">${lang["ok"]}</button>
            <button id="cancel-delete" class="modal-button cancel">${lang["cancel"]}</button>
        </div>
    `;

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    document.getElementById("cancel-delete").onclick = () => {
        document.body.removeChild(overlay);
    };

    document.getElementById("confirm-delete").onclick = () => {
        const iface = document.getElementById("interface-name").value.trim();

        // ✅ Validación: empieza por br o bond, sin importar lo que venga después
        if (!iface.match(/^(br|bond).*/)) {
            alert(lang["invalid_interface_name"]);
            return;
        }

        fetch("/interfaces/table_interfaces/delete_interfaces.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ interface: iface })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(lang["interface_deleted"]);
                loadInterfaceTable();
            } else {
                alert(lang["delete_failed"] + ": " + data.error);
            }
        })
        .catch(err => {
            alert(lang["delete_failed"] + ": " + err.message);
        })
        .finally(() => {
            document.body.removeChild(overlay);
        });
    };
}

