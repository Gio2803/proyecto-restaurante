<?php
require_once '../conexion.php';

if (isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    switch ($funcion) {
        case 'Tabla':
            mostrarTablaVentas();
            break;
        case 'DetallesPedido':
            mostrarDetallesPedido();
            break;
        case 'GenerarTicket':
            generarTicket();
            break;
        case 'ObtenerEstadisticas':
            obtenerEstadisticas();
            break;
    }
}

function mostrarTablaVentas()
{
    global $conexion;

    $fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
    $fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';

    $sql = "SELECT * FROM vista_resumen_pedidos WHERE 1=1";
    $params = [];

    if (!empty($fecha_desde)) {
        $sql .= " AND DATE(fecha_creacion) >= ?";
        $params[] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND DATE(fecha_creacion) <= ?";
        $params[] = $fecha_hasta;
    }

    $sql .= " ORDER BY id_pedido DESC";

    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($pedidos) > 0) {
            foreach ($pedidos as $pedido) {
                $clase_estado = $pedido['estado'] == 'pagado' ? 'estado-pagado' : 'estado-pendiente';
                $fecha_formateada = date('d/m/Y H:i', strtotime($pedido['fecha_creacion']));

                echo "<tr>
                    <td>{$pedido['id_pedido']}</td>
                    <td>{$pedido['numero_mesa']}</td>
                    <td>{$pedido['cliente']}</td>
                    <td>{$pedido['mesero']}</td>
                    <td>$" . number_format($pedido['total'], 2) . "</td>
                    <td><span class='{$clase_estado}'>{$pedido['estado']}</span></td>
                    <td>{$fecha_formateada}</td>
                    <td>
                        <button class='btn btn-sm btn-primary ver-detalles' idpedido='{$pedido['id_pedido']}' title='Ver detalles'>
                            <i class='bi bi-eye'></i>
                        </button>
                        <button class='btn btn-sm btn-success imprimir-ticket' idpedido='{$pedido['id_pedido']}' title='Imprimir ticket'>
                            <i class='bi bi-receipt'></i>
                        </button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='8' class='text-center'>No se encontraron pedidos con los filtros aplicados</td></tr>";
        }
    } catch (Exception $e) {
        echo "<tr><td colspan='8' class='text-center'>Error al cargar los datos: " . $e->getMessage() . "</td></tr>";
    }
}

