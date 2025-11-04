<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina y Barra - Pizzería</title>

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
            --cocina-color: #DC3545;
            --barra-color: #17A2B8;
            --sidebar-width: 220px;
            --sidebar-collapsed: 60px;
        }

        /* ESTILOS DEL MENÚ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--danger-color);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
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

        /* CONTENIDO PRINCIPAL CON MENÚ */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background-color: var(--secondary-color);
            padding: 20px;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed);
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
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
        }

        /* TUS ESTILOS ORIGINALES DE COCINA */
        body {
            background-color: var(--secondary-color) !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0 !important;
            margin: 0 !important;
        }

        .container-custom {
            max-width: 1400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 0 auto;
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
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Filtros */
        .filtros-container {
            padding: 20px;
            background: var(--secondary-color);
            border-radius: 10px;
            margin: 20px;
        }

        .btn-filtro {
            margin: 5px;
            border: 2px solid transparent;
        }

        .btn-filtro.active {
            border-color: var(--danger-color);
            font-weight: bold;
        }

        /* Áreas de trabajo */
        .areas-trabajo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .areas-trabajo {
                grid-template-columns: 1fr;
            }
        }

        .area {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
        }

        .area-cocina {
            border-color: var(--cocina-color);
        }

        .area-barra {
            border-color: var(--barra-color);
        }

        .area-header {
            background: var(--danger-color);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }

        .area-cocina .area-header {
            background: var(--cocina-color);
        }

        .area-barra .area-header {
            background: var(--barra-color);
        }

        /* Tarjetas de pedido */
        .pedido-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }

        .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pedido-card.cocina {
            border-left-color: var(--cocina-color);
        }

        .pedido-card.barra {
            border-left-color: var(--barra-color);
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .mesa-info {
            font-weight: bold;
            font-size: 1.2em;
            color: var(--danger-color);
        }

        .tiempo-info {
            font-size: 0.9em;
            color: #666;
        }

        .estado-pedido {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .estado-recibida {
            background: #ffeb3b;
            color: #333;
        }

        .estado-preparacion {
            background: #2196f3;
            color: white;
        }

        .estado-finalizada {
            background: #4caf50;
            color: white;
        }

        .productos-lista {
            margin: 10px 0;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: bold;
            color: var(--danger-color);
        }

        .producto-cantidad {
            color: #666;
            font-size: 0.9em;
        }

        .acciones-producto {
            display: flex;
            gap: 5px;
        }

        .btn-accion {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
        }

        .btn-preparar {
            background: #28a745;
            color: white;
        }

        .btn-terminar {
            background: #17a2b8;
            color: white;
        }

        .btn-entregar {
            background: #ffc107;
            color: #333;
        }

        .pedido-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .sin-pedidos {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .badge-tiempo {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }

        .estado-pendiente {
            background: #ffeb3b;
            color: #333;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }

        .estado-en_preparacion {
            background: #2196f3;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }

        .estado-terminado {
            background: #4caf50;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }
    </style>
</head>

<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="croquis.php" class="sidebar-brand">
                <i class="bi bi-house-door-fill"></i>
                <span></span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="croquis.php">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Croquis</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="clientes.php">
                        <i class="bi bi-people-fill"></i>
                        <span>Clientes</span>
                    </a>
                </li>

                <!-- MENÚ DE PRODUCTOS -->
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Productos</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="menu_platillos.php">
                                <i class="bi bi-circle"></i>
                                <span>Menu/Platillos/Bebidas</span>
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="categorias.php">
                                <i class="bi bi-tags"></i>
                                <span>Categorías</span>
                            </a></li>
                        <li><a class="dropdown-item" href="unidades_medida.php">
                                <i class="bi bi-rulers"></i>
                                <span>Unidades de Medida</span>
                            </a></li>
                    </ul>
                </li>
                
                <!-- COCINA Y BARRA (ACTIVO) -->
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold active dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i>
                        <span>Cocina y barra</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="cocina.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Cocina</span>
                            </a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i>
                        <span>Ventas</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="cuentas_cobrar.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Estadisticas de Ventas</span>
                            </a></li>
                        <li><a class="dropdown-item" href="historial_ventas.php">
                                <i class="bi bi-receipt"></i>
                                <span>Historial de Ventas</span>
                            </a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav mt-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span>Mi Cuenta</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="usuarios.php">
                                <i class="bi bi-people"></i>
                                <span>Usuarios</span>
                            </a></li>
                        <li><a class="dropdown-item" href="roles.php">
                                <i class="bi bi-shield-lock"></i>
                                <span>Roles</span>
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Cerrar sesión</span>
                            </a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <div class="container container-custom">
            <div class="banner">
                <h1><i class="bi bi-shop"></i> Cocina y Barra</h1>
            </div>

            <div class="header-actions">
                <div>
                    <button class="btn-custom" id="actualizar-pedidos">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar Pedidos
                    </button>
                </div>
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card">
                            <div class="stats-number" id="total-pedidos">0</div>
                            <small>Total Pedidos</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="pedidos-cocina">0</div>
                            <small>En Cocina</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="pedidos-barra">0</div>
                            <small>En Barra</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros-container">
                <h5>Filtrar por Área:</h5>
                <div>
                    <button class="btn btn-outline-primary btn-filtro active" data-area="todas">
                        <i class="bi bi-grid-3x3-gap"></i> Todas las Áreas
                    </button>
                    <button class="btn btn-outline-danger btn-filtro" data-area="cocina">
                        <i class="bi bi-fire"></i> Cocina (Pizzas/Complementos/Postres)
                    </button>
                    <button class="btn btn-outline-info btn-filtro" data-area="barra">
                        <i class="bi bi-cup-straw"></i> Barra (Bebidas)
                    </button>
                </div>
            </div>

            <!-- Áreas de Trabajo -->
            <div class="areas-trabajo">
                <!-- Cocina -->
                <div class="area area-cocina">
                    <div class="area-header">
                        <i class="bi bi-fire"></i> COCINA - Pizzas, Complementos y Postres
                    </div>
                    <div id="pedidos-cocina-container">
                        <!-- Los pedidos de cocina se cargarán aquí -->
                    </div>
                </div>

                <!-- Barra -->
                <div class="area area-barra">
                    <div class="area-header">
                        <i class="bi bi-cup-straw"></i> BARRA - Bebidas
                    </div>
                    <div id="pedidos-barra-container">
                        <!-- Los pedidos de barra se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ========== FUNCIONES DEL MENÚ ==========
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

        // Inicialización del menú
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', toggleMobileSidebar);
            mainContent.addEventListener('click', closeMobileSidebar);

            // Ajustar según el tamaño de pantalla
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

        // ========== FUNCIONES DE COCINA ==========
        let filtroActual = 'todas';
        let intervaloActualizacion = null;

        const categoriasCocina = ['Pizzas', 'Complementos', 'Postres'];
        const categoriasBarra = ['Bebidas'];

        // Cargar todos los pedidos
        function cargarPedidos() {
            console.log('Cargando pedidos...');
            $.ajax({
                url: 'funciones/cocina.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosCocina'
                },
                dataType: 'json',
                success: function(data) {
                    console.log('Pedidos recibidos:', data);
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    procesarPedidos(data);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // Procesar y mostrar pedidos
        function procesarPedidos(pedidos) {
            const pedidosCocina = [];
            const pedidosBarra = [];
            let totalPedidos = 0;
            let pedidosEnCocina = 0;
            let pedidosEnBarra = 0;

            // Separar pedidos por área
            pedidos.forEach(pedido => {
                const productosCocina = pedido.detalles.filter(detalle => 
                    categoriasCocina.some(categoria => 
                        detalle.categoria_nombre === categoria
                    )
                );
                
                const productosBarra = pedido.detalles.filter(detalle => 
                    categoriasBarra.some(categoria => 
                        detalle.categoria_nombre === categoria
                    )
                );

                // Solo mostrar pedidos que tengan productos en esta área
                if (productosCocina.length > 0) {
                    pedidosCocina.push({
                        ...pedido,
                        productos: productosCocina
                    });
                    pedidosEnCocina++;
                }

                if (productosBarra.length > 0) {
                    pedidosBarra.push({
                        ...pedido,
                        productos: productosBarra
                    });
                    pedidosEnBarra++;
                }

                totalPedidos++;
            });

            // Actualizar estadísticas
            actualizarEstadisticas(totalPedidos, pedidosEnCocina, pedidosEnBarra);

            // Mostrar pedidos según el filtro
            mostrarPedidosCocina(pedidosCocina);
            mostrarPedidosBarra(pedidosBarra);
        }

        // Actualizar estadísticas
        function actualizarEstadisticas(total, cocina, barra) {
            document.getElementById('total-pedidos').textContent = total;
            document.getElementById('pedidos-cocina').textContent = cocina;
            document.getElementById('pedidos-barra').textContent = barra;
        }

        // Mostrar pedidos de cocina
        function mostrarPedidosCocina(pedidos) {
            const container = document.getElementById('pedidos-cocina-container');
            
            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="sin-pedidos">
                        <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                        <p>No hay pedidos pendientes para cocina</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            
            pedidos.forEach(pedido => {
                const card = crearTarjetaPedido(pedido, 'cocina');
                container.appendChild(card);
            });
        }

        // Mostrar pedidos de barra
        function mostrarPedidosBarra(pedidos) {
            const container = document.getElementById('pedidos-barra-container');
            
            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="sin-pedidos">
                        <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                        <p>No hay pedidos pendientes para barra</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            
            pedidos.forEach(pedido => {
                const card = crearTarjetaPedido(pedido, 'barra');
                container.appendChild(card);
            });
        }

        // Crear tarjeta de pedido
        function crearTarjetaPedido(pedido, area) {
            const card = document.createElement('div');
            card.className = `pedido-card ${area}`;
            
            // Calcular tiempo transcurrido
            const fechaCreacion = new Date(pedido.fecha_creacion);
            const ahora = new Date();
            const diferenciaMinutos = Math.floor((ahora - fechaCreacion) / (1000 * 60));
            
            // Verificar si todos los productos están terminados
            const todosTerminados = pedido.productos.every(producto => 
                producto.estado_producto === 'terminado'
            );
            
            // Determinar botones del pedido
            let botonesPedido = '';
            if (pedido.estado === 'recibida') {
                botonesPedido = `
                    <button class="btn-accion btn-preparar" onclick="cambiarEstadoPedidoCompleto(${pedido.id_pedido}, 'en_preparacion')">
                        <i class="bi bi-play-circle"></i> Iniciar Preparación
                    </button>
                `;
            } else if (pedido.estado === 'en_preparacion' && !todosTerminados) {
                botonesPedido = `
                    <button class="btn-accion btn-terminar" onclick="cambiarEstadoPedidoCompleto(${pedido.id_pedido}, 'finalizada')">
                        <i class="bi bi-check-circle"></i> Marcar como Finalizado
                    </button>
                `;
            } else if (todosTerminados) {
                botonesPedido = `
                    <button class="btn-accion btn-entregar" onclick="cambiarEstadoPedidoCompleto(${pedido.id_pedido}, 'finalizada')">
                        <i class="bi bi-check-all"></i> Pedido Listo
                    </button>
                `;
            }
            
            card.innerHTML = `
                <div class="pedido-header">
                    <div class="mesa-info">
                        Mesa ${pedido.numero_mesa || 'Sin mesa'}
                        <span class="badge-tiempo">${diferenciaMinutos} min</span>
                    </div>
                    <div class="tiempo-info">
                        ${fechaCreacion.toLocaleTimeString()}
                    </div>
                </div>
                
                <div class="mesero-info">
                    <small>Mesero: ${pedido.nombre_mesero}</small>
                </div>
                
                <div class="productos-lista" id="productos-${pedido.id_pedido}-${area}">
                    ${generarListaProductos(pedido.productos, pedido.id_pedido, area)}
                </div>
                
                <div class="pedido-actions">
                    <span class="estado-pedido estado-${pedido.estado}">
                        ${pedido.estado.replace('_', ' ').toUpperCase()}
                    </span>
                    ${botonesPedido}
                </div>
            `;
            
            return card;
        }

        // Generar lista de productos
        function generarListaProductos(productos, idPedido, area) {
            let html = '';
            
            productos.forEach((producto, index) => {
                const estadoProducto = producto.estado_producto || 'pendiente';
                let btnEstado = '';
                
                if (estadoProducto === 'pendiente') {
                    btnEstado = `
                        <button class="btn-accion btn-preparar" onclick="cambiarEstadoProducto(${idPedido}, ${producto.id_detalle}, 'en_preparacion', '${area}')">
                            <i class="bi bi-play-circle"></i> Preparar
                        </button>
                    `;
                } else if (estadoProducto === 'en_preparacion') {
                    btnEstado = `
                        <button class="btn-accion btn-terminar" onclick="cambiarEstadoProducto(${idPedido}, ${producto.id_detalle}, 'terminado', '${area}')">
                            <i class="bi bi-check-circle"></i> Terminar
                        </button>
                    `;
                } else if (estadoProducto === 'terminado') {
                    btnEstado = `
                        <span class="btn-accion btn-entregar" style="background: #4caf50; color: white;">
                            <i class="bi bi-check-all"></i> Listo
                        </span>
                    `;
                }
                
                html += `
                    <div class="producto-item">
                        <div class="producto-info">
                            <div class="producto-nombre">${producto.nombre_platillo}</div>
                            <div class="producto-cantidad">
                                Cantidad: ${producto.cantidad} | 
                                <span class="estado-${estadoProducto}">
                                    ${estadoProducto.replace('_', ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                        <div class="acciones-producto">
                            ${btnEstado}
                        </div>
                    </div>
                `;
            });
            
            return html;
        }

        // Cambiar estado de un producto individual
        function cambiarEstadoProducto(idPedido, idDetalle, nuevoEstado, area) {
            $.ajax({
                url: 'funciones/cocina.php',
                type: 'POST',
                data: {
                    funcion: 'CambiarEstadoProducto',
                    id_pedido: idPedido,
                    id_detalle: idDetalle,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Estado actualizado!',
                            text: 'El estado del producto ha sido actualizado',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090',
                            timer: 1500
                        }).then(() => {
                            cargarPedidos(); // Recargar todos los pedidos
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al actualizar el estado', 'error');
                }
            });
        }

        // Cambiar estado completo del pedido
        function cambiarEstadoPedidoCompleto(idPedido, nuevoEstado) {
            $.ajax({
                url: 'funciones/cocina.php',
                type: 'POST',
                data: {
                    funcion: 'CambiarEstadoPedidoCompleto',
                    id_pedido: idPedido,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        let mensaje = '';
                        if (nuevoEstado === 'en_preparacion') {
                            mensaje = 'El pedido está ahora en preparación';
                        } else if (nuevoEstado === 'finalizada') {
                            mensaje = 'El pedido ha sido marcado como finalizado';
                        }
                        
                        Swal.fire({
                            title: '¡Pedido actualizado!',
                            text: mensaje,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090',
                            timer: 1500
                        }).then(() => {
                            cargarPedidos(); // Recargar todos los pedidos
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al actualizar el pedido', 'error');
                }
            });
        }

        // Aplicar filtro
        function aplicarFiltro(area) {
            filtroActual = area;
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-area="${area}"]`).classList.add('active');
            
            // Mostrar/ocultar áreas según el filtro
            const areas = document.querySelectorAll('.area');
            areas.forEach(areaElem => {
                if (filtroActual === 'todas') {
                    areaElem.style.display = 'block';
                } else if (filtroActual === 'cocina') {
                    areaElem.style.display = areaElem.classList.contains('area-cocina') ? 'block' : 'none';
                } else if (filtroActual === 'barra') {
                    areaElem.style.display = areaElem.classList.contains('area-barra') ? 'block' : 'none';
                }
            });
        }

        // ========== INICIALIZACIÓN ==========
        $(document).ready(function () {
            // Cargar pedidos al inicio
            cargarPedidos();
            
            // Configurar actualización automática cada 30 segundos
            intervaloActualizacion = setInterval(cargarPedidos, 30000);
            
            // Event listeners
            document.getElementById('actualizar-pedidos').addEventListener('click', cargarPedidos);
            
            // Filtros
            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.addEventListener('click', function() {
                    aplicarFiltro(this.dataset.area);
                });
            });

            // Aplicar filtro por defecto
            aplicarFiltro('todas');
        });

        // Limpiar intervalo al salir de la página
        window.addEventListener('beforeunload', function() {
            if (intervaloActualizacion) {
                clearInterval(intervaloActualizacion);
            }
        });
    </script>
</body>

</html>