<?php
session_start();

// Elimina todas las variables de sesión
// Remove all session variables
session_unset();

// Destruye la sesión actual
// Destroy the current session
session_destroy();

// Redirige al formulario de login
// Redirect to the login page
header('Location: index.php');
exit;
