<?php
session_start(); // ← AGREGAR ESTO AL INICIO
include("check_session.php"); // ← AGREGAR ESTO PARA VERIFICAR SESIÓN
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Categorías</title>
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
        #categoriasTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }
        #categoriasTable tbody tr:hover {
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
            <h1><i class="bi bi-tags-fill"></i> Sistema de Gestión de Categorías</h1>
        </div>
        <div class="header-actions">
            <div>
                <button class="btn-custom" id="nuevo">
                    <i class="bi bi-plus-circle"></i> Nueva Categoría
                </button>
            </div>
            <div class="d-none d-md-block">
                <div class="d-flex gap-2">
                    <div class="stats-card text-center">
                        <div class="stats-number" id="total-categorias">0</div>
                        <small>Categorías Totales</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table id="categoriasTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody id="resultados_categorias">
                    <tr>
                        <td colspan="3" class="text-center">Cargando datos de categorías...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="categoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoriaModalLabel">Gestión de Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body">
                    <!-- Contenido del modal se cargará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-custom" id="guardar-categoria">Guardar Categoría</button>
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
                $.post("funciones/categorias.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_categorias").html(response);

                    if (!$.fn.DataTable.isDataTable('#categoriasTable')) {
                        dataTable = $('#categoriasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ categorías",
                                infoEmpty: "Mostrando 0 a 0 de 0 categorías",
                                infoFiltered: "(filtrado de _MAX_ categorías totales)",
                                lengthMenu: "Mostrar _MENU_ categorías",
                                zeroRecords: "No se encontraron categorías",
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
                        $("#resultados_categorias").html(response);
                        dataTable = $('#categoriasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ categorías",
                                infoEmpty: "Mostrando 0 a 0 de 0 categorías",
                                infoFiltered: "(filtrado de _MAX_ categorías totales)",
                                lengthMenu: "Mostrar _MENU_ categorías",
                                zeroRecords: "No se encontraron categorías",
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
                    Swal.fire("Error", "No se pudo cargar la tabla de categorías", "error");
                    $("#resultados_categorias").html('<tr><td colspan="3" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            function actualizarEstadisticas() {
                const totalCategorias = $('#categoriasTable tbody tr').not('.dataTables_empty').length;
                $('#total-categorias').text(totalCategorias);
            }

            $('#nuevo').click(function () {
                currentMode = 'new';
                currentId = null;
                $('#categoriaModalLabel').text('Nueva Categoría');
                mostrarFormulario();
                $('#categoriaModal').modal('show');
            });

            $(document).on('click', '.editar', function () {
                const id = $(this).attr('idregistros');
                currentMode = 'edit';
                currentId = id;
                $('#categoriaModalLabel').text('Editar Categoría');
                cargarFormularioEdicion(id);
            });

            $(document).on('click', '.eliminar', function () {
                const id = $(this).attr('idregistros');
                eliminarCategoria(id);
            });

            function cargarFormularioEdicion(id) {
                $.post("funciones/categorias.php", {
                    funcion: "Modal",
                    tipo: "Editar",
                    id: id
                }, function (response) {
                    $('#modal-body').html(response);
                    $('#categoriaModal').modal('show');
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario de edición", "error");
                });
            }

            function mostrarFormulario() {
                $.post("funciones/categorias.php", {
                    funcion: "Modal",
                    tipo: "Nuevo"
                }, function (response) {
                    $('#modal-body').html(response);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario", "error");
                });
            }

            $('#guardar-categoria').click(function () {
                const nombre = $('#nombre').val();
                const descripcion = $('#descripcion').val();

                if (!nombre) {
                    Swal.fire('Error', 'Por favor complete el nombre de la categoría', 'error');
                    return;
                }

                const funcion = currentMode === 'new' ? 'Guardar' : 'Editar';
                const data = {
                    funcion: funcion,
                    nombre: nombre,
                    descripcion: descripcion
                };

                if (currentMode === 'edit') {
                    data.idregistros = currentId;
                }

                Swal.fire({
                    title: currentMode === 'new' ? 'Creando categoría' : 'Actualizando categoría',
                    text: 'Procesando solicitud...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post("funciones/categorias.php", data, function (response) {
                    if (response.includes("Error") || response.includes("error") || response.includes("Ya existe")) {
                        Swal.fire('Error', response, 'error');
                    } else {
                        Swal.fire('Éxito', response, 'success');
                        $('#categoriaModal').modal('hide');
                        cargarTabla();
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                });
            });

            function eliminarCategoria(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "La categoría será marcada como eliminada. ¿Desea continuar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando categoría',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post("funciones/categorias.php", {
                            funcion: "Eliminar",
                            idregistros: id
                        }, function (response) {
                            Swal.fire('Eliminada', response, 'success');
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