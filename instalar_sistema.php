<?php
// instalar_sistema.php
session_start();
require_once 'conexion.php';

// Verificar que sea el administrador
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 'Administrador') {
    die('Acceso denegado');
}

// Elementos del menú por defecto
$menu_items = [
    // Elementos principales
    ['nombre' => 'Croquis', 'url' => 'croquis.php', 'icono' => 'bi bi-geo-alt-fill', 'orden' => 1],
    ['nombre' => 'Clientes', 'url' => 'clientes.php', 'icono' => 'bi bi-people-fill', 'orden' => 2],
    ['nombre' => 'Mesas', 'url' => 'mesas.php', 'icono' => 'bi bi-people-fill', 'orden' => 3],
    ['nombre' => 'Productos', 'url' => '#', 'icono' => 'bi bi-box-seam-fill', 'orden' => 4],
    ['nombre' => 'Cocina', 'url' => 'cocina.php', 'icono' => 'bi bi-fire', 'orden' => 5],
    ['nombre' => 'Barra', 'url' => 'barra.php', 'icono' => 'bi bi-cup-straw', 'orden' => 6],
    ['nombre' => 'Ventas', 'url' => '#', 'icono' => 'bi bi-cash-coin', 'orden' => 7],
    ['nombre' => 'Mi Cuenta', 'url' => '#', 'icono' => 'bi bi-person-circle', 'orden' => 8],

    // Subelementos de Productos
    ['nombre' => 'Menu/Platillos/Bebidas', 'url' => 'menu_platillos.php', 'icono' => 'bi bi-circle', 'orden' => 1, 'parent' => 'Productos'],
    ['nombre' => 'Categorías', 'url' => 'categorias.php', 'icono' => 'bi bi-tags', 'orden' => 2, 'parent' => 'Productos'],
    ['nombre' => 'Unidades de Medida', 'url' => 'unidades_medida.php', 'icono' => 'bi bi-rulers', 'orden' => 3, 'parent' => 'Productos'],

    // Subelementos de Ventas
    ['nombre' => 'Estadisticas de Ventas', 'url' => 'estadisticas.php', 'icono' => 'bi bi-credit-card', 'orden' => 1, 'parent' => 'Ventas'],
    ['nombre' => 'Historial de Ventas', 'url' => 'historial_ventas.php', 'icono' => 'bi bi-receipt', 'orden' => 2, 'parent' => 'Ventas'],

    // Subelementos de Mi Cuenta
    ['nombre' => 'Usuarios', 'url' => 'usuarios.php', 'icono' => 'bi bi-people', 'orden' => 1, 'parent' => 'Mi Cuenta'],
    ['nombre' => 'Roles', 'url' => 'roles.php', 'icono' => 'bi bi-shield-lock', 'orden' => 2, 'parent' => 'Mi Cuenta'],
    ['nombre' => 'Permisos Menú', 'url' => 'permisos_menu.php', 'icono' => 'bi bi-shield-check', 'orden' => 3, 'parent' => 'Mi Cuenta'],
];

try {
    // Verificar si ya existen elementos del menú
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        echo "<h2>Instalando Sistema de Menús...</h2>";

        // Insertar elementos principales
        foreach ($menu_items as $item) {
            if (!isset($item['parent'])) {
                $stmt = $pdo->prepare("INSERT INTO menu_items (nombre, url, icono, orden) VALUES (?, ?, ?, ?)");
                $stmt->execute([$item['nombre'], $item['url'], $item['icono'], $item['orden']]);
                echo "✅ Insertado: " . $item['nombre'] . "<br>";
            }
        }

        // Insertar subelementos
        foreach ($menu_items as $item) {
            if (isset($item['parent'])) {
                $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE nombre = ?");
                $stmt->execute([$item['parent']]);
                $parent = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($parent) {
                    $stmt = $pdo->prepare("INSERT INTO menu_items (nombre, url, icono, orden, parent_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$item['nombre'], $item['url'], $item['icono'], $item['orden'], $parent['id']]);
                    echo "✅ Insertado submenú: " . $item['nombre'] . " → " . $item['parent'] . "<br>";
                }
            }
        }

        // Asignar todos los permisos al usuario administrador (id_usuario = 1)
        $stmt = $pdo->query("SELECT id FROM menu_items");
        $todos_los_items = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($todos_los_items as $item_id) {
            $stmt = $pdo->prepare("INSERT INTO permisos_menu (id_usuario, menu_item_id, activo) VALUES (1, ?, true)");
            $stmt->execute([$item_id]);
        }

        echo "<br><strong>✅ Sistema instalado correctamente!</strong><br>";
        echo "<p>Se han creado todos los elementos del menú y asignado permisos al usuario administrador (ID: 1)</p>";
        echo "<a href='croquis.php' class='btn btn-primary mt-3'>Ir al Croquis</a>";

    } else {
        echo "ℹ️ El sistema ya estaba instalado.<br>";
        echo "<a href='croquis.php' class='btn btn-primary mt-3'>Ir al Croquis</a>";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>