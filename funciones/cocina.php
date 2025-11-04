<?php
include("../conexion.php");

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Evitar que se muestren errores en la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // ================= OBTENER PEDIDOS PARA COCINA/BARRA =================
    if ($funcion == "ObtenerPedidosCocina") {
        try {
            // Obtener pedidos activos - INCLUIR pedidos 'finalizada' que tengan productos pendientes
            $stmt = $conexion->query("
            SELECT DISTINCT
                p.id_pedido,
                m.numero_mesa,
                p.nombre_mesero,
                p.estado,
                p.total,
                p.fecha_creacion
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            INNER JOIN detalles_pedido dp ON p.id_pedido = dp.id_pedido
            WHERE p.estado NOT IN ('pagado', 'archivado', 'cancelado')
            AND (dp.estado IS NULL OR dp.estado IN ('pendiente', 'en_preparacion'))
            ORDER BY p.fecha_creacion ASC
        ");

            $pedidos = [];
            while ($pedido = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Obtener detalles del pedido con información de categoría
                $stmtDetalles = $conexion->prepare("
                SELECT 
                    dp.id_detalle,
                    dp.id_platillo,
                    dp.nombre_platillo,
                    dp.cantidad,
                    dp.precio_unitario,
                    dp.subtotal,
                    COALESCE(dp.estado, 'pendiente') as estado_producto,
                    COALESCE(c.nombre, 'Sin Categoría') as categoria_nombre
                FROM detalles_pedido dp
                LEFT JOIN menu m ON dp.id_platillo = m.id_platillo
                LEFT JOIN categorias c ON m.id_categoria = c.id_categoria
                WHERE dp.id_pedido = :id_pedido
                ORDER BY c.nombre, dp.nombre_platillo
            ");
                $stmtDetalles->execute([':id_pedido' => $pedido['id_pedido']]);

                $detalles = [];
                while ($detalle = $stmtDetalles->fetch(PDO::FETCH_ASSOC)) {
                    $detalles[] = $detalle;
                }

                $pedido['detalles'] = $detalles;
                $pedidos[] = $pedido;
            }

            echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener pedidos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ================= CAMBIAR ESTADO DE PRODUCTO =================
    if ($funcion == "CambiarEstadoProducto") {
        try {
            $id_pedido = $_POST['id_pedido'];
            $id_detalle = $_POST['id_detalle'];
            $nuevo_estado = $_POST['nuevo_estado'];

            // Actualizar estado del producto
            $stmt = $conexion->prepare("
                UPDATE detalles_pedido 
                SET estado = :estado 
                WHERE id_detalle = :id_detalle AND id_pedido = :id_pedido
            ");
            $stmt->execute([
                ':estado' => $nuevo_estado,
                ':id_detalle' => $id_detalle,
                ':id_pedido' => $id_pedido
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Estado del producto actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ================= CAMBIAR ESTADO COMPLETO DEL PEDIDO =================
    if ($funcion == "CambiarEstadoPedidoCompleto") {
        try {
            $id_pedido = $_POST['id_pedido'];
            $nuevo_estado = $_POST['nuevo_estado'];

            $conexion->beginTransaction();

            // Si el estado es 'finalizada', actualizar todos los productos a 'terminado'
            if ($nuevo_estado == 'finalizada') {
                $stmtProductos = $conexion->prepare("
                    UPDATE detalles_pedido 
                    SET estado = 'terminado' 
                    WHERE id_pedido = :id_pedido
                ");
                $stmtProductos->execute([':id_pedido' => $id_pedido]);
            }

            // Actualizar estado del pedido
            $stmt = $conexion->prepare("
                UPDATE pedidos 
                SET estado = :estado, fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id_pedido = :id_pedido
            ");
            $stmt->execute([
                ':estado' => $nuevo_estado,
                ':id_pedido' => $id_pedido
            ]);

            $conexion->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Estado del pedido actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ================= ARCHIVAR PEDIDO =================
    if ($funcion == "ArchivarPedido") {
        try {
            $id_pedido = $_POST['id_pedido'];

            // Cambiar estado del pedido a 'archivado'
            $stmt = $conexion->prepare("
                UPDATE pedidos 
                SET estado = 'archivado', fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id_pedido = :id_pedido
            ");
            $stmt->execute([':id_pedido' => $id_pedido]);

            echo json_encode([
                'success' => true,
                'message' => 'Pedido archivado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al archivar pedido: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Si la función no existe
    echo json_encode(['error' => 'Función no válida: ' . $funcion], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Método no válido o función no especificada'], JSON_UNESCAPED_UNICODE);
}
?>