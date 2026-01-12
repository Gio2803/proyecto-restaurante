<?php
require_once '../conexion.php';

header('Content-Type: application/json');

// Función para obtener caja abierta 
function obtenerCajaAbierta($id_usuario)
{
    global $conexion;

    try {
        $sql = "SELECT * FROM corte_caja 
                WHERE id_usuario = ? AND estado = 'ABIERTO' 
                ORDER BY fecha_apertura DESC LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id_usuario]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerCajaAbierta: " . $e->getMessage());
        return false;
    }
}

// Función para abrir caja usando el procedimiento almacenado
function abrirCaja($id_usuario, $monto_inicial, $observaciones = '')
{
    global $conexion;

    try {
        // Verificar si ya hay una caja abierta
        $caja_abierta = obtenerCajaAbierta($id_usuario);
        if ($caja_abierta) {
            return ['success' => false, 'message' => 'Ya existe una caja abierta'];
        }

        // Llamar al procedimiento almacenado
        $sql = "CALL sp_abrir_caja(?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id_usuario, $monto_inicial, $observaciones]);

        return ['success' => true, 'message' => 'Caja abierta correctamente'];

    } catch (Exception $e) {
        error_log("Error en abrirCaja: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()];
    }
}

// Función para obtener resumen de ventas POR MESERO
function obtenerResumenVentas($id_usuario)
{
    global $conexion;

    try {
        // Obtener la caja abierta
        $caja_abierta = obtenerCajaAbierta($id_usuario);
        if (!$caja_abierta) {
            return ['error' => 'No hay caja abierta'];
        }

        $fecha_apertura = $caja_abierta['fecha_apertura'];

        // Obtener ventas por mesero
        $sql_meseros = "SELECT 
                        p.id_mesero,
                        p.nombre_mesero,
                        COUNT(p.id_pedido) as total_pedidos,
                        COALESCE(SUM(p.total), 0) as total
                        FROM pedidos p
                        WHERE p.fecha_creacion >= ? 
                        AND p.estado = 'pagado'
                        AND p.id_mesero IS NOT NULL
                        GROUP BY p.id_mesero, p.nombre_mesero
                        ORDER BY total DESC";

        $stmt = $conexion->prepare($sql_meseros);
        $stmt->execute([$fecha_apertura]);
        $meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener total general
        $sql_total = "SELECT 
                      COALESCE(SUM(total), 0) as total
                      FROM pedidos 
                      WHERE fecha_creacion >= ? 
                      AND estado = 'pagado'";

        $stmt = $conexion->prepare($sql_total);
        $stmt->execute([$fecha_apertura]);
        $result_total = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_general = floatval($result_total['total']);

        // Detalle de ventas
        $sql_detalle = "SELECT 
                        p.id_pedido,
                        p.id_mesero,
                        p.nombre_mesero,
                        p.id_mesa,
                        p.total,
                        p.estado,
                        TO_CHAR(p.fecha_creacion, 'HH24:MI') as fecha_creacion
                        FROM pedidos p
                        WHERE p.fecha_creacion >= ? 
                        AND p.estado = 'pagado'
                        ORDER BY p.fecha_creacion DESC";

        $stmt = $conexion->prepare($sql_detalle);
        $stmt->execute([$fecha_apertura]);
        $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'meseros' => $meseros,
            'total' => $total_general,
            'detalle' => $detalle
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerResumenVentas: " . $e->getMessage());
        return ['error' => 'Error al obtener resumen: ' . $e->getMessage()];
    }
}

// Función para obtener resumen para cierre
function obtenerResumenCierre($id_usuario)
{
    global $conexion;

    try {
        // Obtener la caja abierta
        $caja_abierta = obtenerCajaAbierta($id_usuario);
        if (!$caja_abierta) {
            return ['error' => 'No hay caja abierta'];
        }

        $fecha_apertura = $caja_abierta['fecha_apertura'];
        $monto_inicial = floatval($caja_abierta['monto_inicial']);

        // Obtener ventas totales
        $sql_ventas = "SELECT 
                        COALESCE(SUM(total), 0) as total,
                        COUNT(*) as total_pedidos
                        FROM pedidos 
                        WHERE fecha_creacion >= ? 
                        AND estado = 'pagado'";

        $stmt = $conexion->prepare($sql_ventas);
        $stmt->execute([$fecha_apertura]);
        $result_ventas = $stmt->fetch(PDO::FETCH_ASSOC);

        $ventas_totales = floatval($result_ventas['total']);
        $total_pedidos = intval($result_ventas['total_pedidos']);

        return [
            'monto_inicial' => $monto_inicial,
            'ventas_totales' => $ventas_totales,
            'total_pedidos' => $total_pedidos
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerResumenCierre: " . $e->getMessage());
        return ['error' => 'Error al obtener resumen cierre: ' . $e->getMessage()];
    }
}

// Función para cerrar caja usando el procedimiento almacenado
function cerrarCaja($id_usuario, $efectivo_final, $observaciones = '')
{
    global $conexion;

    try {
        // Obtener la caja abierta
        $caja_abierta = obtenerCajaAbierta($id_usuario);
        if (!$caja_abierta) {
            return ['success' => false, 'message' => 'No hay caja abierta para cerrar'];
        }

        $id_corte = $caja_abierta['id_corte'];

        // SOLO llamar al procedimiento almacenado - ÉL ya calcula la diferencia correctamente
        $sql = "CALL sp_cerrar_caja(?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id_corte, $id_usuario, $efectivo_final, $observaciones]);

        return ['success' => true, 'message' => 'Caja cerrada correctamente'];

    } catch (Exception $e) {
        error_log("Error en cerrarCaja: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()];
    }
}

// Función para obtener datos para el ticket
function obtenerDatosTicket($id_usuario)
{
    global $conexion;

    try {
        // Obtener el último cierre de caja
        $sql_caja = "SELECT * FROM corte_caja 
                    WHERE id_usuario = ? 
                    ORDER BY fecha_cierre DESC 
                    LIMIT 1";
        $stmt = $conexion->prepare($sql_caja);
        $stmt->execute([$id_usuario]);
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caja) {
            return ['error' => 'No se encontró información de caja'];
        }

        // Obtener información del usuario
        $sql_usuario = "SELECT nombre FROM usuarios WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql_usuario);
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener ventas por mesero durante el periodo de la caja
        $fecha_apertura = $caja['fecha_apertura'];
        $fecha_cierre = $caja['fecha_cierre'];

        $sql_meseros = "SELECT 
                        p.id_mesero,
                        COALESCE(p.nombre_mesero, 'Sin mesero') as nombre_mesero,
                        COUNT(p.id_pedido) as total_pedidos,
                        COALESCE(SUM(p.total), 0) as total
                        FROM pedidos p
                        WHERE p.fecha_creacion >= ? 
                        AND p.fecha_creacion <= ?
                        AND p.estado = 'pagado'
                        GROUP BY p.id_mesero, p.nombre_mesero
                        ORDER BY total DESC";

        $stmt = $conexion->prepare($sql_meseros);
        $stmt->execute([$fecha_apertura, $fecha_cierre]);
        $meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener total general de ventas
        $sql_total = "SELECT 
                      COALESCE(SUM(total), 0) as total
                      FROM pedidos 
                      WHERE fecha_creacion >= ? 
                      AND fecha_creacion <= ?
                      AND estado = 'pagado'";

        $stmt = $conexion->prepare($sql_total);
        $stmt->execute([$fecha_apertura, $fecha_cierre]);
        $result_total = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'fecha_apertura' => date('d/m/Y H:i', strtotime($caja['fecha_apertura'])),
            'fecha_cierre' => date('d/m/Y H:i', strtotime($caja['fecha_cierre'])),
            'monto_inicial' => $caja['monto_inicial'],
            'ventas_totales' => $result_total['total'],
            'diferencia' => $caja['diferencia'] ?? 0,
            'meseros' => $meseros,
            'nombre_usuario' => $usuario['nombre'],
            'observaciones' => $caja['observaciones']
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerDatosTicket: " . $e->getMessage());
        return ['error' => 'Error al obtener datos del ticket: ' . $e->getMessage()];
    }
}

// Función para obtener historial de cortes
function obtenerHistorial($id_usuario, $limite = 50)
{
    global $conexion;

    try {
        $sql = "SELECT 
                cc.*,
                u.nombre as nombre_usuario,
                TO_CHAR(cc.fecha_apertura, 'DD/MM/YYYY HH24:MI') as fecha_apertura_formatted,
                TO_CHAR(cc.fecha_cierre, 'DD/MM/YYYY HH24:MI') as fecha_cierre_formatted,
                (cc.monto_inicial + cc.ventas_totales) as total_esperado,
                cc.diferencia
                FROM corte_caja cc
                JOIN usuarios u ON cc.id_usuario = u.id_usuario
                WHERE cc.id_usuario = ?
                ORDER BY cc.fecha_apertura DESC
                LIMIT ?";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id_usuario, $limite]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerHistorial: " . $e->getMessage());
        return ['error' => 'Error al obtener historial: ' . $e->getMessage()];
    }
}

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'Usuario no autenticado']);
            exit;
        }

        $id_usuario = $_SESSION['id_usuario'];
        $funcion = $_POST['funcion'] ?? '';

        $funciones_permitidas = [
            'ObtenerResumenVentas',
            'ObtenerResumenCierre',
            'AbrirCaja',
            'CerrarCaja',
            'ObtenerHistorial',
            'ObtenerDatosTicket'
        ];

        if (!in_array($funcion, $funciones_permitidas)) {
            echo json_encode(['error' => 'Función no válida: ' . $funcion]);
            exit;
        }

        switch ($funcion) {
            case 'ObtenerResumenVentas':
                $resultado = obtenerResumenVentas($id_usuario);
                break;

            case 'ObtenerResumenCierre':
                $resultado = obtenerResumenCierre($id_usuario);
                break;

            case 'AbrirCaja':
                $monto_inicial = floatval($_POST['monto_inicial'] ?? 0);
                $observaciones = $_POST['observaciones'] ?? '';
                $resultado = abrirCaja($id_usuario, $monto_inicial, $observaciones);
                break;

            case 'CerrarCaja':
                $efectivo_final = floatval($_POST['efectivo_final'] ?? 0);
                $observaciones = $_POST['observaciones'] ?? '';
                $resultado = cerrarCaja($id_usuario, $efectivo_final, $observaciones);
                break;

            case 'ObtenerHistorial':
                $resultado = obtenerHistorial($id_usuario);
                break;

            case 'ObtenerDatosTicket':
                $resultado = obtenerDatosTicket($id_usuario);
                break;
        }

        echo json_encode($resultado);

    } catch (Exception $e) {
        error_log("Error general en corte_caja.php: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Método no permitido']);
}
?>