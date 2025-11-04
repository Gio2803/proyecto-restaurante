<?php
include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT * FROM unidades_medida WHERE fechabaja IS NULL ORDER BY nombre");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tabla .= "<tr>
                <td>{$row['nombre']}</td>
                <td>{$row['abreviatura']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_unidad']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_unidad']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $nombre = $_POST['nombre'];
        $abreviatura = $_POST['abreviatura'];

        // Verificar existencia por nombre
        $stmt = $conexion->prepare("SELECT id_unidad FROM unidades_medida WHERE nombre = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe una unidad de medida con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO unidades_medida (nombre, abreviatura) VALUES (:nombre, :abreviatura)");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':abreviatura' => $abreviatura
        ]);

        echo $ok ? "Unidad de medida insertada" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $nombre = $_POST['nombre'];
        $abreviatura = $_POST['abreviatura'];

        // Verificar si ya existe otra unidad con el mismo nombre
        $stmt = $conexion->prepare("SELECT id_unidad FROM unidades_medida WHERE nombre = :nombre AND id_unidad != :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe otra unidad de medida con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE unidades_medida SET nombre=:nombre, abreviatura=:abreviatura WHERE id_unidad=:id");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':abreviatura' => $abreviatura,
            ':id' => $id
        ]);

        echo $ok ? "Unidad de medida actualizada" : "Error al actualizar";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE unidades_medida SET fechabaja=NOW() WHERE id_unidad=:id");
        $stmt->execute([':id' => $id]);
        echo "Unidad de medida eliminada";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM unidades_medida WHERE id_unidad=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $nombre = $row['nombre'] ?? "";
        $abreviatura = $row['abreviatura'] ?? "";

        echo "
        <div class='row'>
            <div class='col-8'>
                <label>Nombre de la Unidad</label>
                <input type='text' class='form-control' id='nombre' value='$nombre' required>
            </div>
            <div class='col-4'>
                <label>Abreviatura</label>
                <input type='text' class='form-control' id='abreviatura' value='$abreviatura'>
            </div>
        </div>";
        exit;
    }
}
?>