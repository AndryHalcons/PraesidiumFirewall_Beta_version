fetch('/interfaces/table_interfaces/get_interfaces.php')
    .then(response => response.json())
    .then(data => {
        const interfaces = data.interfaces || [];
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

            function renderFila(grupo, filaIndex) {
                html += `<tr>`;

                if (filaIndex === 0) {
                    html += `<td><strong>${nombre}</strong></td>`;
                } else if (filaIndex === 1) {
                    html += `<td>
                                <button onclick="editarInterfaz('${nombre}')">Editar</button>
                                <button onclick="guardarInterfaz('${nombre}')">Guardar</button>
                             </td>`;
                } else {
                    html += `<td></td>`;
                }

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
            }

            renderFila(grupo1, 0);
            renderFila(grupo2, 1);
            renderFila(grupo3, 2);

            html += `</tbody>`;
        });

        html += `</table>`;
        container.innerHTML = html;
    })
    .catch(err => {
        console.error('Error al cargar el JSON:', err);
    });

function editarInterfaz(nombre) {
    const tbody = document.getElementById(nombre);
    if (!tbody) return;

    const valores = tbody.querySelectorAll('span.valor');
    valores.forEach(span => {
        const td = span.closest('td');
        const campo = td?.getAttribute('data-campo');
        if (campo === 'name') return; // ❌ No editar el campo "name"

        span.setAttribute('contenteditable', 'true');
        span.style.backgroundColor = '#ffffcc';
    });
}




function guardarInterfaz(nombre) {
    const tbody = document.getElementById(nombre);
    if (!tbody) return;

    const celdas = tbody.querySelectorAll('td[data-campo]');

    // Inicializamos los campos en el orden correcto
    const datos = {
        name: nombre,
        auto: null,
        family: null,
        method: null,
        options: {}
    };

    // Campos que deben ir fuera de "options"
    const camposFuera = ['auto', 'family', 'method'];

    // Campos válidos para "options" según ifupdown2
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

        // Solo añadimos al JSON si tiene valor
        if (valor !== '') {
            if (camposFuera.includes(campo)) {
                datos[campo] = valor;
            } else if (camposOptions.includes(campo)) {
                datos.options[campo] = valor;
            }
        }

        // 🔧 Siempre desactivamos edición, tenga valor o no
        span.setAttribute('contenteditable', 'false');
        span.style.backgroundColor = '';
    });

    // Eliminamos campos vacíos para que no aparezcan como null
    ['auto', 'family', 'method'].forEach(campo => {
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
