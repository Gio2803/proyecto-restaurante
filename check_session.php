<?php
// check_session.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['SISTEMA']['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// También establecer la variable individual para compatibilidad
if (!isset($_SESSION['id_usuario']) && isset($_SESSION['SISTEMA']['id_usuario'])) {
    $_SESSION['id_usuario'] = $_SESSION['SISTEMA']['id_usuario'];
}
?>