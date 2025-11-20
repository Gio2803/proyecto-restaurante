<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'historial_ventas.php';

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


// Usar timezone específico para evitar problemas de zona horaria
$timezone = new DateTimeZone('America/Mexico_City'); // Ajusta según tu zona horaria
$fecha_actual_obj = new DateTime('now', $timezone);
$fecha_actual = $fecha_actual_obj->format('Y-m-d');

// Debug: verificar la fecha
error_log("Fecha actual en servidor: " . $fecha_actual);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - Historial de Ventas</title>
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

        .btn-secondary-custom {
            background-color: var(--light-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background-color: var(--danger-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        #ventasTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }

        #ventasTable tbody tr:hover {
            background-color: rgba(103, 192, 144, 0.1);
        }

        .modal-header {
            background-color: var(--light-color);
            border-bottom: 2px solid var(--primary-color);
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

        .estado-pagado {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        .estado-pendiente {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
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

        .nota-platillo {
            font-size: 0.8rem;
            color: #666;
            font-style: italic;
        }

        /* ESTILOS PARA IMPRIMIR REPORTE COMPLETO */
        @media print {
            body {
                background: white !important;
                margin: 0;
                padding: 0;
                font-size: 12px !important;
            }

            .no-print,
            .sidebar,
            .sidebar-toggle,
            .banner,
            .filtros-container,
            .header-actions,
            .stats-card,
            .btn-custom,
            .btn-secondary-custom,
            .dataTables_length,
            .dataTables_filter,
            .dataTables_info,
            .dataTables_paginate,
            #ventasTable th:last-child,
            #ventasTable td:last-child {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            .container-custom {
                max-width: 100% !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .table-container {
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: white !important;
                margin: 0 !important;
            }

            #ventasTable {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10px !important;
            }

            #ventasTable th,
            #ventasTable td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                text-align: left !important;
                background: white !important;
                color: black !important;
            }

            #ventasTable th {
                background-color: #f8f9fa !important;
                color: #000 !important;
                font-weight: bold !important;
            }

            .reporte-header {
                display: block !important;
                text-align: center;
                margin-bottom: 15px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                page-break-after: avoid;
            }

            .reporte-info {
                display: block !important;
                margin-bottom: 10px;
                padding: 8px;
                background-color: #f8f9fa;
                border-radius: 3px;
                font-size: 10px;
            }

            /* Asegurar que la tabla ocupe toda la página */
            .table-responsive {
                overflow: visible !important;
            }
        }

        .reporte-header {
            display: none;
        }

        .reporte-info {
            display: none;
        }
    </style>
</head>

<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle no-print" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'menu.php'; ?>

    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <div class="container container-custom">
            <!-- Encabezado para impresión -->
            <div class="reporte-header">
                <h1 style="font-size: 18px; margin: 0;">Reporte de Historial de Ventas</h1>
                <p style="font-size: 12px; margin: 5px 0;"><strong>Sistema de Gestión - Restaurante</strong></p>
                <div class="reporte-info">
                    <p style="margin: 2px 0;"><strong>Fecha de generación:</strong> <span
                            id="fecha-generacion">Cargando...</span></p>
                    <p style="margin: 2px 0;"><strong>Rango de fechas:</strong> <span id="fecha-rango-impresion">Todas
                            las fechas</span></p>
                    <p style="margin: 2px 0;"><strong>Total de ventas:</strong> <span
                            id="total-ventas-impresion">0</span></p>
                    <p style="margin: 2px 0;"><strong>Total de ingresos:</strong> <span
                            id="total-ingresos-impresion">$0.00</span></p>
                </div>
            </div>

            <div class="banner text-center no-print">
                <h1><i class="bi bi-receipt"></i> Historial de Ventas</h1>
            </div>

            <!-- Filtros -->
            <div class="filtros-container no-print">
                <div class="row">
                    <div class="col-md-6">
                        <label for="fecha_desde" class="form-label">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" max="<?php echo $fecha_actual; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" max="<?php echo $fecha_actual; ?>">
                    </div>
                </div>
            </div>

            <div class="header-actions no-print">
                <div>
                    <button class="btn-secondary-custom" id="imprimir-reporte">
                        <i class="bi bi-printer"></i> Imprimir Reporte
                    </button>
                </div>
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card text-center">
                            <div class="stats-number" id="total-ventas">0</div>
                            <small>Ventas Totales</small>
                        </div>
                        <div class="stats-card text-center">
                            <div class="stats-number" id="total-ingresos">$0.00</div>
                            <small>Ingresos Totales</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table id="ventasTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Mesa</th>
                            <th>Cliente</th>
                            <th>Mesero</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="no-print">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="resultados_ventas">
                        <tr>
                            <td colspan="8" class="text-center">Cargando datos de ventas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal para ver detalles del pedido -->
        <div class="modal fade no-print" id="pedidoModal" tabindex="-1" aria-labelledby="pedidoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pedidoModalLabel">Detalles del Pedido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modal-body-pedido">
                        <!-- Contenido del modal se cargará aquí -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn-custom" id="imprimir-ticket">Imprimir Ticket</button>
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

        // ========== FUNCIONES DE HISTORIAL DE VENTAS ==========
        $(document).ready(function () {
            let dataTable;
            let currentPedidoId = null;

            function initTable() {
                cargarTabla();

                // Actualizar estadísticas cuando se busque o cambie de página
                $('#ventasTable').on('draw.dt', function () {
                    actualizarEstadisticas();
                });
            }
            function cargarTabla() {
                const fecha_desde = $('#fecha_desde').val();
                const fecha_hasta = $('#fecha_hasta').val();

                // Mostrar loading
                if ($.fn.DataTable.isDataTable('#ventasTable')) {
                    dataTable.clear().draw();
                    $('#ventasTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>');
                }

                $.post("funciones/historial_ventas.php", {
                    funcion: "Tabla",
                    fecha_desde: fecha_desde,
                    fecha_hasta: fecha_hasta
                }, function (response) {
                    // Si DataTable ya existe, solo actualizar los datos
                    if ($.fn.DataTable.isDataTable('#ventasTable')) {
                        // Limpiar tabla
                        dataTable.clear();

                        // Parsear el HTML de respuesta para obtener los datos
                        const tempDiv = $('<div>').html(response);
                        const rows = tempDiv.find('tr');

                        // Agregar filas a DataTable
                        rows.each(function () {
                            const cells = $(this).find('td');
                            if (cells.length > 0) {
                                const rowData = [];
                                cells.each(function () {
                                    rowData.push($(this).html());
                                });
                                dataTable.row.add(rowData);
                            }
                        });

                        // Dibujar tabla
                        dataTable.draw();
                    } else {
                        // Inicializar DataTable por primera vez
                        $("#resultados_ventas").html(response);
                        dataTable = $('#ventasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ pedidos",
                                infoEmpty: "Mostrando 0 a 0 de 0 pedidos",
                                infoFiltered: "(filtrado de _MAX_ pedidos totales)",
                                lengthMenu: "Mostrar _MENU_ pedidos",
                                zeroRecords: "No se encontraron pedidos",
                                search: "Buscar:",
                                paginate: {
                                    first: "Primero",
                                    last: "Último",
                                    next: "Siguiente",
                                    previous: "Anterior"
                                }
                            },
                            responsive: true,
                            pageLength: 10,
                            lengthMenu: [5, 10, 20, 50],
                            order: [[0, 'desc']]
                        });
                    }

                    actualizarEstadisticas();
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar la tabla de ventas", "error");
                    if ($.fn.DataTable.isDataTable('#ventasTable')) {
                        dataTable.clear();
                        dataTable.row.add(['', '', '', '', '', '', '', 'Error al cargar los datos']).draw();
                    } else {
                        $("#resultados_ventas").html('<tr><td colspan="8" class="text-center">Error al cargar los datos</td></tr>');
                    }
                });
            }

            function actualizarEstadisticas() {
                const fecha_desde = $('#fecha_desde').val();
                const fecha_hasta = $('#fecha_hasta').val();

                $.post("funciones/historial_ventas.php", {
                    funcion: "ObtenerEstadisticas",
                    fecha_desde: fecha_desde,
                    fecha_hasta: fecha_hasta
                }, function (response) {
                    console.log('Respuesta estadísticas:', response); // Debug

                    // CORRECCIÓN: Verificar si response es un objeto válido
                    if (response && response.success === true) {
                        $('#total-ventas').text(response.total_ventas);
                        $('#total-ingresos').text('$' + parseFloat(response.total_ingresos).toFixed(2));
                    } else {
                        console.error('Error en estadísticas:', response ? response.error : 'Respuesta inválida');
                        // Fallback: calcular manualmente desde la tabla visible
                        calcularEstadisticasManual();
                    }
                }).fail(function (xhr, status, error) {
                    console.error('Error AJAX estadísticas:', error);
                    calcularEstadisticasManual();
                });
            }

            // Función de respaldo para calcular estadísticas manualmente
            function calcularEstadisticasManual() {
                let totalVentas = 0;
                let totalIngresos = 0;

                $('#ventasTable tbody tr').not('.dataTables_empty').each(function () {
                    totalVentas++;
                    const textoTotal = $(this).find('td:eq(4)').text().trim();
                    const total = parseFloat(textoTotal.replace('$', '').replace(',', '')) || 0;
                    totalIngresos += total;
                });

                $('#total-ventas').text(totalVentas);
                $('#total-ingresos').text('$' + totalIngresos.toFixed(2));
            }

            // Actualizar tabla automáticamente al cambiar fechas
            $('#fecha_desde, #fecha_hasta').change(function () {
                cargarTabla();
            });

            // Validar que la fecha hasta no sea mayor que la fecha actual
            $('#fecha_hasta').on('change', function () {
                const fechaHasta = new Date($(this).val());
                const fechaActual = new Date();

                if (fechaHasta > fechaActual) {
                    Swal.fire({
                        title: 'Fecha inválida',
                        text: 'No puede seleccionar una fecha futura',
                        icon: 'warning',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#67C090'
                    }).then(() => {
                        // Establecer la fecha actual
                        $(this).val('<?php echo $fecha_actual; ?>');
                        cargarTabla();
                    });
                }
            });

            $('#imprimir-reporte').click(function () {
                // Obtener estadísticas actuales para el reporte
                const fecha_desde = $('#fecha_desde').val();
                const fecha_hasta = $('#fecha_hasta').val();

                let textoFechas = 'Todas las fechas';
                if (fecha_desde && fecha_hasta) {
                    textoFechas = `Del ${formatearFecha(fecha_desde)} al ${formatearFecha(fecha_hasta)}`;
                } else if (fecha_desde) {
                    textoFechas = `Desde ${formatearFecha(fecha_desde)}`;
                } else if (fecha_hasta) {
                    textoFechas = `Hasta ${formatearFecha(fecha_hasta)}`;
                }

                // Obtener fecha y hora local del cliente
                const ahora = new Date();
                const fechaLocal = formatearFechaHoraLocal(ahora);

                $('#fecha-rango-impresion').text(textoFechas);
                $('#fecha-generacion').text(fechaLocal);

                // Obtener estadísticas actuales para mostrar en el reporte
                const totalVentas = $('#total-ventas').text();
                const totalIngresos = $('#total-ingresos').text();
                $('#total-ventas-impresion').text(totalVentas);
                $('#total-ingresos-impresion').text(totalIngresos);

                // Imprimir directamente
                window.print();
            });

            function formatearFecha(fecha) {
                const partes = fecha.split('-');
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }

            function formatearFechaHoraLocal(fecha) {
                const dia = String(fecha.getDate()).padStart(2, '0');
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const año = fecha.getFullYear();
                const horas = String(fecha.getHours()).padStart(2, '0');
                const minutos = String(fecha.getMinutes()).padStart(2, '0');

                return `${dia}/${mes}/${año} ${horas}:${minutos}`;
            }

            $(document).on('click', '.ver-detalles', function () {
                const id = $(this).attr('idpedido');
                currentPedidoId = id;
                cargarDetallesPedido(id);
            });

            $(document).on('click', '.imprimir-ticket', function () {
                const id = $(this).attr('idpedido');
                imprimirTicket(id);
            });

            function cargarDetallesPedido(id) {
                $.post("funciones/historial_ventas.php", {
                    funcion: "DetallesPedido",
                    id: id
                }, function (response) {
                    $('#modal-body-pedido').html(response);
                    $('#pedidoModal').modal('show');
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar los detalles del pedido", "error");
                });
            }

            $('#imprimir-ticket').click(function () {
                if (currentPedidoId) {
                    imprimirTicket(currentPedidoId);
                }
            });

            function imprimirTicket(id) {
                $.post("funciones/historial_ventas.php", {
                    funcion: "GenerarTicket",
                    id: id
                }, function (response) {
                    // Crear una ventana nueva para imprimir el ticket
                    const ventana = window.open('', '_blank');
                    ventana.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Ticket de Venta</title>
                            <style>
                                body {
                                    font-family: 'Courier New', monospace;
                                    font-size: 12px;
                                    margin: 0;
                                    padding: 10px;
                                }
                                .ticket-container {
                                    max-width: 300px;
                                    margin: 0 auto;
                                }
                                .ticket-header {
                                    text-align: center;
                                    border-bottom: 1px dashed #000;
                                    padding-bottom: 10px;
                                    margin-bottom: 10px;
                                }
                                .ticket-details {
                                    margin-bottom: 10px;
                                }
                                .ticket-items {
                                    width: 100%;
                                    margin-bottom: 10px;
                                }
                                .ticket-items th {
                                    border-bottom: 1px solid #000;
                                    padding: 3px;
                                }
                                .ticket-items td {
                                    padding: 3px;
                                }
                                .ticket-total {
                                    border-top: 1px dashed #000;
                                    padding-top: 5px;
                                    font-weight: bold;
                                    text-align: right;
                                }
                                .ticket-footer {
                                    text-align: center;
                                    margin-top: 10px;
                                    font-size: 0.7rem;
                                    color: #666;
                                }
                                .nota-platillo {
                                    font-size: 0.7rem;
                                    color: #666;
                                    font-style: italic;
                                    display: block;
                                }
                                @media print {
                                    body {
                                        margin: 0;
                                        padding: 0;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="ticket-container">
                                ${response}
                            </div>
                            <script>
                                window.onload = function() {
                                    window.print();
                                    setTimeout(function() {
                                        window.close();
                                    }, 500);
                                };
                            <\/script>
                        </body>
                        </html>
                    `);
                    ventana.document.close();
                }).fail(function () {
                    Swal.fire("Error", "No se pudo generar el ticket", "error");
                });
            }

            // Establecer fechas por defecto (últimos 30 días)
            const hoy = new Date();
            const hace30Dias = new Date();
            hace30Dias.setDate(hoy.getDate() - 30);

            $('#fecha_desde').val(hace30Dias.toISOString().split('T')[0]);
            $('#fecha_hasta').val(hoy.toISOString().split('T')[0]);

            initTable();
        });
    </script>
</body>

</html>