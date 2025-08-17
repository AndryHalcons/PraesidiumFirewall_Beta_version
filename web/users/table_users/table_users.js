(function () {
    const container = document.createElement("div");
    container.id = "users-container";
    container.classList.add("users-container");

    const table = document.createElement("table");
    table.classList.add("users-table");
    table.innerHTML = `
        <thead>
            <tr>
                <th>${LANG.username}</th>
                <th>${LANG.password}</th>
                <th>${LANG.role}</th>
                <th>${LANG.language}</th>
                <th>${LANG.actions}</th>
            </tr>
        </thead>
        <tbody id="users-table-body"></tbody>
    `;

    const addUserBtn = document.createElement("button");
    addUserBtn.id = "add-user-btn";
    addUserBtn.classList.add("add-user-btn");
    addUserBtn.textContent = "" + LANG.add_user;

    container.appendChild(table);
    container.appendChild(addUserBtn);
    const placeholder = document.getElementById("users-container-placeholder");
    if (placeholder) {
        placeholder.appendChild(container);
    } else {
        console.warn("No se encontró el contenedor #users-container-placeholder");
    }

    const tableBody = container.querySelector("#users-table-body");

    function loadUsers() {
        fetch("/users/table_users/get_users.php")
            .then(response => response.json())
            .then(users => renderTable(users))
            .catch(error => console.error("Error al cargar usuarios:", error));
    }

    loadUsers(); // Cargar al inicio

    function renderTable(users) {
        tableBody.innerHTML = "";
        users.forEach((user, index) => {
            const row = document.createElement("tr");

            row.innerHTML = `
                <td><input type="text" value="${user.user_name}" data-index="${index}" data-field="user_name" disabled></td>
                <td><input type="password" value="${user.user_pass}" data-index="${index}" data-field="user_pass" disabled></td>
                <td><input type="text" value="${user.user_rol}" data-index="${index}" data-field="user_rol" disabled></td>
                <td><input type="text" value="${user.user_languaje}" data-index="${index}" data-field="user_languaje" disabled></td>
                <td>
                    <button class="edit-btn" data-index="${index}"> ${LANG.edit}</button>
                    <button class="save-btn" data-index="${index}"> ${LANG.save}</button>
                    <button class="delete-btn" data-index="${index}"> ${LANG.delete}</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    tableBody.addEventListener("click", (e) => {
        const index = e.target.dataset.index;

        if (e.target.classList.contains("edit-btn")) {
            ["user_name", "user_pass", "user_rol", "user_languaje"].forEach(field => {
                const input = container.querySelector(`input[data-index="${index}"][data-field="${field}"]`);
                input.disabled = false;
            });
        }

        if (e.target.classList.contains("save-btn")) {
            const userData = {};
            ["user_name", "user_pass", "user_rol", "user_languaje"].forEach(field => {
                const input = container.querySelector(`input[data-index="${index}"][data-field="${field}"]`);
                userData[field] = input.value;
                input.disabled = true;
            });

            const isNew = e.target.dataset.new === "true";

            fetch("/users/table_users/update_users.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: isNew ? "add" : "update",
                    index: isNew ? undefined : index,
                    user: userData
                })
            })
            .then(res => res.json())
            .then(data => {
                console.log(isNew ? LANG.user_added : LANG.user_updated, data);
                loadUsers(); // Recargar tabla sin redireccionar
            })
            .catch(err => console.error("Error al guardar usuario:", err));
        }

        if (e.target.classList.contains("delete-btn")) {
            const input = container.querySelector(`input[data-index="${index}"][data-field="user_name"]`);
            const user_name = input ? input.value : null;

            if (user_name && confirm(`${LANG.confirm_delete} "${user_name}"?`)) {
                fetch("/users/table_users/update_users.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        action: "delete",
                        user_name: user_name
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log(LANG.user_deleted, data);
                    loadUsers(); // Recargar tabla sin redireccionar
                })
                .catch(err => console.error("Error al eliminar usuario:", err));
            }
        }
    });

    addUserBtn.addEventListener("click", () => {
        const index = "new-" + Date.now();

        const row = document.createElement("tr");
        row.innerHTML = `
            <td><input type="text" value="" data-index="${index}" data-field="user_name"></td>
            <td><input type="password" value="" data-index="${index}" data-field="user_pass"></td>
            <td><input type="text" value="" data-index="${index}" data-field="user_rol"></td>
            <td><input type="text" value="" data-index="${index}" data-field="user_languaje"></td>
            <td>
                <button class="save-btn" data-index="${index}" data-new="true">💾 ${LANG.save}</button>
                <button class="cancel-btn" data-index="${index}">🗑️ ${LANG.cancel}</button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    tableBody.addEventListener("click", (e) => {
        if (e.target.classList.contains("cancel-btn")) {
            const row = e.target.closest("tr");
            if (row) row.remove();
        }
    });
})();
