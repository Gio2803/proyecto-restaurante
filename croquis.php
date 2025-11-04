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
    <title>Croquis Pizzería (Touch)</title>

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
            /* 🔵 Mantener azul para otros elementos */
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
            /* 🔵 Mantener azul */
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

        .croquis-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        .croquis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 20px;
            justify-content: center;
        }

        .mesa {
            width: 70px;
            height: 70px;
            border-radius: 20%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            touch-action: manipulation;
            user-select: none;
            transition: all 0.3s;
        }

        .mesa:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .mesa.ocupada {
            background: #DC3545;
            /* 🔴 SOLO CAMBIO AQUÍ: Rojo para mesas ocupadas */
        }

        .mesa.con-pedido {
            background: #FFA500;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 1050;
        }

        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-content-large {
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-top: 0;
            font-size: 1.5em;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
        }

        .botones {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-content button {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            touch-action: manipulation;
            transition: all 0.3s;
        }

        .confirmar {
            background: var(--primary-color);
            color: white;
        }

        .confirmar:hover {
            background: var(--light-color);
            transform: translateY(-2px);
        }

        .cancelar {
            background: var(--danger-color);
            /* 🔵 Mantener azul */
            color: white;
        }

        .cancelar:hover {
            background: #0f3259;
            /* 🔵 Mantener azul oscuro */
            transform: translateY(-2px);
        }

        .categorias-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .categoria-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .categoria-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .categoria-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .categoria-nombre {
            font-weight: bold;
            font-size: 1.2em;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
        }

        .categoria-descripcion {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .mesero-info {
            background: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .mesero-nombre {
            font-weight: bold;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
            font-size: 1.1em;
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }

        .producto-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #eee;
        }

        .producto-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: var(--primary-color);
        }

        .producto-nombre {
            font-weight: bold;
            font-size: 1em;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
            margin-bottom: 5px;
        }

        .producto-descripcion {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 8px;
            height: 40px;
            overflow: hidden;
        }

        .producto-precio {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        .pedidos-container {
            margin-top: 20px;
            border: 2px solid var(--secondary-color);
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
        }

        .lista-pedidos {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .item-pedido {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .item-pedido:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-producto {
            flex: 1;
            text-align: left;
        }

        .nombre-producto {
            font-weight: bold;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
            margin-bottom: 5px;
        }

        .detalles-producto {
            font-size: 0.8em;
            color: #666;
        }

        .acciones-producto {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-eliminar {
            background: #dc3545;
            /* 🔴 Mantener rojo para eliminar */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        .total-pedido {
            font-weight: bold;
            font-size: 1.2em;
            color: var(--danger-color);
            /* 🔵 Mantener azul */
            text-align: right;
            padding: 10px;
            border-top: 2px solid var(--primary-color);
            margin-top: 10px;
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

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 15px;
            }

            .btn-custom {
                width: 100%;
            }

            .croquis {
                grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
                gap: 15px;
            }

            .mesa {
                width: 70px;
                height: 70px;
                font-size: 1em;
            }

            .categorias-menu {
                grid-template-columns: 1fr;
            }

            .productos-grid {
                grid-template-columns: 1fr;
            }

            .item-pedido {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .acciones-producto {
                align-self: flex-end;
            }
        }

        .estado-pagado {
            background: #6f42c1;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }

        .cliente-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .cliente-item:hover {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
        }

        .cliente-nombre {
            font-weight: bold;
            color: var(--danger-color);
        }

        .cliente-telefono {
            font-size: 0.8em;
            color: #666;
        }

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .acciones-producto .btn-group {
            margin-left: 10px;
        }

        .acciones-producto .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .subtotal {
            font-weight: bold;
            color: var(--primary-color);
            min-width: 70px;
            text-align: right;
        }

        /* Agregar estos estilos en la sección CSS */
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .alert-warning i {
            color: #856404;
            margin-right: 8px;
        }

        /* Asegurar que los botones sean siempre visibles cuando corresponda */
        #btnAgregarMas {
            display: block !important;
        }

        #btnEnviarAdicionales {
            display: block !important;
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const existingContent = document.querySelector('.container-custom');
            const mainContent = document.getElementById('mainContent');

            if (existingContent && mainContent) {
                mainContent.appendChild(existingContent);
            }
        });
    </script>

    <div class="container container-custom">
        <div class="banner">
            <h1><i class="bi bi-geo-alt-fill"></i> Croquis de Mesas </h1>
        </div>

        <div class="header-actions">
            <div>
                <button class="btn-custom" id="ver-estadisticas">
                    <i class="bi bi-bar-chart-fill"></i> Ver Estadísticas
                </button>
                <button class="btn-custom" id="ver-pedidos">
                    <i class="bi bi-list-check"></i> Ver Pedidos
                </button>
            </div>
            <div class="d-none d-md-block">
                <div class="d-flex gap-2">
                    <div class="stats-card">
                        <div class="stats-number" id="total-mesas">14</div>
                        <small>Mesas Totales</small>
                    </div>
                    <div class="stats-card">
                        <div class="stats-number" id="mesas-ocupadas">0</div>
                        <small>Mesas Ocupadas</small>
                    </div>
                    <div class="stats-card">
                        <div class="stats-number" id="mesas-disponibles">14</div>
                        <small>Mesas Disponibles</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="croquis-container">
            <div class="croquis" id="croquis"></div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal" id="modalConfirmacion">
        <div class="modal-content">
            <h2 id="mesaSeleccionadaConfirm">Mesa X</h2>
            <p>¿Desea atender esta mesa?</p>

            <div class="botones">
                <button type="button" class="confirmar" id="btnConfirmarAtencion">✅ Sí, Atender Mesa</button>
                <button type="button" class="cancelar" id="btnCancelarConfirmacion">❌ Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Selección de Cliente -->
    <div class="modal" id="modalCliente">
        <div class="modal-content">
            <h2>Seleccionar Cliente - Mesa <span id="mesaSeleccionadaCliente">X</span></h2>

            <div class="mb-3">
                <label for="buscarCliente" class="form-label">Buscar Cliente:</label>
                <input type="text" id="buscarCliente" class="form-control" placeholder="Ingrese nombre del cliente...">
                <div id="resultadosClientes" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
            </div>

            <div class="text-center my-3">
                <strong>O</strong>
            </div>

            <div class="mb-3">
                <label for="clienteTemporal" class="form-label">Agregar Cliente Temporal:</label>
                <input type="text" id="clienteTemporal" class="form-control"
                    placeholder="Nombre del cliente (opcional)">
                <small class="text-muted">Si no encuentra al cliente, puede ingresar un nombre temporal</small>
            </div>

            <div class="botones">
                <button type="button" class="confirmar" id="btnConfirmarCliente">✅ Continuar</button>
                <button type="button" class="cancelar" id="btnCancelarCliente">❌ Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Menú -->
    <div class="modal" id="modalMenu">
        <div class="modal-content modal-content-large">
            <h2 id="mesaSeleccionadaMenu">Mesa X</h2>

            <!-- Información del mesero -->
            <div class="mesero-info">
                <i class="bi bi-person-circle"></i>
                <span class="mesero-nombre" id="nombreMesero">
                    <?php echo $_SESSION['SISTEMA']['nombre'] ?? 'Mesero'; ?></span> <br>
                <small>Atendiendo esta mesa</small>
                <div id="infoClienteMesa" style="margin-top: 8px;"></div>
            </div>

            <!-- Categorías -->
            <h4>Seleccione una Categoría:</h4>
            <div class="categorias-menu" id="categoriasMenu">
                <!-- Las categorías se cargarán dinámicamente desde la BD -->
            </div>

            <!-- Productos -->
            <div id="productosContainer" style="display: none;">
                <h4 id="tituloCategoria">Productos de <span id="nombreCategoria"></span></h4>
                <button class="btn btn-secondary btn-sm mb-3" id="btnVolverCategorias">
                    <i class="bi bi-arrow-left"></i> Volver a Categorías
                </button>
                <div class="productos-grid" id="productosGrid">
                    <!-- Los productos se cargarán dinámicamente desde la BD -->
                </div>
            </div>

            <!-- Lista de Pedidos -->
            <div id="pedidosContainer" class="pedidos-container" style="display: none;">
                <h4>📋 Lista de Pedidos - Mesa <span id="numeroMesaPedido"></span></h4>
                <div class="lista-pedidos" id="listaPedidos">
                    <!-- Los pedidos se agregarán dinámicamente aquí -->
                </div>
                <div class="total-pedido" id="totalPedido">
                    Total: $0.00
                </div>
                <div class="botones mt-3">
                    <button type="button" class="confirmar" id="btnEnviarOrden">
                        📤 Enviar Orden a Cocina
                    </button>
                    <button type="button" class="btn-custom" id="btnAgregarMas">
                        ➕ Agregar Más Productos
                    </button>
                    <button type="button" class="btn btn-warning" id="btnEnviarAdicionales" style="display: none;">
                        📤 Enviar Productos Adicionales
                    </button>
                </div>
            </div>

            <div class="botones mt-3">
                <button type="button" class="cancelar" id="btnCerrarMenu">❌ Cerrar Menú</button>
            </div>
        </div>
    </div>

    <!-- Modal de Pedidos Activos -->
    <div class="modal" id="modalPedidos">
        <div class="modal-content modal-content-large">
            <h2>📋 Pedidos Activos</h2>
            <div id="listaPedidosActivos">
                <!-- Los pedidos activos se cargarán aquí -->
            </div>
            <div class="botones mt-3">
                <button type="button" class="cancelar" id="btnCerrarPedidos">❌ Cerrar</button>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ========== VARIABLES GLOBALES ==========
        let mesaActual = null;
        let mesasOcupadas = 0;
        let idMesaActual = 0;
        let numeroMesaActual = 0;
        let categoriasData = [];
        let pedidos = [];
        let pedidoActualId = null;
        let mesasData = [];
        let clienteSeleccionado = null;
        let productosPendientesEnvio = []; // NUEVA VARIABLE para productos pendientes de enviar

        // Iconos para categorías
        const iconosCategorias = {
            'Bebidas': '🥤',
            'Pizzas': '🍕',
            'Postres': '🍰',
            'Complementos': '🍟'
        };

        // ========== FUNCIONES GLOBALES ==========

        function actualizarEstadisticas() {
            const totalMesas = mesasData.length;
            const ocupadas = mesasData.filter(mesa =>
                mesa.estado === 'ocupada' || mesa.estado === 'con-pedido'
            ).length;

            document.getElementById('mesas-ocupadas').textContent = ocupadas;
            document.getElementById('mesas-disponibles').textContent = totalMesas - ocupadas;
            document.getElementById('total-mesas').textContent = totalMesas;
        }

        // ========== FUNCIONES DE CLIENTES ==========

        function buscarClientes(termino) {
            if (termino.length < 2) {
                document.getElementById('resultadosClientes').innerHTML = '';
                return;
            }

            document.getElementById('resultadosClientes').innerHTML = `
        <div class="text-center p-2">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <small class="text-muted ms-2">Buscando clientes...</small>
        </div>
    `;

            if (window.busquedaClientesTimeout) {
                clearTimeout(window.busquedaClientesTimeout);
            }

            window.busquedaClientesTimeout = setTimeout(() => {
                $.ajax({
                    url: 'funciones/croquis.php',
                    type: 'POST',
                    data: {
                        funcion: 'BuscarClientes',
                        termino: termino
                    },
                    dataType: 'json',
                    timeout: 10000,
                    success: function (data) {
                        mostrarResultadosClientes(data);
                    },
                    error: function (xhr, status, error) {
                        if (status === 'timeout') {
                            document.getElementById('resultadosClientes').innerHTML = `
                        <div class="alert alert-warning p-2">
                            <small>La búsqueda tardó demasiado. Intente con menos caracteres.</small>
                        </div>
                    `;
                        } else {
                            console.error('Error al buscar clientes:', error);
                            document.getElementById('resultadosClientes').innerHTML = `
                        <div class="alert alert-danger p-2">
                            <small>Error al buscar clientes. Intente nuevamente.</small>
                        </div>
                    `;
                        }
                    },
                    complete: function () {
                        window.busquedaClientesTimeout = null;
                    }
                });
            }, 500);
        }

        function mostrarResultadosClientes(clientes) {
            const resultados = document.getElementById('resultadosClientes');

            if (clientes.length === 0) {
                resultados.innerHTML = `
            <div class="text-muted p-2">
                <small>No se encontraron clientes. Puede agregar un cliente temporal abajo.</small>
            </div>
        `;
                return;
            }

            resultados.innerHTML = '';
            clientes.forEach(cliente => {
                const item = document.createElement('div');
                item.className = 'cliente-item';
                item.innerHTML = `
            <div class="cliente-nombre">${cliente.nombre} ${cliente.apellidos || ''}</div>
            ${cliente.telefono ? `<div class="cliente-telefono">📞 ${cliente.telefono}</div>` : ''}
        `;
                item.addEventListener('click', () => {
                    seleccionarCliente(cliente);
                });
                resultados.appendChild(item);
            });
        }

        function seleccionarCliente(cliente) {
            clienteSeleccionado = cliente;
            document.getElementById('clienteTemporal').value = `${cliente.nombre} ${cliente.apellidos || ''}`;
            document.getElementById('resultadosClientes').innerHTML = `
        <div class="alert alert-success p-2">
            <strong>Cliente seleccionado:</strong> ${cliente.nombre} ${cliente.apellidos || ''}
            ${cliente.telefono ? `<br><small>Tel: ${cliente.telefono}</small>` : ''}
        </div>
    `;
        }

        function abrirModalCliente() {
            clienteSeleccionado = null;
            document.getElementById('mesaSeleccionadaCliente').textContent = numeroMesaActual;
            document.getElementById('buscarCliente').value = '';
            document.getElementById('clienteTemporal').value = '';
            document.getElementById('resultadosClientes').innerHTML = `
        <div class="text-muted p-2">
            <small>Ingrese al menos 2 caracteres para buscar clientes...</small>
        </div>
    `;

            setTimeout(() => {
                document.getElementById('buscarCliente').focus();
            }, 100);

            document.getElementById('modalCliente').style.display = "flex";
        }

        function cerrarModalCliente() {
            document.getElementById('modalCliente').style.display = "none";
            clienteSeleccionado = null;
        }

        function confirmarCliente() {
            const clienteTemporal = document.getElementById('clienteTemporal').value.trim();

            // DEBUG: Ver qué hay en clienteSeleccionado
            console.log('Cliente seleccionado antes de confirmar:', clienteSeleccionado);
            console.log('Cliente temporal ingresado:', clienteTemporal);

            // Si no se seleccionó un cliente de la BD pero se ingresó uno temporal
            if (!clienteSeleccionado && clienteTemporal) {
                clienteSeleccionado = {
                    id_cliente: null,
                    nombre: clienteTemporal,
                    apellidos: '',
                    telefono: ''
                };
            }

            // Si no hay cliente seleccionado ni temporal, usar "Cliente Temporal"
            if (!clienteSeleccionado && !clienteTemporal) {
                clienteSeleccionado = {
                    id_cliente: null,
                    nombre: 'Cliente Temporal',
                    apellidos: '',
                    telefono: ''
                };
            }

            // DEBUG: Ver qué quedó en clienteSeleccionado
            console.log('Cliente seleccionado después de confirmar:', clienteSeleccionado);

            cerrarModalCliente();
            procederConAtencionMesa();
        }

        // ========== FUNCIONES DE MESAS ==========

        function abrirModalConfirmacion(idMesa, numMesa, mesaElement) {
            mesaActual = mesaElement;
            idMesaActual = idMesa;
            numeroMesaActual = numMesa;
            document.getElementById('mesaSeleccionadaConfirm').textContent = "Mesa " + numMesa;
            document.getElementById('modalConfirmacion').style.display = "flex";
        }

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').style.display = "none";
            mesaActual = null;
        }

        function confirmarAtencion() {
            cerrarModalConfirmacion();
            abrirModalCliente();
        }

        function procederConAtencionMesa() {
            if (mesaActual && !mesaActual.classList.contains("ocupada")) {
                const datos = {
                    funcion: 'ActualizarEstadoMesa',
                    id_mesa: idMesaActual,
                    estado: 'ocupada'
                };

                // SOLO ACTUALIZAR ESTADO, NO ENVIAR DATOS DE CLIENTE
                $.ajax({
                    url: 'funciones/croquis.php',
                    type: 'POST',
                    data: datos,
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            mesaActual.classList.add("ocupada");

                            // Actualizar datos locales
                            const mesaIndex = mesasData.findIndex(m => m.id_mesa === idMesaActual);
                            if (mesaIndex !== -1) {
                                mesasData[mesaIndex].estado = 'ocupada';
                            }

                            actualizarEstadisticas();
                            limpiarPedidosAnteriores(idMesaActual);

                            Swal.fire({
                                title: '¡Mesa Atendida!',
                                html: `Mesa ${numeroMesaActual} lista para ordenar`,
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#67C090'
                            }).then(() => {
                                abrirModalMenu(idMesaActual, numeroMesaActual, mesaActual);
                            });
                        }
                    }
                });
            } else {
                abrirModalMenu(idMesaActual, numeroMesaActual, mesaActual);
            }
        }

        function abrirModalMenu(idMesa, numMesa, mesaElement) {
            idMesaActual = idMesa;
            numeroMesaActual = numMesa;
            mesaActual = mesaElement;

            document.getElementById('mesaSeleccionadaMenu').textContent = "Mesa " + numMesa;
            document.getElementById('numeroMesaPedido').textContent = numMesa;

            // VERIFICAR SI HAY PEDIDO ACTIVO PARA MOSTRAR EL CLIENTE
            verificarPedidoActivoParaCliente(idMesa);

            // LIMPIAR INDICADORES ANTERIORES
            const existingIndicator = document.getElementById('indicadorFinalizado');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            if (mesaElement && !mesaElement.classList.contains("ocupada") && !mesaElement.classList.contains("con-pedido")) {
                // Si es una mesa nueva, limpiar todo
                productosPendientesEnvio = [];
                pedidos = [];
                pedidoActualId = null;
                actualizarListaPedidos();
                limpiarPedidosAnteriores(idMesa);

                // Mostrar botones para nuevo pedido
                document.getElementById('btnEnviarAdicionales').style.display = 'none';
                document.getElementById('btnEnviarOrden').style.display = 'block';
                document.getElementById('btnAgregarMas').style.display = 'block';
            } else {
                // Si es una mesa existente, cargar el pedido
                verificarPedidoActivo(idMesa);
            }

            // SIEMPRE MOSTRAR CATEGORÍAS PRIMERO
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';

            cargarCategoriasDesdeBD();
            document.getElementById('modalMenu').style.display = "flex";
        }

        // NUEVA FUNCIÓN para obtener información del cliente del pedido activo
        function verificarPedidoActivoParaCliente(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidoActivoConCliente',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    const infoClienteElement = document.getElementById('infoClienteMesa');
                    if (infoClienteElement && data.existe) {
                        if (data.id_cliente && data.nombre_cliente !== 'Cliente Temporal') {
                            infoClienteElement.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 8px; background: rgba(103, 192, 144, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid var(--primary-color); margin-top: 8px;">
                            <i class="bi bi-person-check" style="color: var(--primary-color);"></i>
                            <div>
                                <strong style="color: var(--danger-color);">Cliente:</strong> ${data.nombre_cliente}
                                <br>
                                <small class="text-muted" style="font-size: 0.8em;">
                                    <i class="bi bi-star-fill" style="color: gold;"></i> Cliente registrado
                                </small>
                            </div>
                        </div>
                    `;
                        } else {
                            infoClienteElement.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 8px; background: rgba(108, 117, 125, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #6c757d; margin-top: 8px;">
                            <i class="bi bi-person" style="color: #6c757d;"></i>
                            <div>
                                <strong style="color: var(--danger-color);">Cliente:</strong> ${data.nombre_cliente}
                                <br>
                                <small class="text-muted" style="font-size: 0.8em;">
                                    <i class="bi bi-clock"></i> Cliente temporal
                                </small>
                            </div>
                        </div>
                    `;
                        }
                        infoClienteElement.style.display = 'block';
                    }
                }
            });
        }

        function cerrarModalMenu() {
            document.getElementById('modalMenu').style.display = "none";
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';
        }

        function volverACategorias() {
            document.getElementById('productosContainer').style.display = 'none';
            document.getElementById('categoriasMenu').style.display = 'grid';
        }

        // ========== FUNCIONES DE PEDIDOS ==========

        function verificarPedidoActivo(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'VerificarPedidoActivo',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    if (data.existe) {
                        pedidoActualId = data.id_pedido;
                        // VERIFICAR SI EL PEDIDO ESTÁ FINALIZADO
                        verificarEstadoPedido(pedidoActualId);
                        cargarDetallesPedido(pedidoActualId);
                        mostrarListaPedidos();

                        // MOSTRAR BOTONES DEPENDIENDO DEL ESTADO
                        if (data.estado === 'finalizada') {
                            // Si el pedido está finalizado, mostrar solo botón para agregar más
                            document.getElementById('btnEnviarAdicionales').style.display = 'block';
                            document.getElementById('btnEnviarOrden').style.display = 'none';
                            document.getElementById('btnAgregarMas').style.display = 'block';

                            // Mostrar mensaje de que el pedido está finalizado
                            Swal.fire({
                                title: 'Pedido Finalizado',
                                html: `El pedido de la <strong>Mesa ${numeroMesaActual}</strong> está marcado como finalizado.<br>Puede agregar productos adicionales si es necesario.`,
                                icon: 'info',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#67C090'
                            });
                        } else {
                            // Si el pedido está activo, mostrar botones normales
                            document.getElementById('btnEnviarAdicionales').style.display = 'block';
                            document.getElementById('btnEnviarOrden').style.display = 'none';
                            document.getElementById('btnAgregarMas').style.display = 'block';
                        }
                    } else {
                        // No hay pedido activo
                        productosPendientesEnvio = [];
                        pedidos = [];
                        pedidoActualId = null;
                        actualizarListaPedidos();
                        document.getElementById('btnEnviarAdicionales').style.display = 'none';
                        document.getElementById('btnEnviarOrden').style.display = 'block';
                        document.getElementById('btnAgregarMas').style.display = 'block';
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
        
        // NUEVA FUNCIÓN para verificar el estado del pedido
        function verificarEstadoPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerEstadoPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        // Actualizar la interfaz según el estado
                        actualizarInterfazSegunEstado(data.estado);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al verificar estado:', error);
                }
            });
        }

        // FUNCIÓN para actualizar la interfaz según el estado del pedido
        function actualizarInterfazSegunEstado(estado) {
            const btnEnviarAdicionales = document.getElementById('btnEnviarAdicionales');
            const btnEnviarOrden = document.getElementById('btnEnviarOrden');
            const btnAgregarMas = document.getElementById('btnAgregarMas');

            switch (estado) {
                case 'recibida':
                case 'en_preparacion':
                    // Pedido activo - mostrar botones normales
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';
                    break;

                case 'finalizada':
                    // Pedido finalizado - permitir agregar más productos
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';

                    // Mostrar indicador de pedido finalizado
                    mostrarIndicadorFinalizado();
                    break;

                case 'pagado':
                case 'cancelado':
                    // Pedido cerrado - no permitir modificaciones
                    btnEnviarAdicionales.style.display = 'none';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'none';
                    break;

                default:
                    // Estado desconocido - mostrar botones por defecto
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';
            }
        }
        function mostrarIndicadorFinalizado() {
            const pedidosContainer = document.getElementById('pedidosContainer');
            const existingIndicator = document.getElementById('indicadorFinalizado');

            if (!existingIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'indicadorFinalizado';
                indicator.className = 'alert alert-warning text-center';
                indicator.innerHTML = `
            <i class="bi bi-check-circle-fill"></i>
            <strong>Pedido Finalizado</strong> - Puede agregar productos adicionales si es necesario
        `;
                pedidosContainer.insertBefore(indicator, pedidosContainer.firstChild);
            }
        }

        function cargarDetallesPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerDetallesPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    // IMPORTANTE: NO cargar los productos existentes en productosPendientesEnvio
                    // Solo mantenerlos en la variable pedidos para referencia
                    pedidos = data.map(pedido => ({
                        ...pedido,
                        precio_unitario: parseFloat(pedido.precio_unitario) || 0,
                        cantidad: parseInt(pedido.cantidad) || 0,
                        subtotal: parseFloat(pedido.subtotal) || 0
                    }));

                    // Limpiar productos pendientes cuando se carga un pedido existente
                    productosPendientesEnvio = [];
                    actualizarListaPedidos();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // ========== FUNCIONES DE PEDIDOS ==========

        function agregarProductoAOrden(producto, cantidad) {
            const productoExistente = productosPendientesEnvio.find(p => p.id_platillo === producto.id_platillo);

            const precioUnitario = parseFloat(producto.precio) || 0;
            const cantidadNum = parseInt(cantidad) || 0;
            const subtotal = precioUnitario * cantidadNum;

            if (productoExistente) {
                productoExistente.cantidad += cantidadNum;
                productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio_unitario;
            } else {
                const nuevoPedido = {
                    id_platillo: producto.id_platillo,
                    nombre_platillo: producto.nombre,
                    precio_unitario: precioUnitario,
                    cantidad: cantidadNum,
                    subtotal: subtotal
                };
                productosPendientesEnvio.push(nuevoPedido);
            }

            actualizarListaPedidos();
            mostrarListaPedidos();

            Swal.fire({
                title: '¡Producto agregado!',
                html: `<strong>${cantidad} x ${producto.nombre}</strong><br>Agregado a la orden`,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#67C090',
                timer: 1500
            });
        }


        function eliminarProducto(index) {
            // Si la cantidad es mayor a 1, preguntar si quiere eliminar uno o todos
            if (productosPendientesEnvio[index].cantidad > 1) {
                Swal.fire({
                    title: 'Eliminar producto',
                    html: `¿Qué desea hacer con <strong>${productosPendientesEnvio[index].nombre_platillo}</strong>?`,
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Eliminar 1',
                    denyButtonText: 'Eliminar todos',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#67C090',
                    denyButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Eliminar solo una unidad
                        if (productosPendientesEnvio[index].cantidad > 1) {
                            productosPendientesEnvio[index].cantidad -= 1;
                            productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
                        } else {
                            productosPendientesEnvio.splice(index, 1);
                        }
                        actualizarListaPedidos();

                        if (productosPendientesEnvio.length === 0) {
                            ocultarListaPedidos();
                        }
                    } else if (result.isDenied) {
                        // Eliminar todas las unidades
                        productosPendientesEnvio.splice(index, 1);
                        actualizarListaPedidos();

                        if (productosPendientesEnvio.length === 0) {
                            ocultarListaPedidos();
                        }
                    }
                });
            } else {
                // Si solo hay una unidad, eliminar directamente
                productosPendientesEnvio.splice(index, 1);
                actualizarListaPedidos();

                if (productosPendientesEnvio.length === 0) {
                    ocultarListaPedidos();
                }
            }
        }

        function actualizarListaPedidos() {
            const listaPedidos = document.getElementById('listaPedidos');
            const totalPedido = document.getElementById('totalPedido');

            listaPedidos.innerHTML = '';

            let total = 0;

            // Mostrar solo los productos pendientes de envío
            productosPendientesEnvio.forEach((pedido, index) => {
                const precioUnitario = parseFloat(pedido.precio_unitario) || 0;
                const cantidad = parseInt(pedido.cantidad) || 0;
                const subtotal = parseFloat(pedido.subtotal) || 0;

                total += subtotal;

                const itemPedido = document.createElement('div');
                itemPedido.className = 'item-pedido';
                itemPedido.innerHTML = `
            <div class="info-producto">
                <div class="nombre-producto">${pedido.nombre_platillo}</div>
                <div class="detalles-producto">
                    Cantidad: ${cantidad} | Precio: $${precioUnitario.toFixed(2)} c/u
                </div>
            </div>
            <div class="acciones-producto">
                <span class="subtotal">$${subtotal.toFixed(2)}</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="disminuirCantidad(${index})" ${cantidad <= 1 ? 'disabled' : ''}>
                        <i class="bi bi-dash"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="aumentarCantidad(${index})">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="eliminarProducto(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
                listaPedidos.appendChild(itemPedido);
            });

            totalPedido.textContent = `Total: $${total.toFixed(2)}`;
        }

        // Nuevas funciones para modificar cantidades
        function aumentarCantidad(index) {
            productosPendientesEnvio[index].cantidad += 1;
            productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
            actualizarListaPedidos();
        }

        function disminuirCantidad(index) {
            if (productosPendientesEnvio[index].cantidad > 1) {
                productosPendientesEnvio[index].cantidad -= 1;
                productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
                actualizarListaPedidos();
            }
        }



        function agregarMasProductos() {
            // NO limpiar productosPendientesEnvio aquí, mantener los que el usuario está agregando
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';

            // Asegurarse de que las categorías se muestren
            document.getElementById('categoriasMenu').style.display = 'grid';
            document.getElementById('productosContainer').style.display = 'none';
        }


        function mostrarListaPedidos() {
            document.getElementById('pedidosContainer').style.display = 'block';
            document.getElementById('productosContainer').style.display = 'none';
            document.getElementById('categoriasMenu').style.display = 'none';
        }

        function ocultarListaPedidos() {
            document.getElementById('pedidosContainer').style.display = 'none';
        }

        function enviarOrden() {
            if (productosPendientesEnvio.length === 0) {
                Swal.fire({
                    title: 'Orden vacía',
                    text: 'Agregue al menos un producto a la orden',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090'
                });
                return;
            }

            // USAR clienteSeleccionado DIRECTAMENTE
            let id_cliente = clienteSeleccionado ? clienteSeleccionado.id_cliente : null;
            let nombre_cliente = 'Cliente Temporal';

            if (clienteSeleccionado) {
                nombre_cliente = clienteSeleccionado.nombre + (clienteSeleccionado.apellidos ? ' ' + clienteSeleccionado.apellidos : '');
            }

            const datos = {
                funcion: pedidoActualId ? 'ActualizarPedido' : 'CrearPedido',
                id_mesa: idMesaActual,
                id_mesero: <?php echo $_SESSION['SISTEMA']['id_usuario'] ?? 1; ?>,
                nombre_mesero: '<?php echo $_SESSION['SISTEMA']['nombre'] ?? 'Mesero'; ?>',
                pedidos: JSON.stringify(productosPendientesEnvio), // Solo los pendientes
                id_pedido: pedidoActualId,
                id_cliente: id_cliente,
                nombre_cliente: nombre_cliente
            };

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: datos,
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        pedidoActualId = data.id_pedido;

                        // LIMPIAR LOS PRODUCTOS PENDIENTES DESPUÉS DE ENVIAR
                        productosPendientesEnvio = [];

                        if (mesaActual) {
                            mesaActual.classList.add('con-pedido');
                            const mesaIndex = mesasData.findIndex(m => m.id_mesa === idMesaActual);
                            if (mesaIndex !== -1) {
                                mesasData[mesaIndex].estado = 'ocupada';
                            }
                            actualizarEstadisticas();
                        }

                        Swal.fire({
                            title: '¡Orden enviada!',
                            html: `Orden de la <strong>Mesa ${numeroMesaActual}</strong> enviada a cocina correctamente`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090'
                        }).then(() => {
                            cerrarModalMenu();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al enviar la orden', 'error');
                }
            });
        }

        function cargarPedidosActivos() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosActivos'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    mostrarPedidosActivos(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        function mostrarPedidosActivos(pedidos) {
            const lista = document.getElementById('listaPedidosActivos');
            lista.innerHTML = '';

            if (pedidos.length === 0) {
                lista.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                <p class="mt-2">No hay pedidos activos en este momento</p>
                <small class="text-muted">Todos los pedidos han sido completados</small>
            </div>
        `;
                return;
            }

            pedidos.forEach(pedido => {
                const card = document.createElement('div');
                card.className = 'pedidos-container mb-3';
                card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h5 class="mb-2">🍽️ Mesa ${pedido.numero_mesa}</h5>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div>
                            <i class="bi bi-person"></i>
                            <strong>Mesero:</strong> ${pedido.nombre_mesero}
                        </div>
                        <div>
                            <i class="bi bi-clock"></i>
                            <strong>Hora:</strong> ${new Date(pedido.fecha_creacion).toLocaleTimeString()}
                        </div>
                        <div>
                            <i class="bi bi-cash-coin"></i>
                            <strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="estado-pedido estado-${pedido.estado}">
                            ${pedido.estado.replace('_', ' ').toUpperCase()}
                        </span>
                        <small class="ms-2 text-muted">
                            ${new Date(pedido.fecha_creacion).toLocaleDateString()}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetallesPedido(${pedido.id_pedido})">
                        <i class="bi bi-eye"></i> Ver
                    </button>
                </div>
            </div>
        `;
                lista.appendChild(card);
            });
        }

        function verDetallesPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerDetallesPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    let detallesHTML = '<div class="mt-3"><h6>📋 Productos del Pedido:</h6>';

                    if (data.length === 0) {
                        detallesHTML += '<p class="text-muted">No hay productos en este pedido</p>';
                    } else {
                        detallesHTML += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        detallesHTML += '<thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';

                        data.forEach(detalle => {
                            detallesHTML += `
                        <tr>
                            <td>${detalle.nombre_platillo}</td>
                            <td class="text-center">${detalle.cantidad}</td>
                            <td class="text-end">$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(detalle.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                        });

                        const total = data.reduce((sum, detalle) => sum + parseFloat(detalle.subtotal), 0);
                        detallesHTML += `
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong>$${total.toFixed(2)}</strong></td>
                        </tr>
                    </tfoot>
                </table></div>`;
                    }

                    detallesHTML += '</div>';

                    Swal.fire({
                        title: 'Detalles del Pedido',
                        html: detallesHTML,
                        width: 800,
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#67C090'
                    });
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar los detalles del pedido', 'error');
                }
            });
        }

        // ========== FUNCIONES DE CUENTA Y LIBERACIÓN ==========

        function cargarPedidosFinalizados() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosFinalizados'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    mostrarPedidosFinalizados(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        function mostrarPedidosFinalizados(pedidos) {
            const lista = document.getElementById('listaPedidosActivos');
            lista.innerHTML = '';

            if (pedidos.length === 0) {
                lista.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                <p class="mt-2">No hay pedidos finalizados pendientes de pago</p>
                <small class="text-muted">Todos los pedidos han sido pagados</small>
            </div>
        `;
                return;
            }

            pedidos.forEach(pedido => {
                const card = document.createElement('div');
                card.className = 'pedidos-container mb-3';
                card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h5 class="mb-2">🍽️ Mesa ${pedido.numero_mesa}</h5>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div>
                            <i class="bi bi-person"></i>
                            <strong>Mesero:</strong> ${pedido.nombre_mesero}
                        </div>
                        <div>
                            <i class="bi bi-clock"></i>
                            <strong>Finalizado:</strong> ${new Date(pedido.fecha_actualizacion).toLocaleTimeString()}
                        </div>
                        <div>
                            <i class="bi bi-cash-coin"></i>
                            <strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="estado-pedido estado-${pedido.estado}">
                            ${pedido.estado.replace('_', ' ').toUpperCase()}
                        </span>
                        <small class="ms-2 text-muted">
                            ${new Date(pedido.fecha_actualizacion).toLocaleDateString()}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-sm btn-success mb-1" onclick="generarCuenta(${pedido.id_pedido}, ${pedido.numero_mesa})">
                        <i class="bi bi-cash"></i> Cobrar
                    </button>
                    <br>
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetallesPedido(${pedido.id_pedido})">
                        <i class="bi bi-eye"></i> Ver
                    </button>
                </div>
            </div>
        `;
                lista.appendChild(card);
            });
        }

        function generarCuenta(idPedido, numeroMesa) {
            Swal.fire({
                title: 'Generar Cuenta',
                html: `
            <div class="text-start">
                <p><strong>Mesa:</strong> ${numeroMesa}</p>
                <p>¿Desea generar la cuenta y liberar esta mesa?</p>
                <p class="text-warning"><small>Esta acción marcará el pedido como pagado y la mesa quedará disponible para nuevos clientes.</small></p>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Generar Cuenta',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                preConfirm: () => {
                    const mesa = mesasData.find(m => m.numero_mesa == numeroMesa);
                    return mesa ? mesa.id_mesa : null;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const idMesa = result.value;

                    $.ajax({
                        url: 'funciones/croquis.php',
                        type: 'POST',
                        data: {
                            funcion: 'GenerarCuenta',
                            id_pedido: idPedido,
                            id_mesa: idMesa
                        },
                        dataType: 'json',
                        success: function (data) {
                            if (data.success) {
                                const mesaElement = document.querySelector(`.mesa[data-numero="${numeroMesa}"]`);
                                if (mesaElement) {
                                    mesaElement.classList.remove('ocupada', 'con-pedido');
                                }

                                const mesaIndex = mesasData.findIndex(m => m.numero_mesa == numeroMesa);
                                if (mesaIndex !== -1) {
                                    mesasData[mesaIndex].estado = 'disponible';
                                }

                                actualizarEstadisticas();

                                Swal.fire({
                                    title: '¡Cuenta Generada!',
                                    html: `Cuenta de la <strong>Mesa ${numeroMesa}</strong> generada correctamente.<br>La mesa ha sido liberada.`,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar',
                                    confirmButtonColor: '#67C090'
                                }).then(() => {
                                    cargarPedidosFinalizados();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Error:', error);
                            Swal.fire('Error', 'Error al generar la cuenta', 'error');
                        }
                    });
                }
            });
        }

        function limpiarPedidosAnteriores(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'LimpiarPedidosAnteriores',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        console.log('Pedidos anteriores limpiados para mesa:', idMesa);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al limpiar pedidos anteriores:', error);
                }
            });
        }



        function enviarProductosAdicionales() {
            if (productosPendientesEnvio.length === 0) { // CAMBIAR: usar productosPendientesEnvio
                Swal.fire({
                    title: 'No hay productos',
                    text: 'Agregue al menos un producto a la orden',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090'
                });
                return;
            }

            // USAR clienteSeleccionado DIRECTAMENTE
            let id_cliente = clienteSeleccionado ? clienteSeleccionado.id_cliente : null;
            let nombre_cliente = 'Cliente Temporal';

            if (clienteSeleccionado) {
                nombre_cliente = clienteSeleccionado.nombre + (clienteSeleccionado.apellidos ? ' ' + clienteSeleccionado.apellidos : '');
            }

            const datos = {
                funcion: 'AgregarProductosPedido',
                id_pedido: pedidoActualId,
                pedidos: JSON.stringify(productosPendientesEnvio), // CAMBIAR: usar productosPendientesEnvio
                id_cliente: id_cliente,
                nombre_cliente: nombre_cliente
            };

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: datos,
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        // LIMPIAR LOS PRODUCTOS PENDIENTES DESPUÉS DE ENVIAR
                        productosPendientesEnvio = []; // CAMBIAR: limpiar productosPendientesEnvio
                        actualizarListaPedidos();

                        Swal.fire({
                            title: '¡Productos agregados!',
                            html: `Productos agregados al pedido de la <strong>Mesa ${numeroMesaActual}</strong> correctamente`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090'
                        }).then(() => {
                            cerrarModalMenu();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al agregar productos', 'error');
                }
            });
        }

        // ========== FUNCIONES DE CARGA DE DATOS ==========

        function cargarMesasDesdeBD() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerMesas'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    mesasData = data;
                    mostrarMesasEnCroquis();
                    actualizarEstadisticas();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar las mesas: ' + error, 'error');
                }
            });
        }

        function mostrarMesasEnCroquis() {
            const croquis = document.getElementById("croquis");
            croquis.innerHTML = '';

            mesasData.forEach(mesa => {
                const mesaElement = document.createElement("div");
                mesaElement.classList.add("mesa");
                mesaElement.setAttribute('data-numero', mesa.numero_mesa);

                if (mesa.capacidad <= 4) {
                    mesaElement.style.width = "60px";
                    mesaElement.style.height = "60px";
                } else if (mesa.capacidad <= 8) {
                    mesaElement.style.width = "75px";
                    mesaElement.style.height = "60px";
                } else {
                    mesaElement.style.width = "100px";
                    mesaElement.style.height = "60px";
                }

                if (mesa.estado === 'ocupada' || mesa.estado === 'con-pedido') {
                    mesaElement.classList.add("ocupada");
                }

                mesaElement.textContent = mesa.numero_mesa;

                mesaElement.addEventListener("click", () => {
                    if (mesa.estado === 'disponible') {
                        abrirModalConfirmacion(mesa.id_mesa, mesa.numero_mesa, mesaElement);
                    } else {
                        abrirModalMenu(mesa.id_mesa, mesa.numero_mesa, mesaElement);
                    }
                });

                croquis.appendChild(mesaElement);
            });
        }

        function cargarCategoriasDesdeBD() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerCategoriasMenu'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    categoriasData = data;
                    mostrarCategorias();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar las categorías: ' + error, 'error');
                }
            });
        }

        function mostrarCategorias() {
            const categoriasMenu = document.getElementById('categoriasMenu');
            categoriasMenu.innerHTML = '';
            document.getElementById('productosContainer').style.display = 'none';

            categoriasData.forEach(categoria => {
                const icono = iconosCategorias[categoria.nombre] || '📁';
                const card = document.createElement("div");
                card.classList.add("categoria-card");
                card.innerHTML = `
            <div class="categoria-icon">${icono}</div>
            <div class="categoria-nombre">${categoria.nombre}</div>
            <div class="categoria-descripcion">${categoria.descripcion || 'Sin descripción'}</div>
        `;
                card.addEventListener('click', () => {
                    cargarProductosPorCategoria(categoria.id_categoria, categoria.nombre);
                });
                categoriasMenu.appendChild(card);
            });
        }

        function cargarProductosPorCategoria(idCategoria, nombreCategoria) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerProductosPorCategoria',
                    id_categoria: idCategoria
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    mostrarProductos(data, nombreCategoria);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar los productos', 'error');
                }
            });
        }

        function mostrarProductos(productos, nombreCategoria) {
            const productosGrid = document.getElementById('productosGrid');
            productosGrid.innerHTML = '';
            document.getElementById('nombreCategoria').textContent = nombreCategoria;

            if (productos.length === 0) {
                productosGrid.innerHTML = '<p class="text-center">No hay productos en esta categoría</p>';
            } else {
                productos.forEach(producto => {
                    const card = document.createElement("div");
                    card.classList.add("producto-card");
                    card.innerHTML = `
                <div class="producto-nombre">${producto.nombre}</div>
                <div class="producto-descripcion">${producto.descripcion || 'Sin descripción'}</div>
                <div class="producto-precio">$${parseFloat(producto.precio).toFixed(2)}</div>
            `;
                    card.addEventListener('click', () => {
                        seleccionarProducto(producto);
                    });
                    productosGrid.appendChild(card);
                });
            }

            document.getElementById('categoriasMenu').style.display = 'none';
            document.getElementById('productosContainer').style.display = 'block';
        }

        function seleccionarProducto(producto) {
            Swal.fire({
                title: producto.nombre,
                html: `
            <p><strong>Descripción:</strong> ${producto.descripcion || 'Sin descripción'}</p>
            <p><strong>Precio:</strong> $${parseFloat(producto.precio).toFixed(2)}</p>
            <div class="mt-3">
                <label for="cantidadProducto" class="form-label">Cantidad:</label>
                <input type="number" id="cantidadProducto" class="form-control" value="1" min="1" max="20">
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Agregar a la orden',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#67C090',
                preConfirm: () => {
                    const cantidad = document.getElementById('cantidadProducto').value;
                    if (!cantidad || cantidad < 1 || cantidad > 20) {
                        Swal.showValidationMessage('La cantidad debe ser entre 1 y 20');
                        return false;
                    }
                    return cantidad;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const cantidad = result.value;
                    agregarProductoAOrden(producto, cantidad);
                }
            });
        }

        // ========== INICIALIZACIÓN ==========
        $(document).ready(function () {
            cargarMesasDesdeBD();

            document.getElementById('btnConfirmarAtencion').addEventListener('click', confirmarAtencion);
            document.getElementById('btnCancelarConfirmacion').addEventListener('click', cerrarModalConfirmacion);
            document.getElementById('btnCerrarMenu').addEventListener('click', cerrarModalMenu);
            document.getElementById('btnVolverCategorias').addEventListener('click', volverACategorias);
            document.getElementById('btnEnviarOrden').addEventListener('click', enviarOrden);
            document.getElementById('btnAgregarMas').addEventListener('click', agregarMasProductos);
            document.getElementById('btnEnviarAdicionales').addEventListener('click', enviarProductosAdicionales);
            document.getElementById('btnCerrarPedidos').addEventListener('click', () => {
                document.getElementById('modalPedidos').style.display = 'none';
            });

            document.getElementById('buscarCliente').addEventListener('input', function (e) {
                buscarClientes(e.target.value);
            });

            document.getElementById('btnConfirmarCliente').addEventListener('click', confirmarCliente);
            document.getElementById('btnCancelarCliente').addEventListener('click', cerrarModalCliente);

            document.getElementById('clienteTemporal').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    confirmarCliente();
                }
            });

            document.getElementById('ver-pedidos').addEventListener('click', () => {
                cargarPedidosFinalizados();
                document.getElementById('modalPedidos').style.display = 'flex';
            });

            $('#ver-estadisticas').click(function () {
                const totalMesas = mesasData.length;
                const ocupadas = mesasData.filter(mesa => mesa.estado === 'ocupada' || mesa.estado === 'con-pedido').length;
                const porcentaje = totalMesas > 0 ? Math.round((ocupadas / totalMesas) * 100) : 0;

                Swal.fire({
                    title: 'Estadísticas de Mesas',
                    html: `<div style="text-align: left; margin-top: 15px;">
                <p><strong>Mesas totales:</strong> ${totalMesas}</p>
                <p><strong>Mesas ocupadas:</strong> ${ocupadas}</p>
                <p><strong>Mesas disponibles:</strong> ${totalMesas - ocupadas}</p>
                <p><strong>Porcentaje de ocupación:</strong> ${porcentaje}%</p>
            </div>`,
                    icon: 'info',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090'
                });
            });
        });
    </script>
</body>

</html>