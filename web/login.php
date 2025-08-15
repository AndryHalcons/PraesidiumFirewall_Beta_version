<?php
session_start();

$jsonPath = '/var/www/config/users.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $hashedPassword = hash('sha512', $password);

    if (file_exists($jsonPath)) {
        $users = json_decode(file_get_contents($jsonPath), true);

        foreach ($users as $user) {
            if ($user['user_name'] === $username && $user['user_pass'] === $hashedPassword) {
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['role'] = $user['user_rol'];
                $_SESSION['language'] = $user['user_languaje'];
                header('Location: mainpage.php');
                exit;
            }
        }
    }

    $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
    header('Location: index.php');
    exit;
}
