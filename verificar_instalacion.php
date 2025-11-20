<?php
// verificar_instalacion.php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

try {
    // Verificar si existen las tablas necesarias
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $necesita_instalacion = ($result['count'] == 0);
    
    echo json_encode([
        'necesita_instalacion' => $necesita_instalacion,
        'total_menu_items' => $result['count']
    ]);
    
} catch (PDOException $e) {
    // Si hay error, probablemente las tablas no existen
    echo json_encode([
        'necesita_instalacion' => true,
        'error' => $e->getMessage()
    ]);
}
?>