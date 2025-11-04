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
            switch($row['estado']) {
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

    // ================= TABLA MESAS ELIMINADAS =================
    if ($funcion == "TablaEliminadas") {
        // CORRECCIÓN: Cambié NOT NULL por IS NOT NULL
        $stmt = $conexion->query("SELECT * FROM mesas WHERE fechabaja IS NOT NULL ORDER BY numero_mesa");
        $tabla = "";
        
        if ($stmt->rowCount() == 0) {
            $tabla = "<tr><td colspan='6' class='text-center'>No hay mesas eliminadas</td></tr>";
        } else {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fecha_eliminacion = date('d/m/Y H:i', strtotime($row['fechabaja']));
                
                $tabla .= "<tr>
                    <td>{$row['numero_mesa']}</td>
                    <td>{$row['capacidad']}</td>
                    <td><span class='badge bg-secondary'>{$row['estado']}</span></td>
                    <td>{$row['ubicacion']}</td>
                    <td>{$fecha_eliminacion}</td>
                    <td>
                        <button class='btn recuperar btn-sm' idregistros='{$row['id_mesa']}' style='background-color: #28a745; color: white; border: none;'>Recuperar</button>
                        <button class='btn eliminar-permanente btn-sm' idregistros='{$row['id_mesa']}' style='background-color: #dc3545; color: white; border: none;'>Eliminar Permanentemente</button>
                    </td>
                </tr>";
            }
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
            $stmt = $conexion->prepare("INSERT INTO mesas (numero_mesa, capacidad, estado, ubicacion) VALUES (:numero_mesa, :capacidad, :estado, :ubicacion)");
            $ok = $stmt->execute([
                ':numero_mesa' => $numero_mesa,
                ':capacidad' => $capacidad,
                ':estado' => $estado,
                ':ubicacion' => $ubicacion
            ]);

            echo $ok ? "Mesa insertada correctamente" : "Error al insertar la mesa";
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'llave duplicada') !== false) {
                echo "Error: El número de mesa $numero_mesa ya existe en el sistema (incluso si está eliminada).";
            } else {
                echo "Error de base de datos: " . $e->getMessage();
            }
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
            $stmt = $conexion->prepare("UPDATE mesas SET numero_mesa=:numero_mesa, capacidad=:capacidad, estado=:estado, ubicacion=:ubicacion WHERE id_mesa=:id");
            $ok = $stmt->execute([
                ':numero_mesa' => $numero_mesa,
                ':capacidad' => $capacidad,
                ':estado' => $estado,
                ':ubicacion' => $ubicacion,
                ':id' => $id
            ]);

            echo $ok ? "Mesa actualizada correctamente" : "Error al actualizar la mesa";
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'llave duplicada') !== false) {
                echo "Error: El número de mesa $numero_mesa ya existe en el sistema.";
            } else {
                echo "Error de base de datos: " . $e->getMessage();
            }
        }
        exit;
    }

    // ================= ELIMINAR =================
    if ($funcion == "Eliminar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE mesas SET fechabaja=NOW() WHERE id_mesa=:id");
        $stmt->execute([':id' => $id]);
        echo "Mesa eliminada correctamente";
        exit;
    }

    // ================= RECUPERAR MESA =================
    if ($funcion == "Recuperar") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("UPDATE mesas SET fechabaja=NULL, estado='disponible' WHERE id_mesa=:id");
        $stmt->execute([':id' => $id]);
        echo "Mesa recuperada correctamente";
        exit;
    }

    // ================= ELIMINAR PERMANENTEMENTE =================
    if ($funcion == "EliminarPermanente") {
        $id = $_POST['idregistros'];
        $stmt = $conexion->prepare("DELETE FROM mesas WHERE id_mesa=:id");
        $stmt->execute([':id' => $id]);
        echo "Mesa eliminada permanentemente";
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
            <option value='4' " . ($capacidad == '4' ? 'selected' : '') . ">4 personas</option>
            <option value='8' " . ($capacidad == '8' ? 'selected' : '') . ">8 personas</option>
            <option value='12' " . ($capacidad == '12' ? 'selected' : '') . ">12 personas</option>
        ";

        // Solo disponible como estado
        $opciones_estado = "<option value='disponible' selected>Disponible</option>";

        $opciones_ubicacion = "
            <option value='Zona interior' " . ($ubicacion == 'Zona interior' ? 'selected' : '') . ">Zona interior</option>
            <option value='Zona privada' " . ($ubicacion == 'Zona privada' ? 'selected' : '') . ">Zona privada</option>
            <option value='Terraza' " . ($ubicacion == 'Terraza' ? 'selected' : '') . ">Terraza</option>
            <option value='Barra' " . ($ubicacion == 'Barra' ? 'selected' : '') . ">Barra</option>
        ";

        $readonly = $_POST['tipo'] == "Nuevo" ? "readonly" : "";

        echo "
        <div class='row'>
            <div class='col-md-6'>
                <label>Número de Mesa</label>
                <input type='number' class='form-control' id='numero_mesa' value='$numero_mesa' min='1' $readonly required>
                " . ($_POST['tipo'] == "Nuevo" ? "<small class='text-muted'>Número asignado automáticamente</small>" : "") . "
            </div>
            <div class='col-md-6'>
                <label>Capacidad</label>
                <select class='form-control' id='capacidad' required>
                    $opciones_capacidad
                </select>
            </div>
            <div class='col-md-6 mt-3'>
                <label>Estado</label>
                <select class='form-control' id='estado' required readonly>
                    $opciones_estado
                </select>
                <small class='text-muted'>Siempre disponible al crear</small>
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
                SUM(CASE WHEN estado = 'reservada' THEN 1 ELSE 0 END) as reservadas,
                (SELECT COUNT(*) FROM mesas WHERE fechabaja IS NOT NULL) as eliminadas
            FROM mesas 
            WHERE fechabaja IS NULL
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($stats);
        exit;
    }
}
?>