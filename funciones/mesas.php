<?php
include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= TABLA MESAS ACTIVAS =================
    if ($funcion == "Tabla") {
        $stmt = $conexion->query("SELECT * FROM mesas WHERE fechabaja IS NULL ORDER BY numero_mesa");
        $tabla = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $badge_estado = "";
            switch ($row['estado']) {
                case 'disponible':
                    $badge_estado = "<span class='badge bg-success'>Disponible</span>";
                    break;
                case 'ocupada':
                    $badge_estado = "<span class='badge bg-danger'>Ocupada</span>";
                    break;
                case 'reservada':
                    $badge_estado = "<span class='badge bg-warning'>Reservada</span>";
                    break;
                default:
                    $badge_estado = "<span class='badge bg-secondary'>{$row['estado']}</span>";
            }

            $tabla .= "<tr>
                <td>{$row['numero_mesa']}</td>
                <td>{$row['capacidad']}</td>
                <td>{$badge_estado}</td>
                <td>{$row['ubicacion']}</td>
                <td>
                    <button class='btn editar btn-sm' idregistros='{$row['id_mesa']}' style='background-color: #2973B2; color: white; border: none;'>Editar</button>
                    <button class='btn eliminar btn-sm' idregistros='{$row['id_mesa']}' style='background-color: rgb(203,38,38); color: white; border: none;'>Eliminar</button>
                </td>
            </tr>";
        }
        echo $tabla;
        exit;
    }

    // ================= GUARDAR =================
    if ($funcion == "Guardar") {
        $numero_mesa = $_POST['numero_mesa'];
        $capacidad = $_POST['capacidad'];
        $estado = $_POST['estado'];
        $ubicacion = $_POST['ubicacion'];

        try {
            // Verificar si el número de mesa ya existe (incluyendo mesas eliminadas)
            $stmt = $conexion->prepare("SELECT id_mesa FROM mesas WHERE numero_mesa = :numero_mesa");
            $stmt->execute([':numero_mesa' => $numero_mesa]);

            if ($stmt->rowCount() > 0) {
                echo "Error: El número de mesa $numero_mesa ya existe en el sistema.";
                exit;
            }

            $stmt = $conexion->prepare("INSERT INTO mesas (numero_mesa, capacidad, estado, ubicacion) VALUES (:numero_mesa, :capacidad, :estado, :ubicacion)");
            $ok = $stmt->execute([
                ':numero_mesa' => $numero_mesa,
                ':capacidad' => $capacidad,
                ':estado' => $estado,
                ':ubicacion' => $ubicacion
            ]);

            echo $ok ? "Mesa insertada correctamente" : "Error al insertar la mesa";

        } catch (PDOException $e) {
            echo "Error de base de datos: " . $e->getMessage();
        }
        exit;
    }

    // ================= EDITAR =================
    if ($funcion == "Editar") {
        $id = $_POST['idregistros'];
        $numero_mesa = $_POST['numero_mesa'];
        $capacidad = $_POST['capacidad'];
        $estado = $_POST['estado'];
        $ubicacion = $_POST['ubicacion'];

        try {
            // Verificar si el número de mesa ya existe en otra mesa (excluyendo la actual)
            $stmt = $conexion->prepare("SELECT id_mesa FROM mesas WHERE numero_mesa = :numero_mesa AND id_mesa != :id");
            $stmt->execute([
                ':numero_mesa' => $numero_mesa,
                ':id' => $id
            ]);

            if ($stmt->rowCount() > 0) {
                echo "Error: El número de mesa $numero_mesa ya existe en el sistema.";
                exit;
            }

            $stmt = $conexion->prepare("UPDATE mesas SET capacidad=:capacidad, estado=:estado, ubicacion=:ubicacion WHERE id_mesa=:id");
            $ok = $stmt->execute([
                ':capacidad' => $capacidad,
                ':estado' => $estado,
                ':ubicacion' => $ubicacion,
                ':id' => $id
            ]);

            echo $ok ? "Mesa actualizada correctamente" : "Error al actualizar la mesa";

        } catch (PDOException $e) {
            echo "Error de base de datos: " . $e->getMessage();
        }
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];

        // Eliminación permanente
        $stmt = $conexion->prepare("DELETE FROM mesas WHERE id_mesa=:id");
        $result = $stmt->execute([':id' => $id]);

        echo $result ? "Mesa eliminada correctamente" : "Error al eliminar la mesa";
        exit;
    }

    // ================= MODAL =================
    if ($funcion == "Modal") {
        $row = [];
        if ($_POST['tipo'] == "Editar") {
            $stmt = $conexion->prepare("SELECT * FROM mesas WHERE id_mesa=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $numero_mesa = $row['numero_mesa'] ?? "";
        $capacidad = $row['capacidad'] ?? "4";
        $estado = $row['estado'] ?? "disponible";
        $ubicacion = $row['ubicacion'] ?? "";

        // Para nuevo, obtener el próximo número disponible
        if ($_POST['tipo'] == "Nuevo") {
            $stmt = $conexion->query("
                SELECT numero_mesa 
                FROM mesas 
                WHERE fechabaja IS NULL 
                ORDER BY numero_mesa
            ");

            $numeros_ocupados = [];
            while ($row_num = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $numeros_ocupados[] = $row_num['numero_mesa'];
            }

            // Encontrar el primer número disponible
            $proximo_numero = 1;
            while (in_array($proximo_numero, $numeros_ocupados)) {
                $proximo_numero++;
            }
            $numero_mesa = $proximo_numero;
        }

        $opciones_capacidad = "
            <option value='2' " . ($capacidad == '2' ? 'selected' : '') . ">2 personas</option>
            <option value='4' " . ($capacidad == '4' ? 'selected' : '') . ">4 personas</option>
            <option value='5' " . ($capacidad == '5' ? 'selected' : '') . ">5 personas</option>
            <option value='6' " . ($capacidad == '6' ? 'selected' : '') . ">6 personas</option>
            <option value='8' " . ($capacidad == '8' ? 'selected' : '') . ">8 personas</option>
            <option value='12' " . ($capacidad == '12' ? 'selected' : '') . ">12 personas</option>


        ";

        $opciones_estado = "
            <option value='disponible' " . ($estado == 'disponible' ? 'selected' : '') . ">Disponible</option>
            <option value='ocupada' " . ($estado == 'ocupada' ? 'selected' : '') . ">Ocupada</option>
            <option value='reservada' " . ($estado == 'reservada' ? 'selected' : '') . ">Reservada</option>
        ";

        $opciones_ubicacion = "
            <option value='Zona entrada' " . ($ubicacion == 'Zona entrada' ? 'selected' : '') . ">Zona entrada</option>
            <option value='Zona barra' " . ($ubicacion == 'Zona barra' ? 'selected' : '') . ">Zona barra</option>
            <option value='Zona baños' " . ($ubicacion == 'Zona baños' ? 'selected' : '') . ">Zona baños</option>
        ";

        // Campo de número de mesa: readonly en edición, solo lectura en nuevo
        $readonly = $_POST['tipo'] == "Editar" ? "readonly" : "";

        echo "
        <div class='row'>
            <div class='col-md-6'>
                <label>Número de Mesa</label>
                <input type='number' class='form-control' id='numero_mesa' value='$numero_mesa' min='1' $readonly required>
                " . ($_POST['tipo'] == "Nuevo" ? "<small class='text-muted'>Número asignado automáticamente</small>" : "<small class='text-muted'>El número de mesa no se puede modificar</small>") . "
            </div>
            <div class='col-md-6'>
                <label>Capacidad</label>
                <select class='form-control' id='capacidad' required>
                    $opciones_capacidad
                </select>
            </div>
            <div class='col-md-6 mt-3'>
                <label>Estado</label>
                <select class='form-control' id='estado' required>
                    $opciones_estado
                </select>
            </div>
            <div class='col-md-6 mt-3'>
                <label>Ubicación</label>
                <select class='form-control' id='ubicacion' required>
                    $opciones_ubicacion
                </select>
            </div>
        </div>";
        exit;
    }

    // ================= ESTADÍSTICAS =================
    if ($funcion == "Estadisticas") {
        $stmt = $conexion->query("
            SELECT 
                COUNT(*) as total_mesas,
                SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                SUM(CASE WHEN estado = 'reservada' THEN 1 ELSE 0 END) as reservadas
            FROM mesas 
            WHERE fechabaja IS NULL
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($stats);
        exit;
    }
}
?>