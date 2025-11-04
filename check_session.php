<?php

if (!isset($_SESSION['SISTEMA']['id_usuario'])) {
    header("Location: login.php");
    exit();
}
?>