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

    // ================= OBTENER PEDIDOS PARA BARRA =================
    if ($funcion == "ObtenerPedidosBarra") {
        try {
            // Categorías específicas de barra
            $categoriasBarra = ['Bebidas'];

            // Crear placeholders para la consulta
            $placeholders = str_repeat('?,', count($categoriasBarra) - 1) . '?';

            // Obtener pedidos activos solo para barra
            $sql = "
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
                LEFT JOIN menu me ON dp.id_platillo = me.id_platillo
                LEFT JOIN categorias c ON me.id_categoria = c.id_categoria
                WHERE p.estado NOT IN ('pagado', 'archivado', 'cancelado')
                AND (dp.estado IS NULL OR dp.estado IN ('pendiente', 'en_preparacion'))
                AND c.nombre IN ($placeholders)
                ORDER BY p.fecha_creacion ASC
            ";

            $stmt = $conexion->prepare($sql);
            $stmt->execute($categoriasBarra);

            $pedidos = [];
            while ($pedido = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Obtener detalles del pedido solo para barra (INCLUYENDO NOTAS)
                $sqlDetalles = "
                    SELECT 
                        dp.id_detalle,
                        dp.id_platillo,
                        dp.nombre_platillo,
                        dp.cantidad,
                        dp.precio_unitario,
                        dp.subtotal,
                        dp.nota,  -- INCLUIR EL CAMPO NOTA
                        COALESCE(dp.estado, 'pendiente') as estado_producto,
                        COALESCE(c.nombre, 'Sin Categoría') as categoria_nombre
                    FROM detalles_pedido dp
                    LEFT JOIN menu m ON dp.id_platillo = m.id_platillo
                    LEFT JOIN categorias c ON m.id_categoria = c.id_categoria
                    WHERE dp.id_pedido = ?
                    AND c.nombre IN ($placeholders)
                    ORDER BY c.nombre, dp.nombre_platillo
                ";

                $stmtDetalles = $conexion->prepare($sqlDetalles);

                // Combinar parámetros correctamente: primero id_pedido, luego categorías
                $parametrosDetalles = array_merge([$pedido['id_pedido']], $categoriasBarra);
                $stmtDetalles->execute($parametrosDetalles);

                $detalles = [];
                while ($detalle = $stmtDetalles->fetch(PDO::FETCH_ASSOC)) {
                    $detalles[] = $detalle;
                }

                // Solo incluir pedidos que tengan productos de barra
                if (count($detalles) > 0) {
                    $pedido['detalles'] = $detalles;
                    $pedidos[] = $pedido;
                }
            }

            echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener pedidos de barra: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
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
                SET estado = ? 
                WHERE id_detalle = ? AND id_pedido = ?
            ");
            $stmt->execute([$nuevo_estado, $id_detalle, $id_pedido]);

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

            // Si el estado es 'finalizada', actualizar todos los productos de barra a 'terminado'
            if ($nuevo_estado == 'finalizada') {
                $stmtProductos = $conexion->prepare("
                    UPDATE detalles_pedido 
                    SET estado = 'terminado' 
                    WHERE id_pedido = ?
                    AND id_platillo IN (
                        SELECT m.id_platillo 
                        FROM menu m 
                        LEFT JOIN categorias c ON m.id_categoria = c.id_categoria 
                        WHERE c.nombre IN ('Bebidas')
                    )
                ");
                $stmtProductos->execute([$id_pedido]);
            }

            // Actualizar estado del pedido
            $stmt = $conexion->prepare("
                UPDATE pedidos 
                SET estado = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id_pedido = ?
            ");
            $stmt->execute([$nuevo_estado, $id_pedido]);

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
                WHERE id_pedido = ?
            ");
            $stmt->execute([$id_pedido]);

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