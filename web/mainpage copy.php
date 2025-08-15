
<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Puedes usar los datos de sesión aquí
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$language = $_SESSION['language'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- Encabezado superior -->
    <div class="header-top">
        <div class="header-left">
            <h1>Praesidium Firewall</h1>
            <h2>Bienvenido, <?php echo htmlspecialchars($username); ?>!</h2>
            <div class="user-info">
                <p>Rol: <?php echo htmlspecialchars($role); ?></p>
                <p>Idioma: <?php echo htmlspecialchars($language); ?></p>
            </div>
        </div>
    </div>

    <!-- Menú horizontal debajo del encabezado -->
    <div class="top-menu">
        <a href="#">Inicio</a>
        <a href="#">Monitor</a>
        <a href="#">Usuarios</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <!-- Menú lateral vertical -->
    <div class="sidebar">
        <a href="#">Dashboard</a>
        <a href="#">Reglas</a>
        <a href="#">Logs</a>
        <a href="#">Configuración</a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <p>Contenido aquí...</p>
    </div>

</body>
</html>
