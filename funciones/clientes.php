<?php
include("../conexion.php");

// ==== PETICIONES AJAX ====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT * FROM clientes WHERE fechabaja IS NULL");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tabla .= "<tr>
                <td>{$row['id_cliente']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['apellidos']}</td>
                <td>{$row['telefono']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_cliente']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_cliente']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $nombre = $_POST['nombre'];
        $apellidos = $_POST['apellidos'];
        $telefono = $_POST['telefono'];

        // Validar teléfono (solo números)
        if (!preg_match('/^[0-9]+$/', $telefono)) {
            echo "El teléfono debe contener solo números";
            exit;
        }

        // Verificar existencia por nombre y apellidos
        $stmt = $conexion->prepare("SELECT id_cliente FROM clientes WHERE nombre = :nombre AND apellidos = :apellidos");
        $stmt->execute([':nombre' => $nombre, ':apellidos' => $apellidos]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe un cliente con ese nombre y apellidos";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO clientes (nombre, apellidos, telefono) VALUES (:nombre, :apellidos, :telefono)");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':telefono' => $telefono
        ]);

        echo $ok ? "Cliente insertado" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $nombre = $_POST['nombre'];
        $apellidos = $_POST['apellidos'];
        $telefono = $_POST['telefono'];

        // Validar teléfono (solo números)
        if (!preg_match('/^[0-9]+$/', $telefono)) {
            echo "El teléfono debe contener solo números";
            exit;
        }

        // Verificar si ya existe otro cliente con el mismo nombre y apellidos
        $stmt = $conexion->prepare("SELECT id_cliente FROM clientes WHERE nombre = :nombre AND apellidos = :apellidos AND id_cliente != :id");
        $stmt->execute([':nombre' => $nombre, ':apellidos' => $apellidos, ':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe otro cliente con ese nombre y apellidos";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE clientes SET nombre=:nombre, apellidos=:apellidos, telefono=:telefono WHERE id_cliente=:id");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':telefono' => $telefono,
            ':id' => $id
        ]);

        echo $ok ? "Cliente actualizado" : "Error al actualizar";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE clientes SET fechabaja=NOW() WHERE id_cliente=:id");
        $stmt->execute([':id' => $id]);
        echo "Cliente eliminado";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $nombre = $row['nombre'] ?? "";
        $apellidos = $row['apellidos'] ?? "";
        $telefono = $row['telefono'] ?? "";

        echo "
        <div class='row'>
            <div class='col-6'>
                <label>Nombre</label>
                <input type='text' class='form-control' id='nombre' value='$nombre' required>
            </div>
            <div class='col-6'>
                <label>Apellidos</label>
                <input type='text' class='form-control' id='apellidos' value='$apellidos' required>
            </div>
            <div class='col-12 mt-3'>
                <label>Teléfono</label>
                <input type='text' class='form-control' id='telefono' value='$telefono' pattern='[0-9]+' title='Solo se permiten números' required>
            </div>
        </div>";
        exit;
    }
}
?>