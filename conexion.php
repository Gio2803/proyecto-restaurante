<?php
$host = "localhost";
$port = "5432";
$dbname = "restaurant";
$user = "postgres";
$password = "12345678";

$conexion = null;

try {
    $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "❌ Error en la conexión: " . $e->getMessage();
}
?>