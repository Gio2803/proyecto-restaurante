<?php
include("../conexion.php");

// ==== PETICIONES AJAX ====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT * FROM roles WHERE fechabaja IS NULL");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tabla .= "<tr>
                <td>{$row['nombre_rol']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_rol']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_rol']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $nombre_rol = $_POST['nombre_rol'];

        // Verificar existencia
        $stmt = $conexion->prepare("SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol");
        $stmt->execute([':nombre_rol' => $nombre_rol]);
        if ($stmt->rowCount() > 0) {
            echo "El rol ya existe";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO roles (nombre_rol) VALUES (:nombre_rol)");
        $ok = $stmt->execute([
            ':nombre_rol' => $nombre_rol
        ]);

        echo $ok ? "Rol insertado" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $nombre_rol = $_POST['nombre_rol'];

        // Verificar si el nombre ya existe en otro rol
        $stmt = $conexion->prepare("SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol AND id_rol != :id");
        $stmt->execute([':nombre_rol' => $nombre_rol, ':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe otro rol con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE roles SET nombre_rol=:nombre_rol WHERE id_rol=:id");
        $ok = $stmt->execute([
            ':nombre_rol' => $nombre_rol,
            ':id' => $id
        ]);

        echo $ok ? "Rol actualizado" : "Error al actualizar";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        
        // Verificar si hay usuarios con este rol
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = :id AND fechabaja IS NULL");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            echo "No se puede eliminar el rol porque hay usuarios asignados a él";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE roles SET fechabaja=NOW() WHERE id_rol=:id");
        $stmt->execute([':id' => $id]);
        echo "Rol eliminado";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM roles WHERE id_rol=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $nombre_rol = $row['nombre_rol'] ?? "";

        echo "
        <div class='row'>
            <div class='col-12'>
                <label>Nombre del Rol</label>
                <input type='text' class='form-control' id='nombre_rol' value='$nombre_rol' required>
            </div>
        </div>";
        exit;
    }
}
?>