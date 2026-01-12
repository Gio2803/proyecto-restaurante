<?php
session_start();
require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'clientes.php';

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

        // DEBUG: Mostrar resultado de la consulta
        error_log("Permiso verificado: " . print_r($permiso, true));

        if (!$permiso || $permiso['tiene_permiso'] == 0) {
            // Si no tiene permiso específico, verificar si es administrador
            if ($_SESSION['SISTEMA']['rol'] != 1) {
                header('Location: acceso_denegado.php');
                exit;
            }
            // Si es administrador, permitir acceso
        }
    } else {
        // Las tablas no existen, permitir acceso a todos (sistema en configuración)
        error_log("Tablas de permisos no existen - Acceso permitido");
    }

} catch (Exception $e) {
    // Si hay error en la consulta, permitir acceso temporalmente
    error_log("Error en verificación de permisos: " . $e->getMessage());
    // No redirigir, permitir acceso mientras se soluciona
}

// SI LLEGA AQUÍ, PERMITIR ACCESO
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Clientes</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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

        .btn-danger-custom {
            background-color: var(--danger-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }

        .btn-danger-custom:hover {
            background-color: var(--light-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-action {
            padding: 5px 12px;
            margin: 0 3px;
            font-size: 14px;
            border-radius: 5px;
            transition: all 0.2s;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        #clientesTable {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        #clientesTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }

        #clientesTable tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        #clientesTable tbody tr {
            transition: background-color 0.2s;
        }

        #clientesTable tbody tr:hover {
            background-color: rgba(103, 192, 144, 0.1);
        }

        .modal-header {
            background-color: var(--light-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .modal-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-label {
            font-weight: 500;
            color: #444;
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

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 15px;
            }

            .btn-custom,
            .btn-danger-custom {
                width: 100%;
            }
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
                <h1><i class="bi bi-people-fill"></i> Sistema de Gestión de Clientes</h1>
            </div>

            <div class="header-actions">
                <div>
                    <button class="btn-custom" id="nuevo">
                        <i class="bi bi-plus-circle"></i> Nuevo Cliente
                    </button>
                </div>
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card text-center">
                            <div class="stats-number" id="total-clientes">0</div>
                            <small>Clientes Totales</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table id="clientesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Teléfono</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="resultados_clientes">
                        <tr>
                            <td colspan="5" class="text-center">Cargando datos de clientes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clienteModalLabel">Gestión de Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modal-body">
                        <!-- Contenido del modal se cargará aquí -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-custom" id="guardar-cliente">Guardar Cliente</button>
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

        // ========== FUNCIONES DE CLIENTES ==========
        // ========== FUNCIONES DE CLIENTES ==========
        $(document).ready(function () {
            // Variable para la DataTable
            let dataTable;
            // Variable para almacenar el modo actual (nuevo/editar) y el ID
            let currentMode = 'new';
            let currentId = null;

            // Función para validar y formatear teléfono en tiempo real
            function validarTelefonoInput(input) {
                // Remover cualquier carácter que no sea número
                let value = input.value.replace(/[^0-9]/g, '');

                // Limitar a 10 dígitos (ajustable según necesidad)
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }

                // Actualizar el valor del input
                input.value = value;
            }

            // Función para validar teléfono completo antes de enviar
            function validarTelefonoCompleto(telefono) {
                // Validar que tenga al menos 10 dígitos (ajustable según tu país)
                if (telefono.length < 10) {
                    return {
                        valido: false,
                        mensaje: "El teléfono debe tener al menos 10 dígitos"
                    };
                }

                // Validar que solo contenga números
                if (!/^\d+$/.test(telefono)) {
                    return {
                        valido: false,
                        mensaje: "El teléfono debe contener solo números"
                    };
                }

                return {
                    valido: true,
                    mensaje: ""
                };
            }

            // Evento para validar teléfono en tiempo real
            $(document).on('input', '#telefono', function () {
                validarTelefonoInput(this);
            });

            // Evento para evitar pegar texto no numérico
            $(document).on('paste', '#telefono', function (e) {
                // Obtener el texto pegado
                let pastedText = e.originalEvent.clipboardData.getData('text');

                // Validar que solo contenga números
                if (!/^\d+$/.test(pastedText)) {
                    e.preventDefault();
                    Swal.fire('Error', 'Solo puedes pegar números', 'warning');
                    return false;
                }
            });

            // Evento para evitar arrastrar texto no numérico
            $(document).on('drop', '#telefono', function (e) {
                e.preventDefault();
                return false;
            });

            // Evento para validar teclas presionadas (solo números y teclas de control)
            $(document).on('keydown', '#telefono', function (e) {
                // Permitir teclas de control: backspace, delete, tab, escape, enter
                let teclasPermitidas = [8, 9, 13, 27, 46];

                // Permitir flechas: izquierda, arriba, derecha, abajo
                teclasPermitidas = teclasPermitidas.concat([37, 38, 39, 40]);

                // Permitir teclas especiales: home, end
                teclasPermitidas = teclasPermitidas.concat([36, 35]);

                // Si es tecla permitida, permitir
                if (teclasPermitidas.indexOf(e.keyCode) !== -1) {
                    return;
                }

                // Si es Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, permitir
                if ((e.ctrlKey === true) && (e.keyCode === 65 || e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) {
                    return;
                }

                // Si es un número del teclado principal o numérico, permitir
                if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) {
                    return;
                }

                // Para cualquier otra tecla, prevenir
                e.preventDefault();
            });

            // Inicializar la tabla
            function initTable() {
                cargarTabla();
            }

            // Cargar datos de la tabla desde la base de datos
            function cargarTabla() {
                $.post("funciones/clientes.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_clientes").html(response);

                    // Inicializar DataTable si no existe
                    if (!$.fn.DataTable.isDataTable('#clientesTable')) {
                        dataTable = $('#clientesTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ clientes",
                                infoEmpty: "Mostrando 0 a 0 de 0 clientes",
                                infoFiltered: "(filtrado de _MAX_ clientes totales)",
                                lengthMenu: "Mostrar _MENU_ clientes",
                                zeroRecords: "No se encontraron clientes",
                                search: "Buscar:",
                                paginate: {
                                    first: "Primero",
                                    last: "Último",
                                    next: "Siguiente",
                                    previous: "Anterior"
                                }
                            },
                            responsive: true,
                            pageLength: 5,
                            lengthMenu: [5, 10, 20, 50]
                        });
                    } else {
                        // Recargar la tabla si ya existe
                        dataTable.destroy();
                        $("#resultados_clientes").html(response);
                        dataTable = $('#clientesTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ clientes",
                                infoEmpty: "Mostrando 0 a 0 de 0 clientes",
                                infoFiltered: "(filtrado de _MAX_ clientes totales)",
                                lengthMenu: "Mostrar _MENU_ clientes",
                                zeroRecords: "No se encontraron clientes",
                                search: "Buscar:",
                                paginate: {
                                    first: "Primero",
                                    last: "Último",
                                    next: "Siguiente",
                                    previous: "Anterior"
                                }
                            },
                            responsive: true,
                            pageLength: 5,
                            lengthMenu: [5, 10, 20, 50]
                        });
                    }

                    // Actualizar estadísticas
                    actualizarEstadisticas();
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar la tabla de clientes", "error");
                    $("#resultados_clientes").html('<tr><td colspan="5" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            // Actualizar estadísticas
            function actualizarEstadisticas() {
                // Contar filas en la tabla (excluyendo la fila de "no data")
                const totalClientes = $('#clientesTable tbody tr').not('.dataTables_empty').length;
                $('#total-clientes').text(totalClientes);
            }

            // Mostrar modal para nuevo cliente
            $('#nuevo').click(function () {
                currentMode = 'new';
                currentId = null;
                $('#clienteModalLabel').text('Nuevo Cliente');
                mostrarFormulario();
                $('#clienteModal').modal('show');

                // Limpiar y enfocar el campo de teléfono
                setTimeout(function () {
                    $('#telefono').val('').focus();
                }, 500);
            });

            // Delegación de eventos para los botones de editar y eliminar
            $(document).on('click', '.editar', function () {
                const id = $(this).attr('idregistros');
                currentMode = 'edit';
                currentId = id;
                $('#clienteModalLabel').text('Editar Cliente');
                cargarFormularioEdicion(id);
            });

            $(document).on('click', '.eliminar', function () {
                const id = $(this).attr('idregistros');
                eliminarCliente(id);
            });

            // Función para cargar el formulario de edición
            function cargarFormularioEdicion(id) {
                $.post("funciones/clientes.php", {
                    funcion: "Modal",
                    tipo: "Editar",
                    id: id
                }, function (response) {
                    $('#modal-body').html(response);
                    $('#clienteModal').modal('show');

                    // Aplicar validación al campo de teléfono después de cargar
                    setTimeout(function () {
                        validarTelefonoInput(document.getElementById('telefono'));
                    }, 100);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario de edición", "error");
                });
            }

            // Función para mostrar el formulario vacío (nuevo cliente)
            function mostrarFormulario() {
                $.post("funciones/clientes.php", {
                    funcion: "Modal",
                    tipo: "Nuevo"
                }, function (response) {
                    $('#modal-body').html(response);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario", "error");
                });
            }

            // Guardar cliente (nuevo o edición)
            $('#guardar-cliente').click(function () {
                const nombre = $('#nombre').val();
                const apellidos = $('#apellidos').val();
                const telefono = $('#telefono').val();

                if (!nombre || !apellidos || !telefono) {
                    Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error');
                    return;
                }

                // Validar teléfono
                const validacionTelefono = validarTelefonoCompleto(telefono);
                if (!validacionTelefono.valido) {
                    Swal.fire('Error', validacionTelefono.mensaje, 'error');
                    $('#telefono').focus();
                    return;
                }

                // Determinar la función a llamar según el modo
                const funcion = currentMode === 'new' ? 'Guardar' : 'Editar';
                const data = {
                    funcion: funcion,
                    nombre: nombre,
                    apellidos: apellidos,
                    telefono: telefono
                };

                // Si es edición, agregar el ID
                if (currentMode === 'edit') {
                    data.idregistros = currentId;
                }

                Swal.fire({
                    title: currentMode === 'new' ? 'Creando cliente' : 'Actualizando cliente',
                    text: 'Procesando solicitud...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar datos al servidor
                $.post("funciones/clientes.php", data, function (response) {
                    if (response.includes("Error") || response.includes("error") || response.includes("Ya existe")) {
                        Swal.fire('Error', response, 'error');
                    } else {
                        Swal.fire(
                            'Éxito',
                            response,
                            'success'
                        );
                        $('#clienteModal').modal('hide');

                        // Recargar la tabla
                        cargarTabla();
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                });
            });

            // Eliminar cliente
            function eliminarCliente(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "El cliente será marcado como eliminado. ¿Desea continuar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando cliente',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar solicitud de eliminación al servidor
                        $.post("funciones/clientes.php", {
                            funcion: "Eliminar",
                            idregistros: id
                        }, function (response) {
                            Swal.fire(
                                'Eliminado',
                                response,
                                'success'
                            );

                            // Recargar la tabla
                            cargarTabla();
                        }).fail(function () {
                            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                        });
                    }
                });
            }

            // Inicializar la tabla al cargar la página
            initTable();
        });
    </script>
</body>

</html>