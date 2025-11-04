<?php
session_start();

// Incluir conexión y verificar
include("conexion.php");

// Verificar si la conexión se estableció correctamente
if (!$conexion) {
    echo "error";
    error_log("Error: Conexión a la base de datos no disponible");
    exit();
}

if ($_POST['funcion'] == 'iniciar') {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);
    
    // Validaciones básicas
    if (empty($usuario) || empty($contrasena)) {
        echo "error";
        exit();
    }
    
    try {
        // Consulta usando la tabla usuarios con PostgreSQL
        $sql = "SELECT * FROM usuarios WHERE usuario = :usuario AND contrasena = :contrasena AND fechabaja IS NULL";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':contrasena', $contrasena);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['SISTEMA']['id_usuario'] = $row['id_usuario'];
            $_SESSION['SISTEMA']['rol'] = $row['id_rol'];
            $_SESSION['SISTEMA']['usuario'] = $row['usuario'];
            $_SESSION['SISTEMA']['nombre'] = $row['nombre'];
            $_SESSION['SISTEMA']['telefono'] = $row['telefono'];
            echo "success";
        } else {
            echo "error";
        }
    } catch (PDOException $e) {
        echo "error";
        error_log("Error en validar.php: " . $e->getMessage());
    }
    exit();
}
?>