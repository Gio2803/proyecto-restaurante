<?php
session_start();
include("check_session.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Mesas</title>
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
            max-width: 1600px;
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
            background-color: #6c757d;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
        }
        .table-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        #mesasTable thead th, #mesasEliminadasTable thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }
        #mesasTable tbody tr:hover, #mesasEliminadasTable tbody tr:hover {
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
            text-align: center;
            min-width: 120px;
        }
        .stats-card-eliminadas {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            min-width: 120px;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stats-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
        }
        .nav-tabs .nav-link {
            color: var(--light-color);
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 3px solid var(--primary-color);
        }
        .tab-content {
            padding-top: 20px;
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
            <h1><i class="bi bi-table"></i> Sistema de Gestión de Mesas</h1>
        </div>
        
        <div class="header-actions">
            <div>
                <button class="btn-custom" id="nuevo">
                    <i class="bi bi-plus-circle"></i> Nueva Mesa
                </button>
                <button class="btn-secondary-custom" id="verEliminadas">
                    <i class="bi bi-archive"></i> Mesas Eliminadas
                </button>
            </div>
            <div class="d-flex gap-2">
                <div class="stats-card">
                    <div class="stats-number" id="total-mesas">0</div>
                    <div class="stats-label">Total Mesas</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number" id="disponibles">0</div>
                    <div class="stats-label">Disponibles</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number" id="ocupadas">0</div>
                    <div class="stats-label">Ocupadas</div>
                </div>
                <div class="stats-card-eliminadas">
                    <div class="stats-number" id="eliminadas">0</div>
                    <div class="stats-label">Eliminadas</div>
                </div>
            </div>
        </div>

        <!-- Pestañas -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab">
                    <i class="bi bi-table"></i> Mesas Activas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="eliminadas-tab" data-bs-toggle="tab" data-bs-target="#eliminadas" type="button" role="tab">
                    <i class="bi bi-archive"></i> Mesas Eliminadas
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Pestaña Mesas Activas -->
            <div class="tab-pane fade show active" id="activas" role="tabpanel">
                <div class="table-container">
                    <table id="mesasTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Capacidad</th>
                                <th>Estado</th>
                                <th>Ubicación</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody id="resultados_mesas">
                            <tr>
                                <td colspan="5" class="text-center">Cargando datos de mesas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña Mesas Eliminadas -->
            <div class="tab-pane fade" id="eliminadas" role="tabpanel">
                <div class="table-container">
                    <table id="mesasEliminadasTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Capacidad</th>
                                <th>Estado</th>
                                <th>Ubicación</th>
                                <th>Fecha Eliminación</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody id="resultados_mesas_eliminadas">
                            <tr>
                                <td colspan="6" class="text-center">Cargando mesas eliminadas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="mesaModal" tabindex="-1" aria-labelledby="mesaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mesaModalLabel">Gestión de Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body">
                    <!-- Contenido del modal se cargará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-custom" id="guardar-mesa">Guardar Mesa</button>
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
            let dataTableActivas, dataTableEliminadas;
            let currentMode = 'new';
            let currentId = null;

            function initTable() {
                cargarTablaActivas();
                cargarEstadisticas();
            }

            function cargarTablaActivas() {
                $.post("funciones/mesas.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_mesas").html(response);

                    if (!$.fn.DataTable.isDataTable('#mesasTable')) {
                        dataTableActivas = $('#mesasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ mesas activas",
                                infoEmpty: "Mostrando 0 a 0 de 0 mesas activas",
                                infoFiltered: "(filtrado de _MAX_ mesas totales)",
                                lengthMenu: "Mostrar _MENU_ mesas",
                                zeroRecords: "No se encontraron mesas activas",
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
                            lengthMenu: [5, 10, 20, 50]
                        });
                    } else {
                        dataTableActivas.destroy();
                        $("#resultados_mesas").html(response);
                        dataTableActivas = $('#mesasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ mesas activas",
                                infoEmpty: "Mostrando 0 a 0 de 0 mesas activas",
                                infoFiltered: "(filtrado de _MAX_ mesas totales)",
                                lengthMenu: "Mostrar _MENU_ mesas",
                                zeroRecords: "No se encontraron mesas activas",
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
                            lengthMenu: [5, 10, 20, 50]
                        });
                    }
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar la tabla de mesas activas", "error");
                    $("#resultados_mesas").html('<tr><td colspan="5" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            function cargarTablaEliminadas() {
                $.post("funciones/mesas.php", { funcion: "TablaEliminadas" }, function (response) {
                    $("#resultados_mesas_eliminadas").html(response);

                    if (!$.fn.DataTable.isDataTable('#mesasEliminadasTable')) {
                        dataTableEliminadas = $('#mesasEliminadasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ mesas eliminadas",
                                infoEmpty: "Mostrando 0 a 0 de 0 mesas eliminadas",
                                infoFiltered: "(filtrado de _MAX_ mesas totales)",
                                lengthMenu: "Mostrar _MENU_ mesas",
                                zeroRecords: "No se encontraron mesas eliminadas",
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
                            lengthMenu: [5, 10, 20, 50]
                        });
                    } else {
                        dataTableEliminadas.destroy();
                        $("#resultados_mesas_eliminadas").html(response);
                        dataTableEliminadas = $('#mesasEliminadasTable').DataTable({
                            language: {
                                info: "Mostrando _START_ a _END_ de _TOTAL_ mesas eliminadas",
                                infoEmpty: "Mostrando 0 a 0 de 0 mesas eliminadas",
                                infoFiltered: "(filtrado de _MAX_ mesas totales)",
                                lengthMenu: "Mostrar _MENU_ mesas",
                                zeroRecords: "No se encontraron mesas eliminadas",
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
                            lengthMenu: [5, 10, 20, 50]
                        });
                    }
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar la tabla de mesas eliminadas", "error");
                    $("#resultados_mesas_eliminadas").html('<tr><td colspan="6" class="text-center">Error al cargar los datos</td></tr>');
                });
            }

            function cargarEstadisticas() {
                $.post("funciones/mesas.php", { funcion: "Estadisticas" }, function (response) {
                    const stats = JSON.parse(response);
                    $('#total-mesas').text(stats.total_mesas);
                    $('#disponibles').text(stats.disponibles);
                    $('#ocupadas').text(stats.ocupadas);
                    $('#reservadas').text(stats.reservadas);
                    $('#eliminadas').text(stats.eliminadas);
                });
            }

            $('#nuevo').click(function () {
                currentMode = 'new';
                currentId = null;
                $('#mesaModalLabel').text('Nueva Mesa');
                mostrarFormulario();
                $('#mesaModal').modal('show');
            });

            $('#verEliminadas').click(function () {
                $('#eliminadas-tab').tab('show');
                cargarTablaEliminadas();
            });

            // Cuando se cambia de pestaña
            $('#eliminadas-tab').on('shown.bs.tab', function () {
                cargarTablaEliminadas();
            });

            $(document).on('click', '.editar', function () {
                const id = $(this).attr('idregistros');
                currentMode = 'edit';
                currentId = id;
                $('#mesaModalLabel').text('Editar Mesa');
                cargarFormularioEdicion(id);
            });

            $(document).on('click', '.eliminar', function () {
                const id = $(this).attr('idregistros');
                eliminarMesa(id);
            });

            $(document).on('click', '.recuperar', function () {
                const id = $(this).attr('idregistros');
                recuperarMesa(id);
            });

            $(document).on('click', '.eliminar-permanente', function () {
                const id = $(this).attr('idregistros');
                eliminarMesaPermanente(id);
            });

            function cargarFormularioEdicion(id) {
                $.post("funciones/mesas.php", {
                    funcion: "Modal",
                    tipo: "Editar",
                    id: id
                }, function (response) {
                    $('#modal-body').html(response);
                    $('#mesaModal').modal('show');
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario de edición", "error");
                });
            }

            function mostrarFormulario() {
                $.post("funciones/mesas.php", {
                    funcion: "Modal",
                    tipo: "Nuevo"
                }, function (response) {
                    $('#modal-body').html(response);
                }).fail(function () {
                    Swal.fire("Error", "No se pudo cargar el formulario", "error");
                });
            }

            $('#guardar-mesa').click(function () {
                const numero_mesa = $('#numero_mesa').val();
                const capacidad = $('#capacidad').val();
                const estado = $('#estado').val();
                const ubicacion = $('#ubicacion').val();

                if (!numero_mesa || !capacidad || !estado || !ubicacion) {
                    Swal.fire('Error', 'Por favor complete todos los campos', 'error');
                    return;
                }

                const funcion = currentMode === 'new' ? 'Guardar' : 'Editar';
                const data = {
                    funcion: funcion,
                    numero_mesa: numero_mesa,
                    capacidad: capacidad,
                    estado: estado,
                    ubicacion: ubicacion
                };

                if (currentMode === 'edit') {
                    data.idregistros = currentId;
                }

                Swal.fire({
                    title: currentMode === 'new' ? 'Creando mesa' : 'Actualizando mesa',
                    text: 'Procesando solicitud...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post("funciones/mesas.php", data, function (response) {
                    if (response.includes("Error") || response.includes("error") || response.includes("Ya existe")) {
                        Swal.fire('Error', response, 'error');
                    } else {
                        Swal.fire('Éxito', response, 'success');
                        $('#mesaModal').modal('hide');
                        cargarTablaActivas();
                        cargarEstadisticas();
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                });
            });

            function eliminarMesa(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "La mesa será movida a la papelera de reciclaje. ¿Desea continuar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando mesa',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post("funciones/mesas.php", {
                            funcion: "Eliminar",
                            idregistros: id
                        }, function (response) {
                            Swal.fire('Eliminada', response, 'success');
                            cargarTablaActivas();
                            cargarEstadisticas();
                        }).fail(function () {
                            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                        });
                    }
                });
            }

            function recuperarMesa(id) {
                Swal.fire({
                    title: '¿Recuperar mesa?',
                    text: "La mesa será restaurada y estará disponible nuevamente.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, recuperar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Recuperando mesa',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post("funciones/mesas.php", {
                            funcion: "Recuperar",
                            idregistros: id
                        }, function (response) {
                            Swal.fire('Recuperada', response, 'success');
                            cargarTablaEliminadas();
                            cargarTablaActivas();
                            cargarEstadisticas();
                        }).fail(function () {
                            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                        });
                    }
                });
            }

            function eliminarMesaPermanente(id) {
                Swal.fire({
                    title: '¿Eliminar permanentemente?',
                    text: "¡Esta acción no se puede deshacer! La mesa será borrada del sistema completamente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar permanentemente',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando permanentemente',
                            text: 'Procesando solicitud...',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post("funciones/mesas.php", {
                            funcion: "EliminarPermanente",
                            idregistros: id
                        }, function (response) {
                            Swal.fire('Eliminada', response, 'success');
                            cargarTablaEliminadas();
                            cargarEstadisticas();
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