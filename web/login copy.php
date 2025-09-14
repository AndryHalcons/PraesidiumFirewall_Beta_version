<?php
session_start();
// Realiza el proceso de login // Perform the login process
$jsonPath = '/var/www/config_running/users.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $hashedPassword = hash('sha512', $password);

    if (file_exists($jsonPath)) {
        $data = json_decode(file_get_contents($jsonPath), true);

        // Verifica que el JSON tenga la clave 'table_users' y sea un array
        // Check that the JSON has the 'table_users' key and it's an array
        if (isset($data['table_users']) && is_array($data['table_users'])) {
            foreach ($data['table_users'] as $user) {
                if ($user['user_name'] === $username && $user['user_pass'] === $hashedPassword) {
                    // Asigna los datos de sesión usando los nombres correctos
                    // Assign session data using correct key names
                    $_SESSION['username'] = $user['user_name'];
                    $_SESSION['role'] = $user['user_role'];
                    $_SESSION['language'] = $user['user_language'];
                    header('Location: mainpage.php');
                    exit;
                }
            }
        }
    }

    // Si no se encuentra el usuario, redirige con error
    // If user not found, redirect with error
    $_SESSION['error'] = 'Incorrect username or password.';
    header('Location: index.php');
    exit;
}
