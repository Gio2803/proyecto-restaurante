<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'estadisticas.php';

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

// Obtener estadísticas directamente en el mismo archivo
try {
    // Estadísticas generales
    $stmt_ventas = $conexion->prepare("
        SELECT 
            COUNT(*) as total_ventas,
            COALESCE(SUM(total), 0) as ingresos_totales,
            AVG(total) as promedio_venta
        FROM vista_resumen_pedidos 
        WHERE estado = 'pagado'
        AND fecha_creacion >= CURRENT_DATE - INTERVAL '30 days'
    ");
    $stmt_ventas->execute();
    $estadisticas_ventas = $stmt_ventas->fetch(PDO::FETCH_ASSOC);

    // Productos más vendidos
    $stmt_productos = $conexion->prepare("
        SELECT 
            nombre_platillo,
            SUM(cantidad) as total_vendido,
            SUM(subtotal) as ingresos_generados
        FROM detalles_pedido 
        WHERE estado = 'terminado'
        AND fecha_creacion >= CURRENT_DATE - INTERVAL '30 days'
        GROUP BY nombre_platillo 
        ORDER BY total_vendido DESC 
        LIMIT 10
    ");
    $stmt_productos->execute();
    $productos_populares = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // Ventas por mesero
    $stmt_meseros = $conexion->prepare("
        SELECT 
            mesero,
            COUNT(*) as total_pedidos,
            COALESCE(SUM(total), 0) as total_ventas
        FROM vista_resumen_pedidos 
        WHERE estado = 'pagado'
        AND fecha_creacion >= CURRENT_DATE - INTERVAL '30 days'
        GROUP BY mesero 
        ORDER BY total_ventas DESC
    ");
    $stmt_meseros->execute();
    $ventas_meseros = $stmt_meseros->fetchAll(PDO::FETCH_ASSOC);

    // Ventas por día (últimos 7 días)
    $stmt_diarias = $conexion->prepare("
        SELECT 
            DATE(fecha_creacion) as fecha,
            COUNT(*) as pedidos,
            COALESCE(SUM(total), 0) as ventas
        FROM vista_resumen_pedidos 
        WHERE estado = 'pagado'
        AND fecha_creacion >= CURRENT_DATE - INTERVAL '7 days'
        GROUP BY DATE(fecha_creacion)
        ORDER BY fecha DESC
    ");
    $stmt_diarias->execute();
    $ventas_diarias = $stmt_diarias->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $estadisticas_ventas = ['total_ventas' => 0, 'ingresos_totales' => 0, 'promedio_venta' => 0];
    $productos_populares = [];
    $ventas_meseros = [];
    $ventas_diarias = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - Estadísticas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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

        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: white;
            border-bottom: 1px solid #eee;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--light-color));
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .filtros-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
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

        /* Estilos específicos para estadísticas */
        .card-estadistica {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .card-estadistica h5 {
            color: var(--danger-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .numero-grande {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .texto-destacado {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--danger-color);
        }

        .badge-estadistica {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .progress {
            height: 8px;
            margin: 5px 0;
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .tabla-estadisticas th {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        .resumen-dia {
            background: linear-gradient(135deg, #67C090, #26667F);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .resumen-dia .fecha {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .resumen-dia .ventas {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .seccion-estadistica {
            display: block;
        }

        .seccion-estadistica.oculta {
            display: none;
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
                <h1><i class="bi bi-graph-up"></i> Estadísticas del Sistema</h1>
            </div>

            <!-- Filtros -->
            <div class="filtros-container">
                <div class="row">
                    <div class="col-md-6">
                        <label for="rango_fechas" class="form-label">Rango de Fechas</label>
                        <select class="form-control" id="rango_fechas">
                            <option value="7">Últimos 7 días</option>
                            <option value="30" selected>Últimos 30 días</option>
                            <option value="90">Últimos 3 meses</option>
                            <option value="365">Último año</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_estadistica" class="form-label">Tipo de Estadística</label>
                        <select class="form-control" id="tipo_estadistica">
                            <option value="general" selected>General</option>
                            <option value="productos">Productos Más Vendidos</option>
                            <option value="meseros">Desempeño de Meseros</option>
                            <option value="diarias">Ventas Diarias</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <div></div> <!-- Espacio vacío para mantener el layout -->
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card text-center">
                            <div class="stats-number" id="total-ventas"><?php echo $estadisticas_ventas['total_ventas'] ?? 0; ?></div>
                            <small>Ventas Totales</small>
                        </div>
                        <div class="stats-card text-center">
                            <div class="stats-number" id="ingresos-totales">$<?php echo number_format($estadisticas_ventas['ingresos_totales'] ?? 0, 2); ?></div>
                            <small>Ingresos Totales</small>
                        </div>
                        <div class="stats-card text-center">
                            <div class="stats-number" id="promedio-venta">$<?php echo number_format($estadisticas_ventas['promedio_venta'] ?? 0, 2); ?></div>
                            <small>Promedio por Venta</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <!-- Estadísticas Generales -->
                <div id="estadisticas-generales" class="seccion-estadistica">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card-estadistica">
                                <h5><i class="bi bi-cash-coin"></i> Resumen Financiero</h5>
                                <div class="numero-grande">$<?php echo number_format($estadisticas_ventas['ingresos_totales'] ?? 0, 2); ?></div>
                                <p>Ingresos totales (30 días)</p>
                                <div class="d-flex justify-content-between">
                                    <span>Ventas: <?php echo $estadisticas_ventas['total_ventas'] ?? 0; ?></span>
                                    <span class="badge-estadistica">Promedio: $<?php echo number_format($estadisticas_ventas['promedio_venta'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-estadistica">
                                <h5><i class="bi bi-star"></i> Producto Más Popular</h5>
                                <?php if (!empty($productos_populares)): ?>
                                    <div class="texto-destacado"><?php echo $productos_populares[0]['nombre_platillo']; ?></div>
                                    <p>Vendido: <?php echo $productos_populares[0]['total_vendido']; ?> veces</p>
                                    <p>Ingresos: $<?php echo number_format($productos_populares[0]['ingresos_generados'], 2); ?></p>
                                <?php else: ?>
                                    <p>No hay datos disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-estadistica">
                                <h5><i class="bi bi-person-badge"></i> Mejor Mesero</h5>
                                <?php if (!empty($ventas_meseros)): ?>
                                    <div class="texto-destacado"><?php echo $ventas_meseros[0]['mesero']; ?></div>
                                    <p>Ventas: $<?php echo number_format($ventas_meseros[0]['total_ventas'], 2); ?></p>
                                    <p>Pedidos: <?php echo $ventas_meseros[0]['total_pedidos']; ?></p>
                                <?php else: ?>
                                    <p>No hay datos disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Productos Populares -->
                    <div class="card-estadistica mt-4">
                        <h5><i class="bi bi-trophy"></i> Top 10 Productos Más Vendidos</h5>
                        <div class="table-responsive">
                            <table class="table table-hover tabla-estadisticas">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Unidades Vendidas</th>
                                        <th>Ingresos Generados</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_unidades = array_sum(array_column($productos_populares, 'total_vendido'));
                                    foreach ($productos_populares as $index => $producto): 
                                        $porcentaje = $total_unidades > 0 ? ($producto['total_vendido'] / $total_unidades) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $producto['nombre_platillo']; ?></td>
                                        <td><?php echo $producto['total_vendido']; ?></td>
                                        <td>$<?php echo number_format($producto['ingresos_generados'], 2); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $porcentaje; ?>%">
                                                    <?php echo number_format($porcentaje, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ventas por Mesero -->
                    <div class="card-estadistica mt-4">
                        <h5><i class="bi bi-people"></i> Desempeño de Meseros</h5>
                        <div class="table-responsive">
                            <table class="table table-hover tabla-estadisticas">
                                <thead>
                                    <tr>
                                        <th>Mesero</th>
                                        <th>Total Pedidos</th>
                                        <th>Ventas Totales</th>
                                        <th>Promedio por Pedido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_meseros as $mesero): ?>
                                    <tr>
                                        <td><?php echo $mesero['mesero']; ?></td>
                                        <td><?php echo $mesero['total_pedidos']; ?></td>
                                        <td>$<?php echo number_format($mesero['total_ventas'], 2); ?></td>
                                        <td>$<?php echo number_format($mesero['total_ventas'] / max($mesero['total_pedidos'], 1), 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ventas Diarias -->
                    <div class="card-estadistica mt-4">
                        <h5><i class="bi bi-calendar-week"></i> Ventas de los Últimos 7 Días</h5>
                        <div class="row">
                            <?php foreach ($ventas_diarias as $venta_dia): 
                                $fecha = DateTime::createFromFormat('Y-m-d', $venta_dia['fecha']);
                                $fecha_formateada = $fecha ? $fecha->format('d/m') : $venta_dia['fecha'];
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="resumen-dia">
                                    <div class="fecha"><?php echo $fecha_formateada; ?></div>
                                    <div class="ventas">$<?php echo number_format($venta_dia['ventas'], 2); ?></div>
                                    <div class="pedidos"><?php echo $venta_dia['pedidos']; ?> pedidos</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sección de Productos (oculta por defecto) -->
                <div id="estadisticas-productos" class="seccion-estadistica oculta">
                    <div class="card-estadistica">
                        <h5><i class="bi bi-trophy"></i> Productos Más Vendidos</h5>
                        <div class="table-responsive">
                            <table class="table table-hover tabla-estadisticas">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Unidades Vendidas</th>
                                        <th>Ingresos Generados</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_unidades = array_sum(array_column($productos_populares, 'total_vendido'));
                                    foreach ($productos_populares as $index => $producto): 
                                        $porcentaje = $total_unidades > 0 ? ($producto['total_vendido'] / $total_unidades) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $producto['nombre_platillo']; ?></td>
                                        <td><?php echo $producto['total_vendido']; ?></td>
                                        <td>$<?php echo number_format($producto['ingresos_generados'], 2); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $porcentaje; ?>%">
                                                    <?php echo number_format($porcentaje, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sección de Meseros (oculta por defecto) -->
                <div id="estadisticas-meseros" class="seccion-estadistica oculta">
                    <div class="card-estadistica">
                        <h5><i class="bi bi-people"></i> Desempeño de Meseros</h5>
                        <div class="table-responsive">
                            <table class="table table-hover tabla-estadisticas">
                                <thead>
                                    <tr>
                                        <th>Mesero</th>
                                        <th>Total Pedidos</th>
                                        <th>Ventas Totales</th>
                                        <th>Promedio por Pedido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_meseros as $mesero): ?>
                                    <tr>
                                        <td><?php echo $mesero['mesero']; ?></td>
                                        <td><?php echo $mesero['total_pedidos']; ?></td>
                                        <td>$<?php echo number_format($mesero['total_ventas'], 2); ?></td>
                                        <td>$<?php echo number_format($mesero['total_ventas'] / max($mesero['total_pedidos'], 1), 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sección de Ventas Diarias (oculta por defecto) -->
                <div id="estadisticas-diarias" class="seccion-estadistica oculta">
                    <div class="card-estadistica">
                        <h5><i class="bi bi-calendar-week"></i> Ventas de los Últimos 7 Días</h5>
                        <div class="row">
                            <?php foreach ($ventas_diarias as $venta_dia): 
                                $fecha = DateTime::createFromFormat('Y-m-d', $venta_dia['fecha']);
                                $fecha_formateada = $fecha ? $fecha->format('d/m') : $venta_dia['fecha'];
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="resumen-dia">
                                    <div class="fecha"><?php echo $fecha_formateada; ?></div>
                                    <div class="ventas">$<?php echo number_format($venta_dia['ventas'], 2); ?></div>
                                    <div class="pedidos"><?php echo $venta_dia['pedidos']; ?> pedidos</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
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

            sidebarToggle.addEventListener('click', toggleMobileSidebar);
            mainContent.addEventListener('click', closeMobileSidebar);

            function handleResize() {
                const sidebar = document.getElementById('sidebar');
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('mobile-open');
                    mainContent.classList.remove('mobile-expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                }
            }

            window.addEventListener('resize', handleResize);

            // Auto-colapsar en móvil
            if (window.innerWidth <= 992) {
                closeMobileSidebar();
            }
        });

        // ========== FUNCIONES DE ESTADÍSTICAS ==========
        $(document).ready(function () {
            // Inicializar DataTables
            $('.tabla-estadisticas').DataTable({
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                pageLength: 10,
                responsive: true
            });

            // Cambiar tipo de estadística
            $('#tipo_estadistica').change(function () {
                const tipo = $(this).val();
                
                // Ocultar todas las secciones
                $('.seccion-estadistica').addClass('oculta');
                
                // Mostrar solo la sección seleccionada
                switch(tipo) {
                    case 'general':
                        $('#estadisticas-generales').removeClass('oculta');
                        break;
                    case 'productos':
                        $('#estadisticas-productos').removeClass('oculta');
                        break;
                    case 'meseros':
                        $('#estadisticas-meseros').removeClass('oculta');
                        break;
                    case 'diarias':
                        $('#estadisticas-diarias').removeClass('oculta');
                        break;
                }
            });

            // Cambiar rango de fechas
            $('#rango_fechas').change(function () {
                const rango = $(this).val();
                Swal.fire({
                    title: 'Cambiando rango',
                    text: `Mostrando datos de los últimos ${rango} días`,
                    icon: 'info',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        });
    </script>
</body>

</html>