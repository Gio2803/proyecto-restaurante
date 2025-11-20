<?php
include("../conexion.php");

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    if ($funcion == "ObtenerMesas") {
        try {
            $stmt = $conexion->query("
    SELECT id_mesa, numero_mesa, capacidad, estado, ubicacion
    FROM mesas 
    WHERE fechabaja IS NULL 
    ORDER BY numero_mesa
");
            $mesas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mesas[] = $row;
            }
            echo json_encode($mesas, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener mesas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerCategoriasMenu") {
        try {
            $stmt = $conexion->query("SELECT id_categoria, nombre, descripcion FROM categorias WHERE fechabaja IS NULL ORDER BY nombre");
            $categorias = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categorias[] = $row;
            }
            echo json_encode($categorias, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener categorías: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerProductosPorCategoria") {
        try {
            $id_categoria = $_POST['id_categoria'];

            $stmt = $conexion->prepare("
                SELECT id_platillo, nombre, descripcion, precio 
                FROM menu 
                WHERE id_categoria = :id_categoria AND fechabaja IS NULL 
                ORDER BY nombre
            ");
            $stmt->execute([':id_categoria' => $id_categoria]);

            $productos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos[] = $row;
            }
            echo json_encode($productos, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // AGREGAR esta nueva función después de VerificarPedidoActivo
    if ($funcion == "VerificarPedidoActivo") {
        try {
            $id_mesa = $_POST['id_mesa'];

            $stmt = $conexion->prepare("
            SELECT id_pedido, estado 
            FROM pedidos 
            WHERE id_mesa = :id_mesa 
            AND estado NOT IN ('pagado', 'cancelado')
            ORDER BY fecha_creacion DESC 
            LIMIT 1
        ");
            $stmt->execute([':id_mesa' => $id_mesa]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'existe' => true,
                    'id_pedido' => $row['id_pedido'],
                    'estado' => $row['estado'] // INCLUIR EL ESTADO
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['existe' => false], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al verificar pedido: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // NUEVA FUNCIÓN para obtener el estado del pedido
    if ($funcion == "ObtenerEstadoPedido") {
        try {
            $id_pedido = $_POST['id_pedido'];

            $stmt = $conexion->prepare("
            SELECT estado 
            FROM pedidos 
            WHERE id_pedido = :id_pedido
        ");
            $stmt->execute([':id_pedido' => $id_pedido]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true,
                    'estado' => $row['estado']
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener estado: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerDetallesPedido") {
        try {
            $id_pedido = $_POST['id_pedido'];

            $stmt = $conexion->prepare("
            SELECT id_platillo, nombre_platillo, cantidad, precio_unitario, subtotal, nota 
            FROM detalles_pedido 
            WHERE id_pedido = :id_pedido
        ");
            $stmt->execute([':id_pedido' => $id_pedido]);

            $detalles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $detalles[] = $row;
            }
            echo json_encode($detalles, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener detalles: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "CrearPedido") {
        // DEBUG: Log de datos recibidos
        error_log("=== DEBUG PEDIDO ===");
        error_log("ID Cliente recibido: " . ($_POST['id_cliente'] ?? 'NULL'));
        error_log("Nombre Cliente recibido: " . ($_POST['nombre_cliente'] ?? 'NO ESPECIFICADO'));
        error_log("ID Mesa: " . $_POST['id_mesa']);
        error_log("Función: " . $_POST['funcion']);
        try {
            $id_mesa = $_POST['id_mesa'];
            $id_mesero = $_POST['id_mesero'];
            $nombre_mesero = $_POST['nombre_mesero'];
            $pedidos_json = $_POST['pedidos'];

            $id_cliente = isset($_POST['id_cliente']) && !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : null;
            $nombre_cliente = isset($_POST['nombre_cliente']) ? $_POST['nombre_cliente'] : 'Cliente Temporal';

            $stmt = $conexion->prepare("SELECT estado FROM mesas WHERE id_mesa = :id_mesa");
            $stmt->execute([':id_mesa' => $id_mesa]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($mesa['estado'] === 'ocupada') {
                $stmtPedido = $conexion->prepare("
                SELECT id_pedido 
                FROM pedidos 
                WHERE id_mesa = :id_mesa 
                AND estado NOT IN ('pagado', 'cancelado')
                LIMIT 1
            ");
                $stmtPedido->execute([':id_mesa' => $id_mesa]);

                if ($stmtPedido->rowCount() > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Esta mesa ya tiene un pedido activo. Use "Agregar productos" en lugar de crear un nuevo pedido.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    $stmtReset = $conexion->prepare("UPDATE mesas SET estado = 'disponible' WHERE id_mesa = :id_mesa");
                    $stmtReset->execute([':id_mesa' => $id_mesa]);
                }
            }

            $stmt = $conexion->prepare("SELECT numero_mesa FROM mesas WHERE id_mesa = :id_mesa");
            $stmt->execute([':id_mesa' => $id_mesa]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            $numero_mesa = $mesa['numero_mesa'];

            $pedidos = json_decode($pedidos_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en formato de pedidos: ' . json_last_error_msg());
            }

            $conexion->beginTransaction();

            $total = 0;
            foreach ($pedidos as $pedido) {
                $total += $pedido['subtotal'];
            }

            $stmt = $conexion->prepare("
            INSERT INTO pedidos (id_mesa, id_mesero, nombre_mesero, id_cliente, nombre_cliente, total) 
            VALUES (:id_mesa, :id_mesero, :nombre_mesero, :id_cliente, :nombre_cliente, :total)
        ");
            $stmt->execute([
                ':id_mesa' => $id_mesa,
                ':id_mesero' => $id_mesero,
                ':nombre_mesero' => $nombre_mesero,
                ':id_cliente' => $id_cliente,
                ':nombre_cliente' => $nombre_cliente,
                ':total' => $total
            ]);

            $id_pedido = $conexion->lastInsertId();

            foreach ($pedidos as $pedido) {
                $nota = isset($pedido['nota']) ? $pedido['nota'] : '';

                $stmt = $conexion->prepare("
        INSERT INTO detalles_pedido (id_pedido, id_platillo, nombre_platillo, cantidad, precio_unitario, subtotal, nota) 
        VALUES (:id_pedido, :id_platillo, :nombre_platillo, :cantidad, :precio_unitario, :subtotal, :nota)
    ");
                $stmt->execute([
                    ':id_pedido' => $id_pedido,
                    ':id_platillo' => $pedido['id_platillo'],
                    ':nombre_platillo' => $pedido['nombre_platillo'],
                    ':cantidad' => $pedido['cantidad'],
                    ':precio_unitario' => $pedido['precio_unitario'],
                    ':subtotal' => $pedido['subtotal'],
                    ':nota' => $nota
                ]);
            }

            $stmt = $conexion->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = :id_mesa");
            $stmt->execute([':id_mesa' => $id_mesa]);

            $conexion->commit();
            echo json_encode([
                'success' => true,
                'id_pedido' => $id_pedido,
                'id_cliente' => $id_cliente,
                'nombre_cliente' => $nombre_cliente,
                'message' => 'Pedido creado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    if ($funcion == "ObtenerPedidoActivoConCliente") {
        try {
            $id_mesa = $_POST['id_mesa'];

            $stmt = $conexion->prepare("
            SELECT id_cliente, nombre_cliente 
            FROM pedidos 
            WHERE id_mesa = :id_mesa 
            AND estado NOT IN ('finalizada', 'pagado', 'cancelado')
            ORDER BY fecha_creacion DESC 
            LIMIT 1
        ");
            $stmt->execute([':id_mesa' => $id_mesa]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'existe' => true,
                    'id_cliente' => $row['id_cliente'],
                    'nombre_cliente' => $row['nombre_cliente']
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'existe' => false
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerMeserosPorMesa") {
        try {
            $stmt = $conexion->query("
            SELECT 
                m.id_mesa,
                m.numero_mesa,
                m.capacidad,
                m.estado,
                m.ubicacion,
                p.nombre_mesero,
                p.nombre_cliente,
                p.fecha_actualizacion as ultima_actualizacion,
                p.estado as estado_pedido
            FROM mesas m
            LEFT JOIN pedidos p ON m.id_mesa = p.id_mesa 
                AND p.estado NOT IN ('pagado', 'cancelado', 'archivado')
                AND p.fecha_creacion = (
                    SELECT MAX(fecha_creacion) 
                    FROM pedidos 
                    WHERE id_mesa = m.id_mesa 
                    AND estado NOT IN ('pagado', 'cancelado', 'archivado')
                )
            WHERE m.fechabaja IS NULL 
            AND m.estado IN ('ocupada', 'con-pedido')
            ORDER BY 
                CASE m.estado 
                    WHEN 'con-pedido' THEN 1
                    WHEN 'ocupada' THEN 2
                    ELSE 3
                END,
                m.numero_mesa
        ");

            $mesas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Solo incluir mesas que tienen un mesero asignado
                if ($row['nombre_mesero']) {
                    $mesas[] = $row;
                }
            }
            echo json_encode($mesas, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener meseros por mesa: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ActualizarPedido") {
        // DEBUG: Log de datos recibidos
        error_log("=== DEBUG PEDIDO ===");
        error_log("ID Cliente recibido: " . ($_POST['id_cliente'] ?? 'NULL'));
        error_log("Nombre Cliente recibido: " . ($_POST['nombre_cliente'] ?? 'NO ESPECIFICADO'));
        error_log("ID Mesa: " . $_POST['id_mesa']);
        error_log("Función: " . $_POST['funcion']);
        try {
            $id_pedido = $_POST['id_pedido'];
            $pedidos_json = $_POST['pedidos'];

            $id_cliente = isset($_POST['id_cliente']) && !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : null;
            $nombre_cliente = isset($_POST['nombre_cliente']) ? $_POST['nombre_cliente'] : 'Cliente Temporal';

            $pedidos = json_decode($pedidos_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en formato de pedidos: ' . json_last_error_msg());
            }

            $conexion->beginTransaction();

            $stmt = $conexion->prepare("DELETE FROM detalles_pedido WHERE id_pedido = :id_pedido");
            $stmt->execute([':id_pedido' => $id_pedido]);

            $total = 0;
            foreach ($pedidos as $pedido) {
                $total += $pedido['subtotal'];
            }

            $stmt = $conexion->prepare("
            UPDATE pedidos 
            SET total = :total, 
                id_cliente = :id_cliente,
                nombre_cliente = :nombre_cliente,
                fecha_actualizacion = CURRENT_TIMESTAMP 
            WHERE id_pedido = :id_pedido
        ");
            $stmt->execute([
                ':total' => $total,
                ':id_cliente' => $id_cliente,
                ':nombre_cliente' => $nombre_cliente,
                ':id_pedido' => $id_pedido
            ]);

            foreach ($pedidos as $pedido) {
                $nota = isset($pedido['nota']) ? $pedido['nota'] : '';

                $stmt = $conexion->prepare("
        INSERT INTO detalles_pedido (id_pedido, id_platillo, nombre_platillo, cantidad, precio_unitario, subtotal, nota) 
        VALUES (:id_pedido, :id_platillo, :nombre_platillo, :cantidad, :precio_unitario, :subtotal, :nota)
    ");
                $stmt->execute([
                    ':id_pedido' => $id_pedido,
                    ':id_platillo' => $pedido['id_platillo'],
                    ':nombre_platillo' => $pedido['nombre_platillo'],
                    ':cantidad' => $pedido['cantidad'],
                    ':precio_unitario' => $pedido['precio_unitario'],
                    ':subtotal' => $pedido['subtotal'],
                    ':nota' => $nota
                ]);
            }

            $conexion->commit();
            echo json_encode([
                'success' => true,
                'id_pedido' => $id_pedido,
                'id_cliente' => $id_cliente,
                'nombre_cliente' => $nombre_cliente,
                'message' => 'Pedido actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerPedidosActivos") {
        try {
            $stmt = $conexion->query("
            SELECT 
                p.id_pedido, 
                m.numero_mesa, 
                p.nombre_mesero, 
                p.estado, 
                p.total, 
                p.fecha_creacion,
                m.estado as estado_mesa
            FROM pedidos p
            INNER JOIN mesas m ON p.id_mesa = m.id_mesa
            WHERE p.estado NOT IN ('pagado', 'cancelado', 'archivado')  -- CAMBIAR: incluir todos los estados activos
            ORDER BY p.fecha_creacion DESC
        ");

            $pedidos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedidos[] = $row;
            }
            echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener pedidos activos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "BuscarClientes") {
        try {
            $termino = $_POST['termino'];

            $termino = substr($termino, 0, 50);

            $stmt = $conexion->prepare("
            SELECT id_cliente, nombre, apellidos, telefono 
            FROM clientes 
            WHERE (LOWER(nombre) LIKE LOWER(:termino) OR 
                   LOWER(apellidos) LIKE LOWER(:termino) OR 
                   telefono LIKE :termino)
            AND fechabaja IS NULL 
            ORDER BY 
                CASE 
                    WHEN LOWER(nombre) LIKE LOWER(:termino_exacto) THEN 1
                    WHEN LOWER(apellidos) LIKE LOWER(:termino_exacto) THEN 2
                    ELSE 3
                END,
                nombre, apellidos 
            LIMIT 15
        ");

            $termino_like = "%$termino%";
            $termino_exacto = "$termino%";

            $stmt->execute([
                ':termino' => $termino_like,
                ':termino_exacto' => $termino_exacto
            ]);

            $clientes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $clientes[] = $row;
            }

            echo json_encode($clientes, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en búsqueda de clientes: " . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ActualizarEstadoMesa") {
        try {
            $id_mesa = $_POST['id_mesa'];
            $estado = $_POST['estado'];

            // SOLO ACTUALIZAR EL ESTADO, NO EL CLIENTE
            $stmt = $conexion->prepare("
            UPDATE mesas 
            SET estado = :estado
            WHERE id_mesa = :id_mesa
        ");

            $stmt->execute([
                ':id_mesa' => $id_mesa,
                ':estado' => $estado
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerPedidosFinalizados") {
        try {
            $stmt = $conexion->query("
                SELECT 
                    p.id_pedido, 
                    m.numero_mesa, 
                    p.nombre_mesero, 
                    p.estado, 
                    p.total, 
                    p.fecha_creacion,
                    p.fecha_actualizacion
                FROM pedidos p
                INNER JOIN mesas m ON p.id_mesa = m.id_mesa
                WHERE p.estado = 'finalizada' 
                ORDER BY p.fecha_actualizacion DESC
            ");

            $pedidos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedidos[] = $row;
            }
            echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener pedidos finalizados: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "GenerarCuenta") {
        try {
            $id_pedido = $_POST['id_pedido'];
            $id_mesa = $_POST['id_mesa'];

            $conexion->beginTransaction();

            $stmt = $conexion->prepare("
                UPDATE pedidos 
                SET estado = 'pagado', fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id_pedido = :id_pedido
            ");
            $stmt->execute([':id_pedido' => $id_pedido]);

            $stmt = $conexion->prepare("
                UPDATE mesas 
                SET estado = 'disponible' 
                WHERE id_mesa = :id_mesa
            ");
            $stmt->execute([':id_mesa' => $id_mesa]);

            $conexion->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Cuenta generada y mesa liberada correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar cuenta: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "ObtenerDatosTicket") {
        try {
            $id_pedido = $_POST['id_pedido'];

            // Obtener información completa del pedido
            $stmt = $conexion->prepare("
            SELECT 
                p.id_pedido,
                p.fecha_creacion,
                p.total,
                p.nombre_mesero,
                p.nombre_cliente,
                m.numero_mesa,
                m.ubicacion
            FROM pedidos p
            INNER JOIN mesas m ON p.id_mesa = m.id_mesa
            WHERE p.id_pedido = :id_pedido
        ");
            $stmt->execute([':id_pedido' => $id_pedido]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                echo json_encode(['error' => 'Pedido no encontrado'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Obtener detalles del pedido
            $stmt = $conexion->prepare("
            SELECT 
                nombre_platillo,
                cantidad,
                precio_unitario,
                subtotal
            FROM detalles_pedido 
            WHERE id_pedido = :id_pedido
            ORDER BY id_detalle
        ");
            $stmt->execute([':id_pedido' => $id_pedido]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'pedido' => $pedido,
                'detalles' => $detalles
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener datos del ticket: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "AgregarProductosPedido") {
        try {
            $id_pedido = $_POST['id_pedido'];
            $pedidos_json = $_POST['pedidos'];

            // NO usar los datos de cliente enviados, mantener los originales del pedido
            $nuevosPedidos = json_decode($pedidos_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en formato de pedidos: ' . json_last_error_msg());
            }

            $conexion->beginTransaction();

            // Obtener el pedido actual para mantener el cliente original
            $stmt = $conexion->prepare("SELECT total, id_cliente, nombre_cliente FROM pedidos WHERE id_pedido = :id_pedido");
            $stmt->execute([':id_pedido' => $id_pedido]);
            $pedidoActual = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalActual = $pedidoActual['total'];
            $id_cliente_original = $pedidoActual['id_cliente'];
            $nombre_cliente_original = $pedidoActual['nombre_cliente'];

            $nuevoTotal = $totalActual;

            // SOLO AGREGAR LOS NUEVOS PRODUCTOS, NO ACTUALIZAR LOS EXISTENTES
            foreach ($nuevosPedidos as $pedido) {
                $nota = isset($pedido['nota']) ? $pedido['nota'] : '';

                $stmt = $conexion->prepare("
                INSERT INTO detalles_pedido (id_pedido, id_platillo, nombre_platillo, cantidad, precio_unitario, subtotal, nota) 
                VALUES (:id_pedido, :id_platillo, :nombre_platillo, :cantidad, :precio_unitario, :subtotal, :nota)
            ");
                $stmt->execute([
                    ':id_pedido' => $id_pedido,
                    ':id_platillo' => $pedido['id_platillo'],
                    ':nombre_platillo' => $pedido['nombre_platillo'],
                    ':cantidad' => $pedido['cantidad'],
                    ':precio_unitario' => $pedido['precio_unitario'],
                    ':subtotal' => $pedido['subtotal'],
                    ':nota' => $nota
                ]);

                // Sumar al total solo los nuevos productos
                $nuevoTotal += $pedido['subtotal'];
            }

            // Actualizar el total del pedido pero MANTENER EL CLIENTE ORIGINAL
            $stmt = $conexion->prepare("
            UPDATE pedidos 
            SET total = :total, 
                fecha_actualizacion = CURRENT_TIMESTAMP 
            WHERE id_pedido = :id_pedido
        ");
            $stmt->execute([
                ':total' => $nuevoTotal,
                ':id_pedido' => $id_pedido
            ]);

            $conexion->commit();
            echo json_encode([
                'success' => true,
                'id_pedido' => $id_pedido,
                'id_cliente' => $id_cliente_original, // Devolver el cliente original
                'nombre_cliente' => $nombre_cliente_original, // Devolver el cliente original
                'message' => 'Productos agregados al pedido correctamente'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Error al agregar productos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($funcion == "LimpiarPedidosAnteriores") {
        try {
            $id_mesa = $_POST['id_mesa'];

            $stmt = $conexion->prepare("
                UPDATE pedidos 
                SET estado = 'archivado' 
                WHERE id_mesa = :id_mesa 
                AND estado IN ('pagado', 'finalizada')
            ");
            $stmt->execute([':id_mesa' => $id_mesa]);

            echo json_encode([
                'success' => true,
                'message' => 'Pedidos anteriores archivados'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al limpiar pedidos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    echo json_encode(['error' => 'Función no válida: ' . $funcion], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Método no válido o función no especificada'], JSON_UNESCAPED_UNICODE);
}
?>