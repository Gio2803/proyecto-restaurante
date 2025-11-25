<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'corte_caja.php';

try {
    // Verificar permisos usando menu_items
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as tiene_permiso 
        FROM menu_items mi 
        INNER JOIN permisos_menu pm ON mi.id = pm.menu_item_id 
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

} catch (Exception $e) {
    // Si hay error en la consulta, permitir acceso temporalmente
    error_log("Error en verificación de permisos: " . $e->getMessage());
    // No redirigir, permitir acceso mientras se soluciona
}

// Función para verificar permisos de corte de caja
function verificarPermisoCorte($id_usuario)
{
    global $conexion;

    $sql = "SELECT pm.permiso_corte 
            FROM usuarios u 
            JOIN permisos_menu pm ON u.id_usuario = pm.id_usuario 
            JOIN menu_items mi ON pm.menu_item_id = mi.id 
            WHERE u.id_usuario = ? AND mi.url = 'corte_caja.php' AND pm.permiso_corte = true";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id_usuario]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

// Función para obtener caja abierta (MODIFICADA PARA POSTGRES)
function obtenerCajaAbierta($id_usuario)
{
    global $conexion;

    $sql = "SELECT * FROM corte_caja 
            WHERE id_usuario = ? AND estado = 'ABIERTO' 
            ORDER BY fecha_apertura DESC LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id_usuario]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener caja abierta actual
$caja_abierta = obtenerCajaAbierta($_SESSION['id_usuario']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja - Pizzería</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
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
            padding-top: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-custom {
            max-width: 1400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .banner {
            background-color: var(--danger-color);
            color: white;
            padding: 15px;
            border-radius: 15px 15px 0 0;
            text-align: center;
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

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--light-color));
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .caja-status {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }

        .caja-abierta {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .caja-cerrada {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .btn-danger-custom {
            background-color: #dc3545;
            color: white;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }

        .btn-danger-custom:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .resumen-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .modal-header-custom {
            background: var(--danger-color);
            color: white;
        }

        .input-group-custom {
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group-custom .input-group-text {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .input-group-custom .form-control {
            border: none;
            padding: 12px;
        }

        /* Estilos específicos para el modal de cierre de caja */
        .modal-cierre-caja .modal-dialog {
            max-width: 800px !important;
        }

        .modal-cierre-caja .modal-content {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }

        .badge-estado {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .badge-abierta {
            background: #28a745;
            color: white;
        }

        .badge-cerrada {
            background: #dc3545;
            color: white;
        }

        .mesero-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .mesero-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .mesero-nombre {
            font-weight: bold;
            color: var(--danger-color);
            font-size: 1.1em;
        }

        .mesero-total {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .total-general {
            background: linear-gradient(135deg, var(--danger-color), var(--light-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        .total-general .total-monto {
            font-size: 2.5rem;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 15px;
            }

            .btn-custom {
                width: 100%;
            }
        }

        /* ========== ESTILOS DEL MENU ========== */
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

        body {
            padding-top: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar-collapse-btn {
            position: absolute;
            top: 50%;
            right: -12px;
            transform: translateY(-50%);
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1002;
        }

        /* Estilos para el ticket */
        .ticket-popup .swal2-popup {
            max-width: 600px !important;
            font-family: 'Courier New', monospace;
        }

        .ticket-container {
            background: white;
            border: 2px solid #333;
            border-radius: 5px;
            padding: 15px;
            font-size: 12px;
        }

        .ticket-header {
            border-bottom: 2px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .ticket-header h4 {
            margin: 0;
            font-weight: bold;
        }

        .resumen-caja .row {
            margin: 3px 0;
        }

        .total-row {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
            font-weight: bold;
        }

        .mesero-ticket {
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .mesero-ticket small {
            font-size: 10px;
            color: #666;
        }

        .total-meseros {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
        }

        .ticket-footer {
            border-top: 2px dashed #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .ticket-container {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10px !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu.php'; ?>

    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <div class="container container-custom">
            <div class="banner">
                <h1><i class="bi bi-cash-coin"></i> CORTE DE CAJA</h1>
                <p class="mb-0">Sistema de control de caja y ventas</p>
            </div>

            <!-- Estado de la caja -->
            <div class="caja-status <?php echo $caja_abierta ? 'caja-abierta' : 'caja-cerrada'; ?>">
                <i class="bi <?php echo $caja_abierta ? 'bi-unlock-fill' : 'bi-lock-fill'; ?>"></i>
                <?php echo $caja_abierta ? 'CAJA ABIERTA' : 'CAJA CERRADA'; ?>
                <?php if ($caja_abierta): ?>
                    <br><small>Abierta desde: <?php echo date('H:i', strtotime($caja_abierta['fecha_apertura'])); ?></small>
                <?php endif; ?>
            </div>

            <div class="row">
                <!-- Botones de acción -->
                <div class="col-md-6 mb-3">
                    <?php if (!$caja_abierta): ?>
                        <button class="btn btn-custom w-100" data-bs-toggle="modal" data-bs-target="#modalApertura">
                            <i class="bi bi-unlock-fill"></i> ABRIR CAJA
                        </button>
                    <?php else: ?>
                        <button class="btn btn-danger-custom w-100" data-bs-toggle="modal"
                            data-bs-target="#modalCierreCaja">
                            <i class="bi bi-lock-fill"></i> CERRAR CAJA
                        </button>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-secondary w-100" data-bs-toggle="modal"
                        data-bs-target="#modalHistorial">
                        <i class="bi bi-clock-history"></i> HISTORIAL
                    </button>
                </div>
            </div>

            <!-- Resumen de ventas por mesero (solo si la caja está abierta) -->
            <?php if ($caja_abierta): ?>
                <div class="resumen-section">
                    <h4><i class="bi bi-graph-up"></i> VENTAS POR MESERO</h4>
                    <div id="resumen-meseros">
                        <!-- Los datos se cargarán por AJAX -->
                        <div class="col-md-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de ventas -->
                <div class="resumen-section">
                    <h4><i class="bi bi-receipt"></i> DETALLE DE VENTAS</h4>
                    <div id="detalle-ventas">
                        <!-- Los datos se cargarán por AJAX -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Apertura de Caja -->
    <div class="modal fade" id="modalApertura" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-unlock-fill"></i> APERTURA DE CAJA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formApertura">
                        <div class="mb-3">
                            <label class="form-label">Monto Inicial en Efectivo</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="monto_inicial" step="0.01" min="0"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones (Opcional)</label>
                            <textarea class="form-control" name="observaciones" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-custom" onclick="abrirCaja()">Abrir Caja</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cierre de Caja -->
    <div class="modal fade modal-cierre-caja" id="modalCierreCaja" tabindex="-1" aria-labelledby="modalCierreCajaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCierreCajaLabel">Cierre de caja - Pozente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna izquierda - Resumen -->
                        <div class="col-md-6 border-end">
                            <h6 class="text-center mb-4">Resumen de Cierre</h6>

                            <div id="resumen-cierre">
                                <!-- El resumen se cargará por AJAX -->
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>

                            <form id="formCierre">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Efectivo en Caja</label>
                                    <input type="number" class="form-control form-control-lg" name="efectivo_final"
                                        step="0.01" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="3"
                                        placeholder="Agregar observaciones..."></textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Columna derecha - Información adicional -->
                        <div class="col-md-6">
                            <h6 class="text-center mb-4">Estado del Sistema</h6>

                            <div class="mb-4 p-3 bg-info bg-opacity-10 rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Estado Caja:</span>
                                    <span class="badge bg-success">ABIERTA</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Apertura:</span>
                                    <span class="text-muted">
                                        <?php echo $caja_abierta ? date('H:i', strtotime($caja_abierta['fecha_apertura'])) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Resumen Rápido</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded text-center">
                                            <small class="text-muted">Pedidos</small>
                                            <div class="fw-bold" id="contador-pedidos">#0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded text-center">
                                            <small class="text-muted">Total Ventas</small>
                                            <div class="fw-bold text-success" id="total-ventas">$0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="cerrarCaja()">Cerrar Caja</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial -->
    <div class="modal fade" id="modalHistorial" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-clock-history"></i> HISTORIAL DE CORTES</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="historial-cortes">
                        <!-- El historial se cargará por AJAX -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ========== FUNCIONES DE CORTE DE CAJA ==========

        // Cargar resumen de ventas
        function cargarResumen() {
            if (!<?php echo $caja_abierta ? 'true' : 'false'; ?>) return;

            $.ajax({
                url: 'funciones/corte_caja.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerResumenVentas'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        $('#resumen-meseros').html('<p class="text-danger">Error: ' + data.error + '</p>');
                        return;
                    }
                    mostrarVentasPorMesero(data.meseros, data.total);
                    mostrarDetalleVentas(data.detalle);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#resumen-meseros').html('<p class="text-danger">Error al cargar el resumen</p>');
                }
            });
        }

        // Mostrar ventas por mesero
        function mostrarVentasPorMesero(meseros, total) {
            let html = '';

            // Mostrar cada mesero
            meseros.forEach(mesero => {
                html += `
                    <div class="mesero-card">
                        <div class="mesero-header">
                            <div class="mesero-nombre">${mesero.nombre_mesero}</div>
                            <div class="mesero-total">$${parseFloat(mesero.total).toFixed(2)}</div>
                        </div>
                        <div class="mesero-detalles">
                            <small class="text-muted">Pedidos: ${mesero.total_pedidos}</small>
                        </div>
                    </div>
                `;
            });

            // Mostrar total general
            html += `
                <div class="total-general">
                    <div class="total-label">TOTAL GENERAL</div>
                    <div class="total-monto">$${parseFloat(total).toFixed(2)}</div>
                </div>
            `;

            $('#resumen-meseros').html(html);
        }

        // Mostrar detalle de ventas
        function mostrarDetalleVentas(detalle) {
            if (detalle.length === 0) {
                $('#detalle-ventas').html('<p class="text-center text-muted">No hay ventas registradas</p>');
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Mesero</th>
                                <th>Mesa</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            detalle.forEach(pedido => {
                html += `
                    <tr>
                        <td>#${pedido.id_pedido}</td>
                        <td>${pedido.nombre_mesero || 'Sin mesero'}</td>
                        <td>${pedido.id_mesa || 'Take Away'}</td>
                        <td>$${pedido.total}</td>
                        <td>${pedido.fecha_creacion}</td>
                        <td><span class="badge-estado ${pedido.estado === 'pagado' ? 'badge-abierta' : 'badge-cerrada'}">${pedido.estado}</span></td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            $('#detalle-ventas').html(html);
        }

        // Abrir caja
        function abrirCaja() {
            const formData = new FormData(document.getElementById('formApertura'));
            const monto_inicial = formData.get('monto_inicial');

            if (!monto_inicial || monto_inicial <= 0) {
                Swal.fire('Error', 'Ingrese un monto inicial válido', 'error');
                return;
            }

            $.ajax({
                url: 'funciones/corte_caja.php',
                type: 'POST',
                data: {
                    funcion: 'AbrirCaja',
                    monto_inicial: monto_inicial,
                    observaciones: formData.get('observaciones')
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Caja Abierta!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire('Error', 'Error al abrir la caja', 'error');
                }
            });
        }

        // Cargar resumen para cierre
        $('#modalCierreCaja').on('show.bs.modal', function () {
            $.ajax({
                url: 'funciones/corte_caja.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerResumenCierre'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        $('#resumen-cierre').html(`
                            <div class="alert alert-danger">
                                <h5>Error al cargar el resumen</h5>
                                <p>${data.error}</p>
                            </div>
                        `);
                        return;
                    }
                    mostrarResumenCierre(data);
                },
                error: function (xhr, status, error) {
                    $('#resumen-cierre').html(`
                        <div class="alert alert-danger">
                            <h5>Error de conexión</h5>
                            <p>No se pudo cargar el resumen.</p>
                        </div>
                    `);
                }
            });
        });

        // Mostrar resumen para cierre
        function mostrarResumenCierre(data) {
            const montoInicial = parseFloat(data.monto_inicial) || 0;
            const ventasTotales = parseFloat(data.ventas_totales) || 0;
            const totalEsperado = montoInicial + ventasTotales;

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number">$${montoInicial.toFixed(2)}</div>
                            <div class="stats-label">MONTO INICIAL</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number">$${ventasTotales.toFixed(2)}</div>
                            <div class="stats-label">VENTAS TOTALES</div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <strong>Total esperado en caja:</strong> $${totalEsperado.toFixed(2)}
                </div>
            `;
            $('#resumen-cierre').html(html);

            // Establecer valor por defecto
            $('input[name="efectivo_final"]').val(totalEsperado.toFixed(2));

            // Actualizar resumen rápido
            $('#contador-pedidos').text('#' + (data.total_pedidos || '0'));
            $('#total-ventas').text('$' + ventasTotales.toFixed(2));
        }

        // Cerrar caja
        function cerrarCaja() {
            const formData = new FormData(document.getElementById('formCierre'));
            const efectivo_final = formData.get('efectivo_final');
            const observaciones = formData.get('observaciones');

            if (!efectivo_final || efectivo_final <= 0) {
                Swal.fire('Error', 'Ingrese un monto final válido', 'error');
                return;
            }

            // Primero cerrar la caja
            $.ajax({
                url: 'funciones/corte_caja.php',
                type: 'POST',
                data: {
                    funcion: 'CerrarCaja',
                    efectivo_final: efectivo_final,
                    observaciones: observaciones
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        // Obtener datos para el ticket después de cerrar
                        obtenerDatosTicket()
                            .then(ticketData => {
                                // Cerrar el modal primero
                                $('#modalCierreCaja').modal('hide');

                                // Usar la función de impresión automática
                                imprimirTicketAutomatico(ticketData, efectivo_final, observaciones);
                            })
                            .catch(error => {
                                console.error('Error obteniendo datos del ticket:', error);
                                // Aún así recargar la página
                                location.reload();
                            });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire('Error', 'Error al cerrar la caja: ' + error, 'error');
                }
            });
        }

        // Cargar historial
        $('#modalHistorial').on('show.bs.modal', function () {
            $.ajax({
                url: 'funciones/corte_caja.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerHistorial'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        $('#historial-cortes').html(`
                            <div class="alert alert-danger">
                                <h5>Error al cargar el historial</h5>
                                <p>${data.error}</p>
                            </div>
                        `);
                        return;
                    }
                    mostrarHistorial(data);
                },
                error: function (xhr, status, error) {
                    $('#historial-cortes').html(`
                        <div class="alert alert-danger">
                            <h5>Error de conexión</h5>
                            <p>No se pudo cargar el historial.</p>
                        </div>
                    `);
                }
            });
        });

        // Mostrar historial
        function mostrarHistorial(cortes) {
            let html = `
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Apertura</th>
                        <th>Fecha Cierre</th>
                        <th>Usuario</th>
                        <th>Monto Inicial</th>
                        <th>Ventas</th>
                        <th>Efectivo Final</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
    `;

            cortes.forEach(corte => {
                const montoInicial = parseFloat(corte.monto_inicial || 0);
                const ventasTotales = parseFloat(corte.ventas_totales || 0);
                const efectivoFinal = parseFloat(corte.monto_final || 0);

                // Usar la diferencia calculada desde la base de datos
                const diferencia = parseFloat(corte.diferencia || 0);

                // Determinar clase para la diferencia
                let diferenciaClass = 'text-success';
                if (diferencia < 0) {
                    diferenciaClass = 'text-danger';
                } else if (diferencia === 0) {
                    diferenciaClass = 'text-muted';
                }

                html += `
            <tr>
                <td>${corte.fecha_apertura_formatted || 'N/A'}</td>
                <td>${corte.fecha_cierre_formatted || 'N/A'}</td>
                <td>${corte.nombre_usuario || 'N/A'}</td>
                <td>$${montoInicial.toFixed(2)}</td>
                <td>$${ventasTotales.toFixed(2)}</td>
                <td>$${efectivoFinal.toFixed(2)}</td>
                <td class="${diferenciaClass} fw-bold">$${diferencia.toFixed(2)}</td>
                <td>
                    <span class="badge-estado ${corte.estado === 'ABIERTO' ? 'badge-abierta' : 'badge-cerrada'}">
                        ${corte.estado || 'CERRADO'}
                    </span>
                </td>
            </tr>
        `;
            });

            html += `</tbody></table></div>`;
            $('#historial-cortes').html(html);
        }

        // Función para imprimir automáticamente sin botón
        function imprimirTicketAutomatico(ticketData, efectivoFinal, observaciones) {
            // Usar formato de 24 horas para ambas fechas
            const fechaHora = new Date().toLocaleString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).replace(',', '');

            const fechaApertura = ticketData.fecha_apertura || 'N/A';
            const montoInicial = parseFloat(ticketData.monto_inicial || 0).toFixed(2);
            const ventasTotales = parseFloat(ticketData.ventas_totales || 0).toFixed(2);
            const totalEsperado = (parseFloat(ticketData.monto_inicial || 0) + parseFloat(ticketData.ventas_totales || 0)).toFixed(2);
            const diferencia = parseFloat(ticketData.diferencia || 0).toFixed(2);
            let ticketContent = `
<!DOCTYPE html>
<html>
<head>
    <title>Ticket Cierre de Caja - Pozente</title>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 80mm;
        }
        .ticket-container { 
            border: 1px solid #000;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .row { 
            display: flex; 
            margin-bottom: 3px;
        }
        .col-1 { flex: 0 0 8.333333%; }
        .col-2 { flex: 0 0 16.666667%; }
        .col-3 { flex: 0 0 25%; }
        .col-4 { flex: 0 0 33.333333%; }
        .col-5 { flex: 0 0 41.666667%; }
        .col-6 { flex: 0 0 50%; }
        .col-7 { flex: 0 0 58.333333%; }
        .col-8 { flex: 0 0 66.666667%; }
        .col-9 { flex: 0 0 75%; }
        .col-10 { flex: 0 0 83.333333%; }
        .col-11 { flex: 0 0 91.666667%; }
        .col-12 { flex: 0 0 100%; }
        .ticket-header h4 { 
            margin: 0 0 5px 0; 
            font-size: 14px;
            font-weight: bold;
        }
        .ticket-header p { 
            margin: 2px 0; 
            font-size: 11px;
        }
        hr { 
            border: none; 
            border-top: 1px dashed #000; 
            margin: 8px 0;
        }
        .resumen-caja .row { margin-bottom: 2px; }
        .total-row { 
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
            font-weight: bold;
        }
        .mesero-ticket { 
            padding: 2px 0;
            border-bottom: 1px dotted #ccc;
        }
        .mesero-ticket small { font-size: 10px; }
        .total-meseros { 
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
            font-weight: bold;
        }
        .ticket-footer p { 
            margin: 3px 0;
            font-size: 10px;
        }
        .bg-light { background-color: #f8f9fa; }
        @media print {
            body { margin: 0; padding: 0; }
            .ticket-container { border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header text-center">
            <h4>PIZZERÍA</h4>
            <p>Corte de Caja - Cierre</p>
            <p><strong>Fecha:</strong> ${fechaHora}</p>
        </div>
        
        <div class="ticket-info">
            <div class="row">
                <div class="col-6">
                    <strong>Apertura:</strong><br>
                    ${fechaApertura}
                </div>
                <div class="col-6">
                    <strong>Cierre:</strong><br>
                    ${fechaHora}
                </div>
            </div>
            <hr>
            
            <div class="resumen-caja">
                <div class="row">
                    <div class="col-8"><strong>Monto Inicial:</strong></div>
                    <div class="col-4 text-end">$${montoInicial}</div>
                </div>
                <div class="row">
                    <div class="col-8"><strong>Ventas Totales:</strong></div>
                    <div class="col-4 text-end">$${ventasTotales}</div>
                </div>
                <div class="row">
                    <div class="col-8"><strong>Total Esperado:</strong></div>
                    <div class="col-4 text-end">$${totalEsperado}</div>
                </div>
                <div class="row">
                    <div class="col-8"><strong>Efectivo Final:</strong></div>
                    <div class="col-4 text-end">$${parseFloat(efectivoFinal).toFixed(2)}</div>
                </div>
                <div class="row total-row">
                    <div class="col-8"><strong>Diferencia:</strong></div>
                    <div class="col-4 text-end">
                        $${diferencia}
                    </div>
                </div>
            </div>
            <hr>
        </div>
        
        <div class="ventas-meseros">
            <h5 class="text-center">VENTAS POR MESERO</h5>
    `;

            // Agregar cada mesero
            if (ticketData.meseros && ticketData.meseros.length > 0) {
                ticketData.meseros.forEach((mesero, index) => {
                    ticketContent += `
                <div class="mesero-ticket ${index % 2 === 0 ? 'bg-light' : ''}">
                    <div class="row">
                        <div class="col-7">
                            <strong>${mesero.nombre_mesero}</strong><br>
                            <small>${mesero.total_pedidos} pedido(s)</small>
                        </div>
                        <div class="col-5 text-end">
                            $${parseFloat(mesero.total || 0).toFixed(2)}
                        </div>
                    </div>
                </div>
            `;
                });
            } else {
                ticketContent += `
            <div class="text-center">
                <p>No hay ventas por mesero</p>
            </div>
        `;
            }

            ticketContent += `
            <div class="total-meseros">
                <div class="row">
                    <div class="col-6"><strong>TOTAL MESEROS:</strong></div>
                    <div class="col-6 text-end"><strong>$${ventasTotales}</strong></div>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer text-center">
            <p><strong>Usuario:</strong> ${ticketData.nombre_usuario || 'N/A'}</p>
            <p>${observaciones || 'Sin observaciones'}</p>
            <hr>
            <p><small>*** CORTE DE CAJA GENERADO AUTOMÁTICAMENTE ***</small></p>
        </div>
    </div>
</body>
</html>`;

            const ventana = window.open('', '_blank');
            ventana.document.write(ticketContent);
            ventana.document.close();
            ventana.focus();

            // Imprimir automáticamente
            setTimeout(() => {
                ventana.print();

                // Cerrar el modal de SweetAlert y recargar la página después de imprimir
                setTimeout(() => {
                    Swal.close();
                    location.reload();
                }, 1000);

            }, 500);
        }

        // Función para generar ticket automáticamente
        function generarTicketAutomatico(ticketData, efectivoFinal, observaciones) {
            const fechaHora = new Date().toLocaleString('es-MX');
            const fechaApertura = ticketData.fecha_apertura;
            const montoInicial = parseFloat(ticketData.monto_inicial).toFixed(2);
            const ventasTotales = parseFloat(ticketData.ventas_totales).toFixed(2);
            const totalEsperado = parseFloat(ticketData.monto_inicial) + parseFloat(ticketData.ventas_totales);

            let ticketHTML = `
        <div class="ticket-container">
            <div class="ticket-header text-center">
                <h4>PIZZERÍA</h4>
                <p>Corte de Caja - Cierre</p>
                <p><strong>Fecha:</strong> ${fechaHora}</p>
            </div>
            
            <div class="ticket-info">
                <div class="row">
                    <div class="col-6">
                        <strong>Apertura:</strong><br>
                        ${fechaApertura}
                    </div>
                    <div class="col-6">
                        <strong>Cierre:</strong><br>
                        ${fechaHora}
                    </div>
                </div>
                <hr>
                
                <div class="resumen-caja">
                    <div class="row">
                        <div class="col-8"><strong>Monto Inicial:</strong></div>
                        <div class="col-4 text-end">$${montoInicial}</div>
                    </div>
                    <div class="row">
                        <div class="col-8"><strong>Ventas Totales:</strong></div>
                        <div class="col-4 text-end">$${ventasTotales}</div>
                    </div>
                    <div class="row">
                        <div class="col-8"><strong>Total Esperado:</strong></div>
                        <div class="col-4 text-end">$${totalEsperado.toFixed(2)}</div>
                    </div>
                    <div class="row">
                        <div class="col-8"><strong>Efectivo Final:</strong></div>
                        <div class="col-4 text-end">$${parseFloat(efectivoFinal).toFixed(2)}</div>
                    </div>
                    <div class="row total-row">
                        <div class="col-8"><strong>Diferencia:</strong></div>
                        <div class="col-4 text-end">
                            $${(parseFloat(efectivoFinal) - totalEsperado).toFixed(2)}
                        </div>
                    </div>
                </div>
                <hr>
            </div>
            
            <div class="ventas-meseros">
                <h5 class="text-center">VENTAS POR MESERO</h5>
    `;

            // Agregar cada mesero
            if (ticketData.meseros && ticketData.meseros.length > 0) {
                ticketData.meseros.forEach((mesero, index) => {
                    ticketHTML += `
                <div class="mesero-ticket ${index % 2 === 0 ? 'bg-light' : ''}">
                    <div class="row">
                        <div class="col-7">
                            <strong>${mesero.nombre_mesero}</strong><br>
                            <small>${mesero.total_pedidos} pedido(s)</small>
                        </div>
                        <div class="col-5 text-end">
                            $${parseFloat(mesero.total).toFixed(2)}
                        </div>
                    </div>
                </div>
            `;
                });
            } else {
                ticketHTML += `
            <div class="text-center">
                <p>No hay ventas por mesero</p>
            </div>
        `;
            }

            ticketHTML += `
                <div class="total-meseros">
                    <div class="row">
                        <div class="col-6"><strong>TOTAL MESEROS:</strong></div>
                        <div class="col-6 text-end"><strong>$${ventasTotales}</strong></div>
                    </div>
                </div>
            </div>
            
            <div class="ticket-footer text-center">
                <p><strong>Usuario:</strong> ${ticketData.nombre_usuario}</p>
                <p>${observaciones || 'Sin observaciones'}</p>
                <hr>
                <p><small>*** CORTE DE CAJA GENERADO AUTOMÁTICAMENTE ***</small></p>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <button class="btn btn-primary no-print" onclick="imprimirTicket()">
                <i class="bi bi-printer"></i> Imprimir Ticket
            </button>
        </div>
    `;

            // Mostrar ticket en un modal
            Swal.fire({
                title: 'Caja Cerrada Exitosamente',
                html: ticketHTML,
                width: 600,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'ticket-popup'
                },
                didOpen: () => {
                    // Asignar la función de impresión al botón
                    window.imprimirTicket = function () {
                        const printContent = document.querySelector('.ticket-container').outerHTML;
                        const ventana = window.open('', '_blank');
                        ventana.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Ticket Cierre de Caja</title>
                        <style>
                            body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; padding: 10px; }
                            .ticket-container { border: 1px solid #000; padding: 10px; }
                            .text-center { text-align: center; }
                            .text-end { text-align: right; }
                            .row { display: flex; margin-bottom: 3px; }
                            .col-1 { flex: 0 0 8.333333%; }
                            .col-2 { flex: 0 0 16.666667%; }
                            .col-3 { flex: 0 0 25%; }
                            .col-4 { flex: 0 0 33.333333%; }
                            .col-5 { flex: 0 0 41.666667%; }
                            .col-6 { flex: 0 0 50%; }
                            .col-7 { flex: 0 0 58.333333%; }
                            .col-8 { flex: 0 0 66.666667%; }
                            .col-9 { flex: 0 0 75%; }
                            .col-10 { flex: 0 0 83.333333%; }
                            .col-11 { flex: 0 0 91.666667%; }
                            .col-12 { flex: 0 0 100%; }
                            hr { border: none; border-top: 1px dashed #000; margin: 8px 0; }
                            .total-row { border-top: 1px solid #000; padding-top: 3px; margin-top: 3px; font-weight: bold; }
                            .mesero-ticket { padding: 2px 0; border-bottom: 1px dotted #ccc; }
                            .total-meseros { border-top: 2px solid #000; padding-top: 5px; margin-top: 5px; font-weight: bold; }
                            @media print { 
                                body { margin: 0; padding: 0; }
                                .ticket-container { border: none; }
                                .no-print { display: none !important; }
                            }
                        </style>
                    </head>
                    <body>
                        ${printContent}
                    </body>
                    </html>
                `);
                        ventana.document.close();
                        ventana.focus();

                        // Imprimir automáticamente
                        setTimeout(() => {
                            ventana.print();
                            setTimeout(() => {
                                ventana.close();
                                Swal.close();
                                location.reload();
                            }, 500);
                        }, 500);
                    };

                    // Imprimir automáticamente después de 1 segundo
                    setTimeout(() => {
                        window.imprimirTicket();
                    }, 1000);
                }
            });
        }

        // Función auxiliar para obtener datos del ticket
        function obtenerDatosTicket() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: 'funciones/corte_caja.php',
                    type: 'POST',
                    data: {
                        funcion: 'ObtenerDatosTicket'
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.error) {
                            reject(response.error);
                        } else {
                            resolve(response);
                        }
                    },
                    error: function (xhr, status, error) {
                        reject('Error de conexión: ' + error);
                    }
                });
            });
        }

        // ========== INICIALIZACIÓN ==========
        $(document).ready(function () {
            // Cargar resumen si la caja está abierta
            if (<?php echo $caja_abierta ? 'true' : 'false'; ?>) {
                cargarResumen();
                // Auto-actualizar cada 5 segundos
                setInterval(cargarResumen, 5000);
            }
        });
    </script>
</body>

</html>