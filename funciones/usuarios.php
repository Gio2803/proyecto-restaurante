<?php
include("../conexion.php");

// ==== PETICIONES AJAX ====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT u.*, r.nombre_rol 
                                  FROM usuarios u 
                                  LEFT JOIN roles r ON r.id_rol = u.id_rol 
                                  WHERE u.fechabaja IS NULL");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tabla .= "<tr>
                <td>{$row['usuario']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['telefono']}</td>
                <td>{$row['nombre_rol']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_usuario']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_usuario']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $usuario = $_POST['usuario'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $id_rol = $_POST['id_rol'];
        $contrasena = $_POST['contrasena'];

        // Verificar existencia
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        if ($stmt->rowCount() > 0) {
            echo "El usuario ya existe";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, telefono, id_rol) 
                                    VALUES (:usuario,:contrasena,:nombre,:telefono,:id_rol)");
        $ok = $stmt->execute([
            ':usuario' => $usuario,
            ':contrasena' => $contrasena,
            ':nombre' => $nombre,
            ':telefono' => $telefono,
            ':id_rol' => $id_rol
        ]);

        echo $ok ? "Usuario insertado" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $usuario = $_POST['usuario'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $id_rol = $_POST['id_rol'];
        $contrasena = $_POST['contrasena'];

        $stmt = $conexion->prepare("UPDATE usuarios SET usuario=:usuario, nombre=:nombre, telefono=:telefono, id_rol=:id_rol WHERE id_usuario=:id");
        $stmt->execute([
            ':usuario' => $usuario,
            ':nombre' => $nombre,
            ':telefono' => $telefono,
            ':id_rol' => $id_rol,
            ':id' => $id
        ]);

        if (!empty($contrasena)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET contrasena=:contrasena WHERE id_usuario=:id");
            $stmt->execute([
                ':contrasena' => $contrasena,
                ':id' => $id
            ]);
        }

        echo "Usuario actualizado";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE usuarios SET fechabaja=NOW() WHERE id_usuario=:id");
        $stmt->execute([':id' => $id]);
        echo "Usuario eliminado";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $usuario = $row['usuario'] ?? "";
        $nombre = $row['nombre'] ?? "";
        $telefono = $row['telefono'] ?? "";
        $contrasena = $row['contrasena'] ?? "";
        $id_rol = $row['id_rol'] ?? "";

        $roles = $conexion->query("SELECT * FROM roles WHERE fechabaja IS NULL");
        $options = "<option value=''>Seleccione</option>";
        while ($r = $roles->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($id_rol == $r['id_rol']) ? "selected" : "";
            $options .= "<option value='{$r['id_rol']}' $sel>{$r['nombre_rol']}</option>";
        }

        echo "
        <div class='row'>
            <div class='col-6'>
                <label>Usuario</label>
                <input type='text' class='form-control' id='usuario' value='$usuario'>
            </div>
            <div class='col-6'>
                <label>Contraseña</label>
                <input type='password' class='form-control' id='contrasena' value='$contrasena'>
            </div>
            <div class='col-6'>
                <label>Nombre</label>
                <input type='text' class='form-control' id='nombre' value='$nombre'>
            </div>
            <div class='col-6'>
                <label>Teléfono</label>
                <input type='text' class='form-control' id='telefono' value='$telefono'>
            </div>
            <div class='col-6'>
                <label>Rol</label>
                <select class='form-control' id='id_rol'>$options</select>
            </div>
        </div>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body { background-color: #F2EFE7 !important; }
        .btn-custom { background-color: #2973B2; color: white; border-radius: 5px; padding:10px; font-size:18px; width:200px; border:none; }
        .btn-custom1 { background-color: rgb(178, 41, 41); color:white; border-radius:5px; padding:10px; font-size:18px; width:200px; border:none; }
        .btn-custom:hover { background-color:#1f5a8e; }
        .btn-custom1:hover { background-color: rgb(204,27,27); }
        .banner { background-color: #2973B2; color: white; padding:5px; }
        #modal .modal-header, #modal .modal-footer { background-color: #F5F5F5; }
        #modal .modal-title { color:black; }
    </style>
</head>

<body>
    <!-- MODAL -->
    <div class="modal" tabindex="-1" id="modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titulo_modal">Alta de Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="cerrar"></button>
                </div>
                <div class="modal-body" id="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn-custom invisible" id="Guardar_Edita">Editar Usuario</button>
                    <button type="button" class="btn-custom" id="Guardar_Nuevo">Agregar Usuario</button>
                    <button type="button" class="btn-custom1" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center banner">
                <h1><i class="bi bi-person-lines-fill"></i> Usuarios</h1>
            </div>
            <div class="col-10"></div>
            <div class="col-2 text-center mt-3">
                <button class="btn-custom w-50" id="nuevo" data-bs-toggle="modal" data-bs-target="#modal">Nuevo <i class="bi bi-plus-circle"></i></button>
            </div>
            <div class="col-12 text-center mt-3">
                <table id="usuariosTable" class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="resultados_usuarios"></tbody>
                </table>
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

            // Cargar tabla
            function Tabla() {
                $.post("funciones/usuarios.php", { funcion: "Tabla" }, function (response) {
                    $("#resultados_usuarios").html(response);
                    if ($.fn.DataTable.isDataTable("#usuariosTable")) {
                        $("#usuariosTable").DataTable().destroy();
                    }
                    $("#usuariosTable").DataTable({
                        language: {
                            info: "Mostrando pagina _PAGE_ de _PAGES_",
                            infoEmpty: "Sin Resultados",
                            infoFiltered: "(filtrado de _MAX_ resultados)",
                            lengthMenu: "Mostrando _MENU_ resultados por pagina",
                            zeroRecords: "No se encontraron resultados",
                        }
                    });
                });
            }
            Tabla();

            // Modal AJAX
            function Modal(tipo, id) {
                $.post("funciones/usuarios.php", { funcion: "Modal", tipo: tipo, id: id }, function (response) {
                    $("#modal-body").html(response);
                });
            }

            // Nuevo
            $(document).on("click", "#nuevo", function () {
                Modal("Nuevo", 0);
                $("#Guardar_Nuevo").show();
                $("#Guardar_Edita").hide().addClass("invisible");
                $("#titulo_modal").text("Alta de Usuarios");
            });

            // Editar
            $(document).on("click", ".editar", function () {
                var id = $(this).attr("idregistros");
                Modal("Editar", id);
                $("#Guardar_Nuevo").hide();
                $("#Guardar_Edita").show().attr("idregistros", id).removeClass("invisible");
                $("#titulo_modal").text("Editar Usuario");
            });

            // Guardar Nuevo
            $(document).on("click", "#Guardar_Nuevo", function () {
                var usuario = $("#usuario").val();
                var contrasena = $("#contrasena").val();
                var nombre = $("#nombre").val();
                var telefono = $("#telefono").val();
                var id_rol = $("#id_rol").val();

                if (!usuario || !contrasena || !nombre || !telefono || !id_rol) {
                    Swal.fire("Error", "Todos los campos son obligatorios", "error");
                    return;
                }

                $.post("funciones/usuarios.php", {
                    funcion: "Guardar",
                    usuario: usuario,
                    contrasena: contrasena,
                    nombre: nombre,
                    telefono: telefono,
                    id_rol: id_rol
                }, function (response) {
                    if (response === "Usuario insertado") {
                        Swal.fire("Éxito", response, "success");
                        $("#modal").modal("hide");
                        Tabla();
                    } else {
                        Swal.fire("Error", response, "error");
                    }
                });
            });

            // Editar Usuario
            $(document).on("click", "#Guardar_Edita", function () {
                var id = $(this).attr("idregistros");
                var usuario = $("#usuario").val();
                var contrasena = $("#contrasena").val();
                var nombre = $("#nombre").val();
                var telefono = $("#telefono").val();
                var id_rol = $("#id_rol").val();

                if (!usuario || !nombre || !telefono || !id_rol) {
                    Swal.fire("Error", "Todos los campos son obligatorios", "error");
                    return;
                }

                $.post("funciones/usuarios.php", {
                    funcion: "Editar",
                    idregistros: id,
                    usuario: usuario,
                    contrasena: contrasena,
                    nombre: nombre,
                    telefono: telefono,
                    id_rol: id_rol
                }, function (response) {
                    Swal.fire("Éxito", response, "success");
                    $("#modal").modal("hide");
                    Tabla();
                });
            });

            // Eliminar
            $(document).on("click", ".eliminar", function () {
                var id = $(this).attr("idregistros");
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "No podrá revertir esto",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("funciones/usuarios.php", { funcion: "Eliminar", idregistros: id }, function (response) {
                            Swal.fire('Eliminado', response, 'success');
                            Tabla();
                        });
                    }
                });
            });

        });
    </script>
</body>
</html>
