<?php
include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("
            SELECT mp.*, c.nombre as categoria_nombre, um.nombre as unidad_nombre 
            FROM menu mp 
            LEFT JOIN categorias c ON mp.id_categoria = c.id_categoria 
            LEFT JOIN unidades_medida um ON mp.id_unidad = um.id_unidad 
            WHERE mp.fechabaja IS NULL 
            ORDER BY mp.nombre
        ");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $precio = number_format($row['precio'], 2);
            $tabla .= "<tr>
                <td>{$row['nombre']}</td>
                <td>{$row['descripcion']}</td>
                <td>{$row['categoria_nombre']}</td>
                <td>\$$precio</td>
                <td>{$row['unidad_nombre']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_platillo']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_platillo']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= OBTENER CATEGORIAS =================
    if ($funcion == "ObtenerCategorias") {
        $stmt = $conexion->query("SELECT id_categoria, nombre FROM categorias WHERE fechabaja IS NULL ORDER BY nombre");
        $categorias = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categorias .= "<option value='{$row['id_categoria']}'>{$row['nombre']}</option>";
        }
        echo $categorias;
        exit;
    }

    // ================= OBTENER UNIDADES =================
    if ($funcion == "ObtenerUnidades") {
        $stmt = $conexion->query("SELECT id_unidad, nombre FROM unidades_medida WHERE fechabaja IS NULL ORDER BY nombre");
        $unidades = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $unidades .= "<option value='{$row['id_unidad']}'>{$row['nombre']}</option>";
        }
        echo $unidades;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $id_categoria = $_POST['id_categoria'];
        $precio = $_POST['precio'];
        $id_unidad = $_POST['id_unidad'];

        // Validar precio
        if (!is_numeric($precio) || $precio <= 0) {
            echo "El precio debe ser un número mayor a 0";
            exit;
        }

        // Verificar existencia por nombre
        $stmt = $conexion->prepare("SELECT id_platillo FROM menu WHERE nombre = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe un platillo con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO menu (nombre, descripcion, id_categoria, precio, id_unidad) VALUES (:nombre, :descripcion, :id_categoria, :precio, :id_unidad)");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':id_categoria' => $id_categoria,
            ':precio' => $precio,
            ':id_unidad' => $id_unidad
        ]);

        echo $ok ? "Insertado Correctamente" : "Error al insertar";
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $id_categoria = $_POST['id_categoria'];
        $precio = $_POST['precio'];
        $id_unidad = $_POST['id_unidad'];

        // Validar precio
        if (!is_numeric($precio) || $precio <= 0) {
            echo "El precio debe ser un número mayor a 0";
            exit;
        }

        // Verificar si ya existe otro platillo con el mismo nombre
        $stmt = $conexion->prepare("SELECT id_platillo FROM menu WHERE nombre = :nombre AND id_platillo != :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo "Ya existe otro platillo con ese nombre";
            exit;
        }

        $stmt = $conexion->prepare("UPDATE menu SET nombre=:nombre, descripcion=:descripcion, id_categoria=:id_categoria, precio=:precio, id_unidad=:id_unidad WHERE id_platillo=:id");
        $ok = $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':id_categoria' => $id_categoria,
            ':precio' => $precio,
            ':id_unidad' => $id_unidad,
            ':id' => $id
        ]);

        echo $ok ? "Platillo actualizado" : "Error al actualizar";
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE menu SET fechabaja=NOW() WHERE id_platillo=:id");
        $stmt->execute([':id' => $id]);
        echo "Platillo eliminado";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM menu WHERE id_platillo=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $nombre = $row['nombre'] ?? "";
        $descripcion = $row['descripcion'] ?? "";
        $id_categoria = $row['id_categoria'] ?? "";
        $precio = $row['precio'] ?? "";
        $id_unidad = $row['id_unidad'] ?? "";

        // Obtener categorías
        $stmt_cat = $conexion->query("SELECT id_categoria, nombre FROM categorias WHERE fechabaja IS NULL ORDER BY nombre");
        $options_categorias = "";
        while ($cat = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($cat['id_categoria'] == $id_categoria) ? 'selected' : '';
            $options_categorias .= "<option value='{$cat['id_categoria']}' $selected>{$cat['nombre']}</option>";
        }

        // Obtener unidades
        $stmt_uni = $conexion->query("SELECT id_unidad, nombre FROM unidades_medida WHERE fechabaja IS NULL ORDER BY nombre");
        $options_unidades = "";
        while ($uni = $stmt_uni->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($uni['id_unidad'] == $id_unidad) ? 'selected' : '';
            $options_unidades .= "<option value='{$uni['id_unidad']}' $selected>{$uni['nombre']}</option>";
        }

        echo "
        <div class='row'>
            <div class='col-12'>
                <label>Nombre del Platillo</label>
                <input type='text' class='form-control' id='nombre' value='$nombre' required>
            </div>
            <div class='col-12 mt-3'>
                <label>Descripción</label>
                <textarea class='form-control' id='descripcion' rows='3'>$descripcion</textarea>
            </div>
            <div class='col-6 mt-3'>
                <label>Categoría</label>
                <select class='form-control' id='id_categoria' required>
                    <option value=''>Seleccione una categoría</option>
                    $options_categorias
                </select>
            </div>
            <div class='col-6 mt-3'>
                <label>Unidad de Medida</label>
                <select class='form-control' id='id_unidad' required>
                    <option value=''>Seleccione una unidad</option>
                    $options_unidades
                </select>
            </div>
            <div class='col-12 mt-3'>
                <label>Precio</label>
                <input type='number' class='form-control' id='precio' value='$precio' step='0.01' min='0' required>
            </div>
        </div>";
        exit;
    }
}
?>