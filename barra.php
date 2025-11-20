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
    <title>Barra - Pizzería</title>

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

        /* ESTILOS ESPECÍFICOS DE BARRA */
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
            background-color: var(--barra-color);
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
            background: linear-gradient(135deg, var(--barra-color), #2980b9);
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

        /* Área de barra */
        .area-barra {
            border: 3px solid var(--barra-color);
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
            margin: 20px;
        }

        .area-header {
            background: var(--barra-color);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
        }

        /* NUEVO DISEÑO COMPACTO PARA PEDIDOS */
        .pedidos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .pedido-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--barra-color);
            transition: all 0.3s;
            min-height: 200px;
            display: flex;
            flex-direction: column;
        }

        .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .mesa-info {
            font-weight: bold;
            font-size: 1.3em;
            color: var(--danger-color);
        }

        .tiempo-info {
            font-size: 0.8em;
            color: #666;
            text-align: right;
        }

        .badge-tiempo {
            background: var(--barra-color);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .mesero-info {
            font-size: 0.85em;
            color: #555;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .productos-lista {
            flex: 1;
            margin: 8px 0;
            max-height: 150px;
            overflow-y: auto;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: 600;
            color: var(--danger-color);
            font-size: 0.9em;
            margin-bottom: 2px;
        }

        .producto-cantidad {
            color: #666;
            font-size: 0.8em;
        }

        .acciones-producto {
            display: flex;
            gap: 5px;
        }

        .btn-accion {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.75em;
            white-space: nowrap;
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
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 2px solid #f0f0f0;
        }

        .estado-pedido {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
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

        .sin-pedidos {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            grid-column: 1 / -1;
        }

        .estado-pendiente {
            background: #ffeb3b;
            color: #333;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.7em;
        }

        .estado-en_preparacion {
            background: #2196f3;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.7em;
        }

        .estado-terminado {
            background: #4caf50;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.7em;
        }

        /* Indicador de actualización automática */
        .auto-refresh-indicator {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Scroll personalizado */
        .productos-lista::-webkit-scrollbar {
            width: 4px;
        }

        .productos-lista::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .productos-lista::-webkit-scrollbar-thumb {
            background: var(--barra-color);
            border-radius: 2px;
        }

        .productos-lista::-webkit-scrollbar-thumb:hover {
            background: #138496;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .pedidos-grid {
                grid-template-columns: 1fr;
            }
            
            .pedido-card {
                min-height: auto;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 15px;
            }
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
            <div class="banner">
                <h1><i class="bi bi-cup-straw"></i> BARRA - Bebidas</h1>
            </div>

            <div class="header-actions">
                <div>
                    
                </div>
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card">
                            <div class="stats-number" id="total-pedidos">0</div>
                            <small>Total Pedidos</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="pedidos-pendientes">0</div>
                            <small>Pendientes</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="pedidos-preparacion">0</div>
                            <small>En Preparación</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de Barra -->
            <div class="area-barra">
                <div class="area-header">
                    <i class="bi bi-cup-straw"></i> PEDIDOS DE BARRA
                </div>
                <div class="pedidos-grid" id="pedidos-barra-container">
                    <!-- Los pedidos de barra se cargarán aquí -->
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

        // Inicialización del menú
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

            if (window.innerWidth <= 992) {
                closeMobileSidebar();
            }
        });

        // ========== FUNCIONES DE BARRA ==========
        const categoriasBarra = ['Bebidas'];
        let intervaloActualizacion = null;

        // Cargar pedidos de barra
        function cargarPedidosBarra() {
            $.ajax({
                url: 'funciones/barra.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosBarra'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    procesarPedidosBarra(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // Procesar y mostrar pedidos de barra
        function procesarPedidosBarra(pedidos) {
            const pedidosBarra = [];
            let totalPedidos = 0;
            let pedidosPendientes = 0;
            let pedidosPreparacion = 0;

            // Filtrar solo pedidos de barra
            pedidos.forEach(pedido => {
                const productosBarra = pedido.detalles.filter(detalle =>
                    categoriasBarra.some(categoria =>
                        detalle.categoria_nombre === categoria
                    )
                );

                if (productosBarra.length > 0) {
                    pedidosBarra.push({
                        ...pedido,
                        productos: productosBarra
                    });
                    totalPedidos++;

                    if (pedido.estado === 'recibida') {
                        pedidosPendientes++;
                    } else if (pedido.estado === 'en_preparacion') {
                        pedidosPreparacion++;
                    }
                }
            });

            actualizarEstadisticasBarra(totalPedidos, pedidosPendientes, pedidosPreparacion);
            mostrarPedidosBarra(pedidosBarra);
        }

        // Actualizar estadísticas de barra
        function actualizarEstadisticasBarra(total, pendientes, preparacion) {
            document.getElementById('total-pedidos').textContent = total;
            document.getElementById('pedidos-pendientes').textContent = pendientes;
            document.getElementById('pedidos-preparacion').textContent = preparacion;
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
                const card = crearTarjetaPedidoBarra(pedido);
                container.appendChild(card);
            });
        }

        // Crear tarjeta de pedido para barra
        function crearTarjetaPedidoBarra(pedido) {
            const card = document.createElement('div');
            card.className = 'pedido-card';

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
                        <i class="bi bi-play-circle"></i> Iniciar
                    </button>
                `;
            } else if (pedido.estado === 'en_preparacion' && !todosTerminados) {
                botonesPedido = `
                    <button class="btn-accion btn-terminar" onclick="cambiarEstadoPedidoCompleto(${pedido.id_pedido}, 'finalizada')">
                        <i class="bi bi-check-circle"></i> Finalizar
                    </button>
                `;
            } else if (todosTerminados) {
                botonesPedido = `
                    <button class="btn-accion btn-entregar" onclick="cambiarEstadoPedidoCompleto(${pedido.id_pedido}, 'finalizada')">
                        <i class="bi bi-check-all"></i> Listo
                    </button>
                `;
            }

            card.innerHTML = `
                <div class="pedido-header">
                    <div>
                        <div class="mesa-info">
                            Mesa ${pedido.numero_mesa || 'Sin mesa'}
                            <span class="badge-tiempo">${diferenciaMinutos} min</span>
                        </div>
                        <div class="mesero-info">
                            <i class="bi bi-person"></i> ${pedido.nombre_mesero}
                        </div>
                    </div>
                    <div class="tiempo-info">
                        ${fechaCreacion.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}
                    </div>
                </div>
                
                <div class="productos-lista">
                    ${generarListaProductosBarra(pedido.productos, pedido.id_pedido)}
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

        // Generar lista de productos para barra
        function generarListaProductosBarra(productos, idPedido) {
            let html = '';

            productos.forEach((producto, index) => {
                const estadoProducto = producto.estado_producto || 'pendiente';
                let btnEstado = '';

                if (estadoProducto === 'pendiente') {
                    btnEstado = `
                        <button class="btn-accion btn-preparar" onclick="cambiarEstadoProducto(${idPedido}, ${producto.id_detalle}, 'en_preparacion')">
                            <i class="bi bi-play-circle"></i> Preparar.
                        </button>
                    `;
                } else if (estadoProducto === 'en_preparacion') {
                    btnEstado = `
                        <button class="btn-accion btn-terminar" onclick="cambiarEstadoProducto(${idPedido}, ${producto.id_detalle}, 'terminado')">
                            <i class="bi bi-check-circle"></i> Listo
                        </button>
                    `;
                } else if (estadoProducto === 'terminado') {
                    btnEstado = `
                        <span class="estado-terminado">
                            <i class="bi bi-check-all"></i> TERMINADO
                        </span>
                    `;
                }

                html += `
                    <div class="producto-item">
                        <div class="producto-info">
                            <div class="producto-nombre">${producto.nombre_platillo}</div>
                            <div class="producto-cantidad">
                                Cant: ${producto.cantidad} • 
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
        function cambiarEstadoProducto(idPedido, idDetalle, nuevoEstado) {
            $.ajax({
                url: 'funciones/barra.php',
                type: 'POST',
                data: {
                    funcion: 'CambiarEstadoProducto',
                    id_pedido: idPedido,
                    id_detalle: idDetalle,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Estado actualizado!',
                            text: 'El estado del producto ha sido actualizado',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090',
                            timer: 1500
                        }).then(() => {
                            cargarPedidosBarra();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al actualizar el estado', 'error');
                }
            });
        }

        // Cambiar estado completo del pedido
        function cambiarEstadoPedidoCompleto(idPedido, nuevoEstado) {
            $.ajax({
                url: 'funciones/barra.php',
                type: 'POST',
                data: {
                    funcion: 'CambiarEstadoPedidoCompleto',
                    id_pedido: idPedido,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json',
                success: function (data) {
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
                            cargarPedidosBarra();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al actualizar el pedido', 'error');
                }
            });
        }

        // ========== INICIALIZACIÓN ==========
        $(document).ready(function () {
            cargarPedidosBarra();
            intervaloActualizacion = setInterval(cargarPedidosBarra, 5000);
        });

        window.addEventListener('beforeunload', function () {
            if (intervaloActualizacion) {
                clearInterval(intervaloActualizacion);
            }
        });
    </script>
</body>

</html>