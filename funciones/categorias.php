<?php
include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT * FROM categorias WHERE fechabaja IS NULL ORDER BY nombre");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tabla .= "<tr>
                <td>{$row['nombre']}</td>
                <td>{$row['descripcion']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_categoria']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_categoria']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];

        // Verificar existencia por nombre
        $stmt = $conexion->prepare("SELECT id_categoria FROM categorias WHERE nombre = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe una categoría con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);

        echo $ok ? "Categoría insertada" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];

        // Verificar si ya existe otra categoría con el mismo nombre
        $stmt = $conexion->prepare("SELECT id_categoria FROM categorias WHERE nombre = :nombre AND id_categoria != :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe otra categoría con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE categorias SET nombre=:nombre, descripcion=:descripcion WHERE id_categoria=:id");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':id' => $id
        ]);

        echo $ok ? "Categoría actualizada" : "Error al actualizar";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE categorias SET fechabaja=NOW() WHERE id_categoria=:id");
        $stmt->execute([':id' => $id]);
        echo "Categoría eliminada";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM categorias WHERE id_categoria=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $nombre = $row['nombre'] ?? "";
        $descripcion = $row['descripcion'] ?? "";

        echo "
        <div class='row'>
            <div class='col-12'>
                <label>Nombre de la Categoría</label>
                <input type='text' class='form-control' id='nombre' value='$nombre' required>
            </div>
            <div class='col-12 mt-3'>
                <label>Descripción</label>
                <textarea class='form-control' id='descripcion' rows='3'>$descripcion</textarea>
            </div>
        </div>";
        exit;
    }
}
?>