function mostrarDetallesPedido()
{
    global $conexion;

    $id = $_POST['id'];

    try {
        // Obtener información del pedido
        $stmt = $conexion->prepare("SELECT * FROM vista_resumen_pedidos WHERE id_pedido = ?");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $fecha_creacion = date('d/m/Y H:i', strtotime($pedido['fecha_creacion']));
            $fecha_actualizacion = date('d/m/Y H:i', strtotime($pedido['fecha_actualizacion']));
            $clase_estado = $pedido['estado'] == 'pagado' ? 'estado-pagado' : 'estado-pendiente';

            // Obtener detalles del pedido (platillos)
            $stmt_detalles = $conexion->prepare("
                SELECT * FROM detalles_pedido 
                WHERE id_pedido = ?
            ");
            $stmt_detalles->execute([$id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

            echo "
            <div class='row'>
                <div class='col-md-6'>
                    <p><strong>ID Pedido:</strong> {$pedido['id_pedido']}</p>
                    <p><strong>Mesa:</strong> {$pedido['numero_mesa']}</p>
                    <p><strong>Cliente:</strong> {$pedido['cliente']}</p>
                </div>
                <div class='col-md-6'>
                    <p><strong>Mesero:</strong> {$pedido['mesero']}</p>
                    <p><strong>Estado:</strong> <span class='{$clase_estado}'>{$pedido['estado']}</span></p>
                    <p><strong>Fecha:</strong> {$fecha_creacion}</p>
                </div>
            </div>
            <div class='row mt-3'>
                <div class='col-12'>
                    <h5>Detalles del Pedido</h5>
                    <table class='table table-bordered'>
                        <thead>
                            <tr>
                                <th>Platillo</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>";

            if (count($detalles) > 0) {
                foreach ($detalles as $detalle) {
                    $nota = !empty($detalle['nota']) ? $detalle['nota'] : 'Sin nota';
                    echo "<tr>
                        <td>{$detalle['nombre_platillo']}</td>
                        <td>{$detalle['cantidad']}</td>
                        <td>$" . number_format($detalle['precio_unitario'], 2) . "</td>
                        <td>$" . number_format($detalle['subtotal'], 2) . "</td>
                        <td><small class='nota-platillo'>{$nota}</small></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No se encontraron detalles del pedido</td></tr>";
            }

            echo "</tbody>
                        <tfoot>
                            <tr>
                                <td colspan='3' class='text-end'><strong>Total:</strong></td>
                                <td colspan='2'><strong>$" . number_format($pedido['total'], 2) . "</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>";
        } else {
            echo "<p class='text-center'>No se encontró el pedido solicitado</p>";
        }
    } catch (Exception $e) {
        echo "<p class='text-center'>Error al cargar los detalles: " . $e->getMessage() . "</p>";
    }
}

function generarTicket()
{
    global $conexion;

    $id = $_POST['id'];

    try {
        // Obtener información del pedido
        $stmt = $conexion->prepare("SELECT * FROM vista_resumen_pedidos WHERE id_pedido = ?");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $fecha = date('d/m/Y H:i', strtotime($pedido['fecha_creacion']));

            // Obtener detalles del pedido (platillos)
            $stmt_detalles = $conexion->prepare("
                SELECT * FROM detalles_pedido 
                WHERE id_pedido = ?
            ");
            $stmt_detalles->execute([$id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

            // Formatear fecha correctamente como en croquis.php
            $fechaObj = new DateTime($pedido['fecha_creacion']);
            $fechaFormateada = $fechaObj->format('d/m/Y');
            $horaFormateada = $fechaObj->format('H:i');

            // CALCULAR EL TOTAL CORRECTAMENTE - SUMAR TODOS LOS PRODUCTOS
            $total = 0;
            foreach ($detalles as $detalle) {
                $subtotalItem = floatval($detalle['subtotal']);
                $total += $subtotalItem;
            }

            // También usar el total de la base de datos como respaldo
            $totalBD = floatval($pedido['total']);
            if ($totalBD > $total) {
                $total = $totalBD; // Usar el mayor de los dos cálculos
            }

            echo "
            <div class='ticket-container' style='font-family: \"Courier New\", monospace; max-width: 300px; margin: 0 auto; background: white; padding: 20px; border: 2px solid #000;'>
                <div class='ticket-header text-center mb-3'>
                    <h4 style='margin: 0; font-weight: bold; font-size: 18px;'>🍕 PIZZERÍA DEL CENTRO</h4>
                    <p style='margin: 2px 0; font-size: 12px;'>Av. Principal #123, Ciudad</p>
                    <p style='margin: 2px 0; font-size: 12px;'>Tel: (555) 123-4567</p>
                    <p style='margin: 2px 0; font-size: 12px;'>RFC: PIZ123456789</p>
                    <hr style='margin: 8px 0; border-top: 2px dashed #000;'>
                    <p style='margin: 0; font-size: 15px;'> Ticket reimpreso</p>
                </div>
                    
                <div class='ticket-info mb-3' style='font-size: 12px;'>
                    <div class='row'>
                        <div class='col-6'>
                            <strong>TICKET:</strong> #{$pedido['id_pedido']}
                        </div>
                        <div class='col-6 text-end'>
                            <strong>MESA:</strong> {$pedido['numero_mesa']}
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-12'>
                            <strong>FECHA:</strong> {$fechaFormateada}
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-12'>
                            <strong>HORA:</strong> {$horaFormateada}
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-12'>
                            <strong>MESERO:</strong> {$pedido['mesero']}
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-12'>
                            <strong>CLIENTE:</strong> {$pedido['cliente']}
                        </div>
                    </div>
                </div>
                
                <hr style='margin: 8px 0; border-top: 1px solid #000;'>
                
                <div class='ticket-items mb-3'>
                    <table style='width: 100%; font-size: 11px; border-collapse: collapse;'>
                        <thead>
                            <tr style='border-bottom: 1px dashed #000;'>
                                <th style='text-align: left; width: 45%; padding: 2px 0;'>PRODUCTO</th>
                                <th style='text-align: center; width: 15%; padding: 2px 0;'>CANT</th>
                                <th style='text-align: right; width: 20%; padding: 2px 0;'>PRECIO</th>
                                <th style='text-align: right; width: 20%; padding: 2px 0;'>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>";

            // Agregar items y calcular total nuevamente
            $totalCalculado = 0;
            foreach ($detalles as $detalle) {
                $precio = floatval($detalle['precio_unitario']);
                $subtotalItem = floatval($detalle['subtotal']);
                $totalCalculado += $subtotalItem;

                // Mostrar nota si existe
                $notaHTML = '';
                if (!empty($detalle['nota'])) {
                    $notaHTML = "<br><small style='color: #e67e22; font-style: italic;'>Nota: {$detalle['nota']}</small>";
                }

                echo "
                <tr style='border-bottom: 1px dashed #ccc;'>
                    <td style='text-align: left; padding: 3px 0;'>{$detalle['nombre_platillo']}{$notaHTML}</td>
                    <td style='text-align: center; padding: 3px 0;'>{$detalle['cantidad']}</td>
                    <td style='text-align: right; padding: 3px 0;'>$" . number_format($precio, 2) . "</td>
                    <td style='text-align: right; padding: 3px 0;'>$" . number_format($subtotalItem, 2) . "</td>
                </tr>";
            }

            // Usar el total calculado si es mayor que el de la BD
            if ($totalCalculado > $total) {
                $total = $totalCalculado;
            }

            echo "
                        </tbody>
                    </table>
                </div>
                
                <hr style='margin: 8px 0; border-top: 1px solid #000;'>
                
                <div class='ticket-totals' style='font-size: 12px;'>
                    <div class='row' style='font-size: 14px; font-weight: bold; border-top: 2px solid #000; padding-top: 8px; margin-top: 5px;'>
                        <div class='col-8 text-end'><strong>TOTAL:</strong></div>
                        <div class='col-4 text-end'>$" . number_format($total, 2) . "</div>
                    </div>
                    <div class='row' style='font-size: 10px; color: #666; margin-top: 3px;'>
                        <div class='col-12 text-center'>
                            <em>IVA INCLUIDO</em>
                        </div>
                    </div>
                </div>
                
                <hr style='margin: 15px 0; border-top: 2px dashed #000;'>
                
                <div class='ticket-footer text-center' style='font-size: 11px;'>
                    <p style='margin: 5px 0;'>
                        <strong>¡GRACIAS POR SU PREFERENCIA!</strong>
                    </p>
                    <p style='margin: 5px 0;'>
                        Este ticket es su comprobante de pago
                    </p>
                    <p style='margin: 5px 0;'>
                        *** {$fechaFormateada} {$horaFormateada} ***
                    </p>
                </div>
            </div>";
        } else {
            echo "<p>No se pudo generar el ticket</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error al generar el ticket: " . $e->getMessage() . "</p>";
    }
}

function obtenerEstadisticas()
{
    global $conexion;

    $fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
    $fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';

    // Consulta mejorada que incluye todos los estados relevantes
    $sql = "SELECT 
                COUNT(*) as total_ventas, 
                COALESCE(SUM(p.total), 0) as total_ingresos 
            FROM pedidos p
            WHERE p.estado IN ('pagado', 'finalizada')"; // Incluir ambos estados

    $params = [];

    if (!empty($fecha_desde)) {
        $sql .= " AND DATE(p.fecha_creacion) >= ?";
        $params[] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND DATE(p.fecha_creacion) <= ?";
        $params[] = $fecha_hasta;
    }

    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Asegurarse de que los valores sean numéricos
        $total_ventas = (int) ($resultado['total_ventas'] ?? 0);
        $total_ingresos = (float) ($resultado['total_ingresos'] ?? 0);

        // Debug: registrar los resultados
        error_log("Estadísticas - Ventas: " . $total_ventas . ", Ingresos: " . $total_ingresos);

        // ENVIAR CABECERA JSON PRIMERO
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'total_ventas' => $total_ventas,
            'total_ingresos' => $total_ingresos
        ], JSON_NUMERIC_CHECK); // Asegurar que los números se envíen como números

    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticas: " . $e->getMessage());

        // ENVIAR CABECERA JSON PRIMERO
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'total_ventas' => 0,
            'total_ingresos' => 0,
            'error' => $e->getMessage()
        ], JSON_NUMERIC_CHECK);
    }

    // IMPORTANTE: Salir después de enviar la respuesta
    exit;
}
?>