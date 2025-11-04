<?php
session_start(); // ← AGREGAR ESTO AL INICIO
include("check_session.php"); // ← AGREGAR ESTO PARA VERIFICAR SESIÓN
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Roles</title>

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

        #rolesTable {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        #rolesTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }

        #rolesTable tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        #rolesTable tbody tr {
            transition: background-color 0.2s;
        }

        #rolesTable tbody tr:hover {
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
            <h1><i class="bi bi-person-badge"></i> Sistema de Gestión de Roles</h1>
        </div>

        <div class="header-actions">
            <div>
                <button class="btn-custom" id="nuevo">
                    <i class="bi bi-plus-circle"></i> Nuevo Rol
                </button>
            </div>
            <div class="d-none d-md-block">
                <div class="d-flex gap-2">
                    <div class="stats-card text-center">
                        <div class="stats-number" id="total-roles">0</div>
                        <small>Roles Totales</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table id="rolesTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre del Rol</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody id="resultados_roles">
                    <tr>
                        <td colspan="2" class="text-center">Cargando datos de roles...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalLabel">Gestión de Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body">
                    <!-- Contenido del modal se cargará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-custom" id="guardar-rol">Guardar Rol</button>
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
                $.post("funciones/roles.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_roles").html(response);

                    // Inicializar DataTable si no existe
                    if (!$.fn.DataTable.isDataTable('#rolesTable')) {
                        dataTable = $('#rolesTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
                                infoEmpty: "Mostrando 0 a 0 de 0 roles",
                                infoFiltered: "(filtrado de _MAX_ roles totales)",
                                lengthMenu: "Mostrar _MENU_ roles",
                                zeroRecords: "No se encontraron roles",
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
                        $("#resultados_roles").html(response);
                        dataTable = $('#rolesTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
                                infoEmpty: "Mostrando 0 a 0 de 0 roles",
                                infoFiltered: "(filtrado de _MAX_ roles totales)",
                                lengthMenu: "Mostrar _MENU_ roles",
                                zeroRecords: "No se encontraron roles",
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
                    Swal.fire("Error", "No se pudo cargar la tabla de roles", "error");
                    $("#resultados_roles").html('<tr><td colspan="2" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            // Actualizar estadísticas
            function actualizarEstadisticas() {
                // Contar filas en la tabla (excluyendo la fila de "no data")
                const totalRoles = $('#rolesTable tbody tr').not('.dataTables_empty').length;
                $('#total-roles').text(totalRoles);
            }

            // Mostrar modal para nuevo rol
            $('#nuevo').click(function () {
                currentMode = 'new';
                currentId = null;
                $('#roleModalLabel').text('Nuevo Rol');
                mostrarFormulario();
                $('#roleModal').modal('show');
            });

            // Delegación de eventos para los botones de editar y eliminar
            $(document).on('click', '.editar', function () {
                const id = $(this).attr('idregistros');
                currentMode = 'edit';
                currentId = id;
                $('#roleModalLabel').text('Editar Rol');
                cargarFormularioEdicion(id);
            });

            $(document).on('click', '.eliminar', function () {
                const id = $(this).attr('idregistros');
                eliminarRol(id);
            });

            // Función para cargar el formulario de edición
            function cargarFormularioEdicion(id) {
                $.post("funciones/roles.php", {
                    funcion: "Modal",
                    tipo: "Editar",
                    id: id
                }, function (response) {
                    $('#modal-body').html(response);
                    $('#roleModal').modal('show');
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario de edición", "error");
                });
            }

            // Función para mostrar el formulario vacío (nuevo rol)
            function mostrarFormulario() {
                $.post("funciones/roles.php", {
                    funcion: "Modal",
                    tipo: "Nuevo"
                }, function (response) {
                    $('#modal-body').html(response);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario", "error");
                });
            }

            // Guardar rol (nuevo o edición)
            $('#guardar-rol').click(function () {
                const nombre_rol = $('#nombre_rol').val();

                if (!nombre_rol) {
                    Swal.fire('Error', 'Por favor complete el nombre del rol', 'error');
                    return;
                }

                // Determinar la función a llamar según el modo
                const funcion = currentMode === 'new' ? 'Guardar' : 'Editar';
                const data = {
                    funcion: funcion,
                    nombre_rol: nombre_rol
                };

                // Si es edición, agregar el ID
                if (currentMode === 'edit') {
                    data.idregistros = currentId;
                }

                Swal.fire({
                    title: currentMode === 'new' ? 'Creando rol' : 'Actualizando rol',
                    text: 'Procesando solicitud...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar datos al servidor
                $.post("funciones/roles.php", data, function (response) {
                    if (response.includes("Error") || response.includes("error") || response.includes("No se puede")) {
                        Swal.fire('Error', response, 'error');
                    } else {
                        Swal.fire(
                            'Éxito',
                            response,
                            'success'
                        );
                        $('#roleModal').modal('hide');

                        // Recargar la tabla
                        cargarTabla();
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                });
            });

            // Eliminar rol
            function eliminarRol(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "El rol será marcado como eliminado. ¿Desea continuar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando rol',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar solicitud de eliminación al servidor
                        $.post("funciones/roles.php", {
                            funcion: "Eliminar",
                            idregistros: id
                        }, function (response) {
                            if (response.includes("No se puede")) {
                                Swal.fire('Error', response, 'error');
                            } else {
                                Swal.fire(
                                    'Eliminado',
                                    response,
                                    'success'
                                );
                                // Recargar la tabla
                                cargarTabla();
                            }
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