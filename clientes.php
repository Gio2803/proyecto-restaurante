<?php
session_start(); // ← AGREGAR ESTO AL INICIO
include("check_session.php"); // ← AGREGAR ESTO PARA VERIFICAR SESIÓN
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
            /* Verde principal */
            --secondary-color: #DDF4E7;
            /* Fondo claro */
            --danger-color: #124170;
            /* Usamos azul oscuro como "peligro" */
            --light-color: #26667F;
            /* Azul medio para encabezados / modal */
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
            /* Verde suave */
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
    </style>

</head>

<body>
    <?php include "menu.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mover el contenido al main-content
            const existingContent = document.querySelector('.container-custom');
            const mainContent = document.getElementById('mainContent');

            if (existingContent && mainContent) {
                mainContent.appendChild(existingContent);
            }
        });
    </script>
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
    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            // Variable para la DataTable
            let dataTable;
            // Variable para almacenar el modo actual (nuevo/editar) y el ID
            let currentMode = 'new';
            let currentId = null;

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

                // Validar que el teléfono solo contenga números
                if (!/^\d+$/.test(telefono)) {
                    Swal.fire('Error', 'El teléfono debe contener solo números', 'error');
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