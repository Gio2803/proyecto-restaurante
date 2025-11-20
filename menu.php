<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = basename($_SERVER['PHP_SELF']);

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
?>

<!-- Sidebar con permisos -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="croquis.php" class="sidebar-brand">
            <i class="bi bi-house-door-fill"></i>
            <span>Restaurante</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="navbar-nav">
            <?php
            // Cargar menú con permisos para el usuario actual (excluyendo "Mi Cuenta")
            try {
                $stmt = $conexion->prepare("
                    SELECT mi.id, mi.nombre, mi.url, mi.icono, mi.parent_id 
                    FROM menu_items mi
                    INNER JOIN permisos_menu pm ON mi.id = pm.menu_item_id
                    WHERE pm.id_usuario = ? AND pm.activo = true AND mi.activo = true 
                    AND mi.nombre != 'Mi Cuenta'  -- Excluir Mi Cuenta del menú principal
                    ORDER BY mi.orden, mi.nombre
                ");
                $stmt->execute([$_SESSION['id_usuario']]);
                $menu_permitido = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Si no hay menú configurado, mostrar menú por defecto
                if (empty($menu_permitido)) {
                    ?>
                    <!-- Menú por defecto cuando no hay permisos configurados -->
                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($pagina_actual == 'croquis.php') ? 'active' : ''; ?>"
                            href="croquis.php">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>Croquis</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($pagina_actual == 'clientes.php') ? 'active' : ''; ?>"
                            href="clientes.php">
                            <i class="bi bi-people-fill"></i>
                            <span>Clientes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($pagina_actual == 'mesas.php') ? 'active' : ''; ?>"
                            href="mesas.php">
                            <i class="bi bi-table"></i>
                            <span>Mesas</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-box-seam-fill"></i>
                            <span>Productos</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo ($pagina_actual == 'menu_platillos.php') ? 'active' : ''; ?>"
                                    href="menu_platillos.php">
                                    <i class="bi bi-egg-fried"></i>
                                    <span>Menu/Platillos/Bebidas</span>
                                </a></li>
                            <li><a class="dropdown-item <?php echo ($pagina_actual == 'categorias.php') ? 'active' : ''; ?>"
                                    href="categorias.php">
                                    <i class="bi bi-tags"></i>
                                    <span>Categorías</span>
                                </a></li>
                            <li><a class="dropdown-item <?php echo ($pagina_actual == 'unidades_medida.php') ? 'active' : ''; ?>"
                                    href="unidades_medida.php">
                                    <i class="bi bi-rulers"></i>
                                    <span>Unidades de Medida</span>
                                </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-cash-coin"></i>
                            <span>Cocina y Barra</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo ($pagina_actual == 'cocina.php') ? 'active' : ''; ?>"
                                    href="cocina.php">
                                    <i class="bi bi-fire"></i>
                                    <span>Cocina</span>
                                </a></li>
                            <li><a class="dropdown-item <?php echo ($pagina_actual == 'barra.php') ? 'active' : ''; ?>"
                                    href="barra.php">
                                    <i class="bi bi-cup-straw"></i>
                                    <span>Barra</span>
                                </a></li>
                        </ul>
                    </li>
                    <?php
                } else {
                    // Organizar en estructura jerárquica
                    $menu_estructura = [];
                    foreach ($menu_permitido as $item) {
                        if ($item['parent_id'] === null) {
                            $menu_estructura[$item['id']] = $item;
                            $menu_estructura[$item['id']]['subitems'] = [];
                        }
                    }

                    foreach ($menu_permitido as $item) {
                        if ($item['parent_id'] !== null && isset($menu_estructura[$item['parent_id']])) {
                            $menu_estructura[$item['parent_id']]['subitems'][] = $item;
                        }
                    }

                    // Mostrar elementos del menú con permisos
                    foreach ($menu_estructura as $item):
                        if (empty($item['subitems'])): ?>
                            <li class="nav-item">
                                <a class="nav-link fw-bold <?php echo ($pagina_actual == $item['url']) ? 'active' : ''; ?>"
                                    href="<?php echo $item['url']; ?>">
                                    <i class="<?php echo $item['icono']; ?>"></i>
                                    <span><?php echo $item['nombre']; ?></span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="<?php echo $item['icono']; ?>"></i>
                                    <span><?php echo $item['nombre']; ?></span>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php foreach ($item['subitems'] as $subitem): ?>
                                        <li>
                                            <a class="dropdown-item <?php echo ($pagina_actual == $subitem['url']) ? 'active' : ''; ?>"
                                                href="<?php echo $subitem['url']; ?>">
                                                <i class="<?php echo $subitem['icono']; ?>"></i>
                                                <span><?php echo $subitem['nombre']; ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endif;
                    endforeach;
                }
            } catch (Exception $e) {
                // Si hay error, mostrar menú por defecto
                error_log("Error cargando menú: " . $e->getMessage());
                ?>
                <!-- Menú por defecto en caso de error -->
                <li class="nav-item">
                    <a class="nav-link fw-bold <?php echo ($pagina_actual == 'croquis.php') ? 'active' : ''; ?>"
                        href="croquis.php">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Croquis</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?php echo ($pagina_actual == 'clientes.php') ? 'active' : ''; ?>"
                        href="clientes.php">
                        <i class="bi bi-people-fill"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?php echo ($pagina_actual == 'mesas.php') ? 'active' : ''; ?>"
                        href="mesas.php">
                        <i class="bi bi-table"></i>
                        <span>Mesas</span>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>

        <!-- Menú de cuenta (SIEMPRE VISIBLE - SOLO UNA VEZ) -->
        <ul class="navbar-nav mt-auto">
            <li class="nav-item dropdown">
                <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span>Mi Cuenta</span>
                </a>
                <ul class="dropdown-menu">
                    <?php
                    // Cargar subitems de "Mi Cuenta" desde la base de datos con permisos
                    try {
                        // Primero obtener el ID de "Mi Cuenta"
                        $stmt = $conexion->prepare("
                            SELECT id FROM menu_items 
                            WHERE nombre = 'Mi Cuenta' AND activo = true 
                            LIMIT 1
                        ");
                        $stmt->execute();
                        $mi_cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($mi_cuenta) {
                            $id_mi_cuenta = $mi_cuenta['id'];

                            // Obtener los subitems con permisos (excepto Cerrar Sesión)
                            $stmt = $conexion->prepare("
                                SELECT mi.nombre, mi.url, mi.icono 
                                FROM menu_items mi
                                INNER JOIN permisos_menu pm ON mi.id = pm.menu_item_id
                                WHERE pm.id_usuario = ? AND pm.activo = true 
                                AND mi.activo = true AND mi.parent_id = ?
                                AND mi.nombre != 'Cerrar Sesión'
                                ORDER BY mi.orden
                            ");
                            $stmt->execute([$_SESSION['id_usuario'], $id_mi_cuenta]);
                            $subitems_cuenta = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Mostrar subitems con permisos
                            foreach ($subitems_cuenta as $subitem): ?>
                                <li>
                                    <a class="dropdown-item <?php echo ($pagina_actual == $subitem['url']) ? 'active' : ''; ?>"
                                        href="<?php echo $subitem['url']; ?>">
                                        <i class="<?php echo $subitem['icono']; ?>"></i>
                                        <span><?php echo $subitem['nombre']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach;

                            // Si es administrador (rol = 1), mostrar "Permisos Menú" aunque no tenga permiso específico
                            if ($_SESSION['SISTEMA']['rol'] == 1): ?>
                                <li>
                                    <a class="dropdown-item <?php echo ($pagina_actual == 'permisos_menu.php') ? 'active' : ''; ?>"
                                        href="permisos_menu.php">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Permisos Menú</span>
                                    </a>
                                </li>
                            <?php endif;
                        }
                    } catch (Exception $e) {
                        error_log("Error cargando subitems de Mi Cuenta: " . $e->getMessage());
                        // Si hay error y es administrador, mostrar Permisos Menú
                        if ($_SESSION['SISTEMA']['rol'] == 1): ?>
                            <li>
                                <a class="dropdown-item <?php echo ($pagina_actual == 'permisos_menu.php') ? 'active' : ''; ?>"
                                    href="permisos_menu.php">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Permisos Menú</span>
                                </a>
                            </li>
                        <?php endif;
                    } ?>

                    <!-- Cerrar Sesión (SIEMPRE VISIBLE) -->
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Cerrar sesión</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</div>

<!-- SOLO la función confirmLogout aquí -->
<script>
    function confirmLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "¿Estás seguro de que quieres salir del sistema?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
</script>