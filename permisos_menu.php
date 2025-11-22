<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'permisos_menu.php';

try {
    // Primero verificar si las tablas de permisos existen
    $stmt = $conexion->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'permisos_menu'
        ) as tabla_existe
    ");
    $stmt->execute();
    $tabla_existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tabla_existe['tabla_existe'] == 't' || $tabla_existe['tabla_existe'] === true) {
        // La tabla existe, verificar permisos
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as tiene_permiso 
            FROM permisos_menu pm 
            INNER JOIN menu_items mi ON pm.menu_item_id = mi.id 
            WHERE pm.id_usuario = ? AND mi.url = ? AND pm.activo = true
        ");
        $stmt->execute([$_SESSION['id_usuario'], $pagina_actual]);
        $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$permiso || $permiso['tiene_permiso'] == 0) {
            // Si no tiene permiso específico, verificar si es administrador
            if ($_SESSION['SISTEMA']['rol'] != 1) {
                header('Location: acceso_denegado.php');
                exit;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error en verificación de permisos: " . $e->getMessage());
}

$pdo = $conexion; // 👈 esto crea un alias para evitar errores

// Verificar que el usuario tenga permisos de administrador
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtener parámetro de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Obtener lista de usuarios (activos, no dados de baja) con filtro de búsqueda
$usuarios = [];
$sql_usuarios = "SELECT id_usuario, nombre, usuario FROM usuarios WHERE fechabaja IS NULL";

if (!empty($busqueda)) {
    $sql_usuarios .= " AND (nombre ILIKE :busqueda OR usuario ILIKE :busqueda)";
}

$sql_usuarios .= " ORDER BY nombre";

$stmt = $conexion->prepare($sql_usuarios);

if (!empty($busqueda)) {
    $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
} else {
    $stmt->execute();
}

if ($stmt) {
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener elementos del menú
$menu_items = [];
$stmt = $conexion->query("SELECT id, nombre, url, icono, parent_id FROM menu_items WHERE activo = true ORDER BY orden, nombre");
if ($stmt) {
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar en estructura jerárquica
    $menu_estructura = [];
    foreach ($menu_items as $item) {
        if ($item['parent_id'] === null) {
            $menu_estructura[$item['id']] = $item;
            $menu_estructura[$item['id']]['subitems'] = [];
        }
    }
    
    foreach ($menu_items as $item) {
        if ($item['parent_id'] !== null && isset($menu_estructura[$item['parent_id']])) {
            $menu_estructura[$item['parent_id']]['subitems'][] = $item;
        }
    }
}

// Procesar formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    
    // Eliminar permisos existentes para este usuario
    $stmt = $pdo->prepare("DELETE FROM permisos_menu WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    
    // Insertar nuevos permisos
    if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
        $stmt = $pdo->prepare("INSERT INTO permisos_menu (id_usuario, menu_item_id, activo) VALUES (?, ?, true)");
        foreach ($_POST['permisos'] as $menu_item_id) {
            if (is_numeric($menu_item_id)) {
                $stmt->execute([$id_usuario, $menu_item_id]);
            }
        }
    }
    
    $_SESSION['mensaje'] = "Permisos actualizados correctamente";
    header("Location: permisos_menu.php?usuario_id=" . $id_usuario);
    exit;
}

// Obtener permisos cuando se selecciona un usuario
$permisos_actuales = [];
if (isset($_GET['usuario_id'])) {
    $usuario_seleccionado = $_GET['usuario_id'];
    $stmt = $conexion->prepare("SELECT menu_item_id FROM permisos_menu WHERE id_usuario = ? AND activo = true");
    $stmt->execute([$usuario_seleccionado]);
    $permisos_actuales = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $usuario_seleccionado = null;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos de Menú</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #67C090;
            --secondary-color: #DDF4E7;
            --danger-color: #124170;
            --light-color: #26667F;
        }

        body {
            background-color: var(--secondary-color) !important;
            padding-top: 0;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .container-custom {
            max-width: 1400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .banner {
            background-color: var(--danger-color);
            color: white;
            padding: 15px;
            border-radius: 15px 15px 0 0;
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            background-color: var(--light-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        .search-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .search-icon {
            color: var(--primary-color);
        }

        .user-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-top: 10px;
            background: white;
        }

        .user-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-item:hover {
            background-color: var(--secondary-color);
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        .user-name {
            font-weight: 600;
        }

        .user-username {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .user-item.active .user-username {
            color: #e9ecef;
        }

        .results-count {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 10px;
            color: var(--primary-color);
        }

        /* Estilos específicos para permisos */
        .menu-item {
            margin-bottom: 10px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .submenu-item {
            margin-left: 30px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .parent-item {
            font-weight: bold;
            background-color: #e9ecef;
            border-left: 4px solid var(--primary-color);
        }

        .menu-checkbox {
            transform: scale(1.2);
            margin-right: 10px;
        }

        .form-check-label {
            font-size: 1rem;
            cursor: pointer;
        }

        /* Estilos del sidebar integrados */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background-color: var(--danger-color);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar-header {
            padding: 15px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sidebar-brand span {
            transition: opacity 0.3s ease;
            font-size: 0.9rem;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            display: none;
        }

        .sidebar-nav {
            padding: 10px 0;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            color: white !important;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--primary-color) !important;
            border-left-color: var(--primary-color);
        }

        .nav-link.active {
            background-color: rgba(103, 192, 144, 0.2);
            color: var(--primary-color) !important;
            border-left-color: var(--primary-color);
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .nav-link span {
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            display: none;
        }

        .dropdown-menu {
            background-color: var(--light-color);
            border: none;
            border-radius: 0 8px 8px 0;
            margin-left: 8px;
            min-width: 180px;
        }

        .dropdown-item {
            color: white !important;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--primary-color) !important;
        }

        .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .dropdown-item.text-danger:hover {
            color: #bd2130 !important;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .dropdown-toggle::after {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }

        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background: var(--primary-color);
            border: none;
            border-radius: 4px;
            color: white;
            padding: 6px 10px;
            cursor: pointer;
            display: none;
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: 220px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background-color: var(--secondary-color);
            padding: 15px;
        }

        .main-content.expanded {
            margin-left: 60px;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 220px;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .main-content.mobile-expanded {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .sidebar.collapsed {
                transform: translateX(-100%);
            }

            .sidebar.collapsed.mobile-open {
                transform: translateX(0);
                width: 220px;
            }

            .submenu-item {
                margin-left: 15px;
            }
        }

        .sidebar::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'menu.php'; ?>

    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <div class="container container-custom">
            <div class="banner text-center">
                <h1><i class="bi bi-shield-lock"></i> Gestión de Permisos de Menú</h1>
            </div>

            <div class="table-container">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- BUSCADOR DE USUARIOS -->
                <div class="search-box">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="busqueda" class="form-label">
                                <i class="bi bi-search me-1"></i>Buscar Usuario
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                       placeholder="Escribe el nombre o usuario..." 
                                       value="<?php echo htmlspecialchars($busqueda); ?>"
                                       autocomplete="off">
                                <?php if (!empty($busqueda)): ?>
                                    <button class="btn btn-outline-danger" type="button" onclick="limpiarBusqueda()">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div id="resultsInfo">
                                <?php if (!empty($busqueda)): ?>
                                    <div class="results-count">
                                        <?php echo count($usuarios); ?> usuario(s) encontrado(s) para "<?php echo htmlspecialchars($busqueda); ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SPINNER DE CARGA -->
                    <div class="loading-spinner" id="loadingSpinner">
                        <i class="bi bi-arrow-repeat spinner"></i> Buscando...
                    </div>

                    <!-- LISTA DE RESULTADOS -->
                    <div id="userResultsContainer">
                        <?php if (!empty($busqueda) && !empty($usuarios) && !$usuario_seleccionado): ?>
                            <div class="user-results mt-3">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <div class="user-item"
                                         onclick="seleccionarUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')">
                                        <div class="user-name">
                                            <i class="bi bi-person-circle me-2"></i>
                                            <?php echo htmlspecialchars($usuario['nombre']); ?>
                                        </div>
                                        <div class="user-username">
                                            @<?php echo htmlspecialchars($usuario['usuario']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($busqueda) && empty($usuarios) && !$usuario_seleccionado): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No se encontraron usuarios para "<?php echo htmlspecialchars($busqueda); ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($usuario_seleccionado): ?>
                    <!-- Mostrar información del usuario seleccionado -->
                    <?php 
                    $usuario_actual = null;
                    foreach ($usuarios as $usuario) {
                        if ($usuario['id_usuario'] == $usuario_seleccionado) {
                            $usuario_actual = $usuario;
                            break;
                        }
                    }
                    ?>
                    
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-person-check me-2"></i>
                            <strong>Editando permisos para:</strong> 
                            <?php echo htmlspecialchars($usuario_actual['nombre']); ?> 
                            (<?php echo htmlspecialchars($usuario_actual['usuario']); ?>)
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="volverABusqueda()">
                            <i class="bi bi-arrow-left me-1"></i>Cambiar Usuario
                        </button>
                    </div>

                    <!-- Formulario de permisos -->
                    <form method="POST">
                        <input type="hidden" name="id_usuario" value="<?php echo $usuario_seleccionado; ?>">
                        
                        <div class="mb-3">
                            <h5>Permisos de Menú</h5>
                            <p class="text-muted">Marque las casillas para permitir el acceso a cada elemento del menú</p>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarTodos()">
                                    <i class="bi bi-check-all me-1"></i>Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deseleccionarTodos()">
                                    <i class="bi bi-x-circle me-1"></i>Deseleccionar Todos
                                </button>
                            </div>
                        </div>
                        
                        <div class="menu-list">
                            <?php 
                            // Cargar TODOS los items del menú activos
                            $stmt_todos = $conexion->query("
                                SELECT mi.id, mi.nombre, mi.url, mi.icono, mi.parent_id 
                                FROM menu_items mi 
                                WHERE mi.activo = true 
                                ORDER BY mi.orden, mi.nombre
                            ");
                            $todos_los_items = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Organizar en estructura jerárquica
                            $menu_estructura_completa = [];
                            foreach ($todos_los_items as $item) {
                                if ($item['parent_id'] === null) {
                                    $menu_estructura_completa[$item['id']] = $item;
                                    $menu_estructura_completa[$item['id']]['subitems'] = [];
                                }
                            }
                            
                            foreach ($todos_los_items as $item) {
                                if ($item['parent_id'] !== null && isset($menu_estructura_completa[$item['parent_id']])) {
                                    $menu_estructura_completa[$item['parent_id']]['subitems'][] = $item;
                                }
                            }
                            ?>
                            
                            <?php foreach ($menu_estructura_completa as $item): ?>
                                <div class="menu-item parent-item">
                                    <div class="form-check">
                                        <input class="form-check-input menu-checkbox parent-checkbox" type="checkbox" 
                                               name="permisos[]" 
                                               value="<?php echo $item['id']; ?>" 
                                               id="menu_<?php echo $item['id']; ?>"
                                               data-parent="<?php echo $item['id']; ?>"
                                               <?php echo in_array($item['id'], $permisos_actuales) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="menu_<?php echo $item['id']; ?>">
                                            <i class="<?php echo $item['icono']; ?> me-2"></i>
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <?php if (!empty($item['subitems'])): ?>
                                    <?php foreach ($item['subitems'] as $subitem): ?>
                                        <div class="submenu-item ms-4">
                                            <div class="form-check">
                                                <input class="form-check-input menu-checkbox child-checkbox" type="checkbox" 
                                                       name="permisos[]" 
                                                       value="<?php echo $subitem['id']; ?>" 
                                                       id="menu_<?php echo $subitem['id']; ?>"
                                                       data-parent="<?php echo $item['id']; ?>"
                                                       <?php echo in_array($subitem['id'], $permisos_actuales) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="menu_<?php echo $subitem['id']; ?>">
                                                    <i class="<?php echo $subitem['icono']; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($subitem['nombre']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn-custom">
                                <i class="bi bi-check-circle me-2"></i>Guardar Permisos
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="volverABusqueda()">Cancelar</button>
                        </div>
                    </form>
                <?php elseif (empty($busqueda)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Escribe en el buscador para encontrar usuarios y gestionar sus permisos.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ========== FUNCIONES DEL BUSCADOR ==========
        let searchTimer;
        
        function buscarUsuarios() {
            const busqueda = document.getElementById('busqueda').value.trim();
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsContainer = document.getElementById('userResultsContainer');
            const resultsInfo = document.getElementById('resultsInfo');
            
            // Mostrar spinner
            loadingSpinner.style.display = 'block';
            resultsContainer.innerHTML = '';
            
            // Limpiar timer anterior
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            
            // Esperar 500ms después de que el usuario deje de escribir
            searchTimer = setTimeout(() => {
                if (busqueda.length === 0) {
                    loadingSpinner.style.display = 'none';
                    resultsInfo.innerHTML = '';
                    resultsContainer.innerHTML = '';
                    return;
                }
                
                // Hacer la búsqueda via AJAX
                fetch(`permisos_menu.php?busqueda=${encodeURIComponent(busqueda)}&ajax=1`)
                    .then(response => response.text())
                    .then(html => {
                        // Extraer solo la parte de resultados del HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        
                        const newResults = tempDiv.querySelector('#userResultsContainer');
                        const newInfo = tempDiv.querySelector('#resultsInfo');
                        
                        if (newResults) {
                            resultsContainer.innerHTML = newResults.innerHTML;
                        }
                        if (newInfo) {
                            resultsInfo.innerHTML = newInfo.innerHTML;
                        }
                        
                        loadingSpinner.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error en la búsqueda:', error);
                        loadingSpinner.style.display = 'none';
                        resultsContainer.innerHTML = `
                            <div class="alert alert-danger mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Error al buscar usuarios
                            </div>
                        `;
                    });
            }, 500);
        }
        
        function seleccionarUsuario(idUsuario, nombreUsuario) {
            // Limpiar el área de resultados
            document.getElementById('userResultsContainer').innerHTML = '';
            document.getElementById('resultsInfo').innerHTML = '';
            
            // Redirigir a la misma página con el usuario seleccionado
            const busqueda = document.getElementById('busqueda').value;
            const url = `permisos_menu.php?usuario_id=${idUsuario}&busqueda=${encodeURIComponent(busqueda)}`;
            window.location.href = url;
        }
        
        function limpiarBusqueda() {
            document.getElementById('busqueda').value = '';
            document.getElementById('userResultsContainer').innerHTML = '';
            document.getElementById('resultsInfo').innerHTML = '';
            document.getElementById('busqueda').focus();
        }
        
        function volverABusqueda() {
            const busqueda = document.getElementById('busqueda').value;
            window.location.href = `permisos_menu.php?busqueda=${encodeURIComponent(busqueda)}`;
        }

        // ========== INICIALIZACIÓN DEL BUSCADOR ==========
        document.addEventListener('DOMContentLoaded', function() {
            const busquedaInput = document.getElementById('busqueda');
            
            if (busquedaInput) {
                // Enfocar automáticamente en el campo de búsqueda
                busquedaInput.focus();
                
                // Buscar automáticamente al escribir
                busquedaInput.addEventListener('input', buscarUsuarios);
                
                // Limpiar búsqueda con Escape
                busquedaInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        limpiarBusqueda();
                    }
                });
            }
        });

        // ========== FUNCIONES DE PERMISOS ==========
        function seleccionarTodos() {
            const checkboxes = document.querySelectorAll('.menu-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        function deseleccionarTodos() {
            const checkboxes = document.querySelectorAll('.menu-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Lógica para selección padre-hijo
        document.addEventListener('DOMContentLoaded', function() {
            // Cuando se selecciona un padre, seleccionar todos sus hijos
            document.querySelectorAll('.parent-checkbox').forEach(parent => {
                parent.addEventListener('change', function() {
                    const parentId = this.getAttribute('data-parent');
                    const children = document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`);
                    children.forEach(child => {
                        child.checked = this.checked;
                    });
                });
            });

            // Cuando se cambia un hijo, verificar si todos están seleccionados para marcar el padre
            document.querySelectorAll('.child-checkbox').forEach(child => {
                child.addEventListener('change', function() {
                    const parentId = this.getAttribute('data-parent');
                    const parent = document.querySelector(`.parent-checkbox[data-parent="${parentId}"]`);
                    const siblings = document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`);
                    
                    const allChecked = Array.from(siblings).every(sibling => sibling.checked);
                    parent.checked = allChecked;
                });
            });
        });

        // ========== FUNCIONES DEL SIDEBAR ==========
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('mobile-open');
            mainContent.classList.toggle('mobile-expanded');
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('mobile-open');
                mainContent.classList.remove('mobile-expanded');
            }
        }

        // ========== INICIALIZACIÓN DEL SIDEBAR ==========
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleMobileSidebar);
            }
            if (mainContent) {
                mainContent.addEventListener('click', closeMobileSidebar);
            }

            function handleResize() {
                const sidebar = document.getElementById('sidebar');
                if (window.innerWidth > 992) {
                    if (sidebar) sidebar.classList.remove('mobile-open');
                    if (mainContent) mainContent.classList.remove('mobile-expanded');
                } else {
                    if (sidebar) sidebar.classList.remove('collapsed');
                    if (mainContent) mainContent.classList.remove('expanded');
                }
            }

            window.addEventListener('resize', handleResize);

            // Auto-colapsar en móvil
            if (window.innerWidth <= 992) {
                closeMobileSidebar();
            }
        });
    </script>
</body>
</html>