<?php
header("Content-Type: application/json");

$jsonFile = "/var/www/config/users.json";
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["action"])) {
    echo json_encode(["error" => "Acción no especificada"]);
    exit;
}

if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
}
$users = json_decode(file_get_contents($jsonFile), true);

// Añadir usuario
if ($input["action"] === "add") {
    $newUser = $input["user"] ?? null;

    if (!$newUser || !isset($newUser["user_name"], $newUser["user_pass"])) {
        echo json_encode(["error" => "Datos de usuario inválidos"]);
        exit;
    }

    foreach ($users as $u) {
        if ($u["user_name"] === $newUser["user_name"]) {
            echo json_encode(["error" => "El usuario ya existe"]);
            exit;
        }
    }

    // Hashear contraseña
    $newUser["user_pass"] = hash("sha512", $newUser["user_pass"]);

    $users[] = $newUser;
    file_put_contents($jsonFile, json_encode($users, JSON_PRETTY_PRINT));
    echo json_encode(["success" => true, "message" => "Usuario añadido"]);
    exit;
}

// Actualizar usuario
if ($input["action"] === "update") {
    $index = $input["index"] ?? null;
    $updatedUser = $input["user"] ?? null;

    if (!is_numeric($index) || !$updatedUser || !isset($updatedUser["user_name"], $updatedUser["user_pass"])) {
        echo json_encode(["error" => "Datos de actualización inválidos"]);
        exit;
    }

    if (!isset($users[$index])) {
        echo json_encode(["error" => "Usuario no encontrado"]);
        exit;
    }

    // Verificar si la contraseña ha cambiado
    $oldPass = $users[$index]["user_pass"];
    $newPass = $updatedUser["user_pass"];

    if ($newPass !== $oldPass) {
        $updatedUser["user_pass"] = hash("sha512", $newPass);
    }

    $users[$index] = $updatedUser;
    file_put_contents($jsonFile, json_encode($users, JSON_PRETTY_PRINT));
    echo json_encode(["success" => true, "message" => "Usuario actualizado"]);
    exit;
}

// Eliminar usuario
if ($input["action"] === "delete") {
    $userName = $input["user_name"] ?? null;

    if (!$userName) {
        echo json_encode(["error" => "Nombre de usuario no especificado"]);
        exit;
    }

    $users = array_filter($users, fn($u) => $u["user_name"] !== $userName);
    $users = array_values($users);

    file_put_contents($jsonFile, json_encode($users, JSON_PRETTY_PRINT));
    echo json_encode(["success" => true, "message" => "Usuario eliminado"]);
    exit;
}

echo json_encode(["error" => "Acción no válida"]);
exit;
