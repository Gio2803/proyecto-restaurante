<?php
session_start(); // ← AGREGAR ESTO AL INICIO
include("check_session.php"); // ← AGREGAR ESTO PARA VERIFICAR SESIÓN
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Menú/Platillos</title>
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
        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        #menuTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }
        #menuTable tbody tr:hover {
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
        <div class="banner text-center">
            <h1><i class="bi bi-egg-fried"></i> Sistema de Gestión de Menú/Platillos/Bebidas</h1>
        </div>
        <div class="header-actions">
            <div>
                <button class="btn-custom" id="nuevo">
                    <i class="bi bi-plus-circle"></i> Nuevo Platillo
                </button>
            </div>
            <div class="d-none d-md-block">
                <div class="d-flex gap-2">
                    <div class="stats-card text-center">
                        <div class="stats-number" id="total-platillos">0</div>
                        <small>Platillos Totales</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table id="menuTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Unidad</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody id="resultados_menu">
                    <tr>
                        <td colspan="6" class="text-center">Cargando datos del menú...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalLabel">Gestión de Platillo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body">
                    <!-- Contenido del modal se cargará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-custom" id="guardar-platillo">Guardar</button>
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
            let dataTable;
            let currentMode = 'new';
            let currentId = null;

            function initTable() {
                cargarTabla();
            }

            function cargarTabla() {
                $.post("funciones/menu_platillos.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_menu").html(response);

                    if (!$.fn.DataTable.isDataTable('#menuTable')) {
                        dataTable = $('#menuTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ platillos",
                                infoEmpty: "Mostrando 0 a 0 de 0 platillos",
                                infoFiltered: "(filtrado de _MAX_ platillos totales)",
                                lengthMenu: "Mostrar _MENU_ platillos",
                                zeroRecords: "No se encontraron platillos",
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
                        dataTable.destroy();
                        $("#resultados_menu").html(response);
                        dataTable = $('#menuTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ platillos",
                                infoEmpty: "Mostrando 0 a 0 de 0 platillos",
                                infoFiltered: "(filtrado de _MAX_ platillos totales)",
                                lengthMenu: "Mostrar _MENU_ platillos",
                                zeroRecords: "No se encontraron platillos",
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
                    actualizarEstadisticas();
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar la tabla del menú", "error");
                    $("#resultados_menu").html('<tr><td colspan="6" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            function actualizarEstadisticas() {
                const totalPlatillos = $('#menuTable tbody tr').not('.dataTables_empty').length;
                $('#total-platillos').text(totalPlatillos);
            }

            $('#nuevo').click(function () {
                currentMode = 'new';
                currentId = null;
                $('#menuModalLabel').text('Nuevo Platillo');
                mostrarFormulario();
                $('#menuModal').modal('show');
            });

            $(document).on('click', '.editar', function () {
                const id = $(this).attr('idregistros');
                currentMode = 'edit';
                currentId = id;
                $('#menuModalLabel').text('Editar Platillo');
                cargarFormularioEdicion(id);
            });

            $(document).on('click', '.eliminar', function () {
                const id = $(this).attr('idregistros');
                eliminarPlatillo(id);
            });

            function cargarFormularioEdicion(id) {
                $.post("funciones/menu_platillos.php", {
                    funcion: "Modal",
                    tipo: "Editar",
                    id: id
                }, function (response) {
                    $('#modal-body').html(response);
                    $('#menuModal').modal('show');
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario de edición", "error");
                });
            }

            function mostrarFormulario() {
                $.post("funciones/menu_platillos.php", {
                    funcion: "Modal",
                    tipo: "Nuevo"
                }, function (response) {
                    $('#modal-body').html(response);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario", "error");
                });
            }

            $('#guardar-platillo').click(function () {
                const nombre = $('#nombre').val();
                const descripcion = $('#descripcion').val();
                const id_categoria = $('#id_categoria').val();
                const precio = $('#precio').val();
                const id_unidad = $('#id_unidad').val();

                if (!nombre || !id_categoria || !precio || !id_unidad) {
                    Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error');
                    return;
                }

                if (precio <= 0) {
                    Swal.fire('Error', 'El precio debe ser mayor a 0', 'error');
                    return;
                }

                const funcion = currentMode === 'new' ? 'Guardar' : 'Editar';
                const data = {
                    funcion: funcion,
                    nombre: nombre,
                    descripcion: descripcion,
                    id_categoria: id_categoria,
                    precio: precio,
                    id_unidad: id_unidad
                };

                if (currentMode === 'edit') {
                    data.idregistros = currentId;
                }

                Swal.fire({
                    title: currentMode === 'new' ? 'Creando platillo' : 'Actualizando platillo',
                    text: 'Procesando solicitud...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post("funciones/menu_platillos.php", data, function (response) {
                    if (response.includes("Error") || response.includes("error") || response.includes("Ya existe")) {
                        Swal.fire('Error', response, 'error');
                    } else {
                        Swal.fire('Éxito', response, 'success');
                        $('#menuModal').modal('hide');
                        cargarTabla();
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                });
            });

            function eliminarPlatillo(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "El platillo será marcado como eliminado. ¿Desea continuar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando platillo',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post("funciones/menu_platillos.php", {
                            funcion: "Eliminar",
                            idregistros: id
                        }, function (response) {
                            Swal.fire('Eliminado', response, 'success');
                            cargarTabla();
                        }).fail(function () {
                            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                        });
                    }
                });
            }

            initTable();
        });
    </script>
</body>
</html>