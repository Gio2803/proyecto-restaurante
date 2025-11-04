<?php
include("conexion.php");

if ($conexion) {
    echo "✅ Conexión exitosa<br>";
    
    // Verificar si la tabla usuarios existe
    try {
        $stmt = $conexion->query("SELECT * FROM usuarios LIMIT 1");
        echo "✅ Tabla usuarios existe<br>";
        
        // Mostrar algunos usuarios (opcional)
        $usuarios = $conexion->query("SELECT id_usuario, usuario, nombre FROM usuarios WHERE fechabaja IS NULL");
        echo "<br>Usuarios en la base de datos:<br>";
        foreach ($usuarios as $usuario) {
            echo "ID: " . $usuario['id_usuario'] . " - Usuario: " . $usuario['usuario'] . " - Nombre: " . $usuario['nombre'] . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error con la tabla usuarios: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Error en la conexión";
}
?>