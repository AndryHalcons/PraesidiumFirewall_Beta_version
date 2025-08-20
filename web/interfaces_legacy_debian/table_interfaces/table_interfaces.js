function loadInterfaceTable() {
    fetch('/interfaces/table_interfaces/get_interfaces.php')
        .then(response => response.json())
        .then(data => renderInterfaceTable(data.interfaces || []))
        .catch(err => {
            console.error('Error al cargar el JSON:', err);
        });
}

function renderInterfaceTable(interfaces) {
    const container = document.getElementById('tabla-interfaces');
    if (!container) return;

    const camposPrioritarios = [
        'name', 'auto', 'family', 'method',
        'address', 'netmask', 'gateway', 'broadcast', 'network'
    ];

    const camposAvanzados = [
        'hwaddress', 'hostname', 'domain', 'source',
        'pre-up', 'up', 'post-up', 'down', 'post-down',
        'vlan-raw-device',
        'bridge_ports', 'bridge_fd', 'bridge_maxwait', 'bridge_stp',
        'bond-mode', 'bond-miimon', 'bond-slaves', 'bond-primary', 'bond-xmit_hash_policy',
        'wireless-essid', 'wireless-mode', 'wireless-key', 'wpa-ssid', 'wpa-psk',
        'dns-nameservers', 'dns-search', 'mtu', 'metric', 'scope',
        'accept_ra', 'autoconf', 'privext'
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
                if (valor === undefined && iface.options) {
                    valor = iface.options[campo];
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
                        <button onclick="crearBond()">${lang.create_bond}</button>
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

    const valores = tbody.querySelectorAll('span.valor');
    valores.forEach(span => {
        const td = span.closest('td');
        const campo = td?.getAttribute('data-campo');
        if (campo === 'name') return;

        span.setAttribute('contenteditable', 'true');
        span.style.backgroundColor = '#ffffcc';
    });
}

function guardarInterfaz(nombre) {
    const tbody = document.getElementById(nombre);
    if (!tbody) return;

    const celdas = tbody.querySelectorAll('td[data-campo]');

    const datos = {
        name: nombre,
        auto: null,
        family: null,
        method: null,
        options: {}
    };

    const camposFuera = ['auto', 'family', 'method'];

    const camposOptions = [
        'address', 'netmask', 'gateway', 'broadcast', 'network',
        'hwaddress', 'hostname', 'domain', 'source',
        'pre-up', 'up', 'post-up', 'down', 'post-down',
        'vlan-raw-device',
        'bridge_ports', 'bridge_fd', 'bridge_maxwait', 'bridge_stp',
        'bond-mode', 'bond-miimon', 'bond-slaves', 'bond-primary', 'bond-xmit_hash_policy',
        'wireless-essid', 'wireless-mode', 'wireless-key', 'wpa-ssid', 'wpa-psk',
        'dns-nameservers', 'dns-search', 'mtu', 'metric', 'scope',
        'accept_ra', 'autoconf', 'privext'
    ];

    celdas.forEach(td => {
        const campo = td.getAttribute('data-campo');
        const span = td.querySelector('span.valor');
        if (!span) return;

        const valor = span.innerText.trim();

        if (valor !== '') {
            if (camposFuera.includes(campo)) {
                datos[campo] = valor;
            } else if (camposOptions.includes(campo)) {
                datos.options[campo] = valor;
            }
        }

        span.setAttribute('contenteditable', 'false');
        span.style.backgroundColor = '';
    });

    camposFuera.forEach(campo => {
        if (datos[campo] === null) {
            delete datos[campo];
        }
    });

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


        fetch("/interfaces/table_interfaces/update_interfaces.php", {
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

        fetch("/interfaces/table_interfaces/update_interfaces.php", {
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

