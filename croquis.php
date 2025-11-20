<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'check_session.php';
require_once 'conexion.php';

// VERIFICACIÓN TEMPORAL SIMPLIFICADA - PERMITIR ACCESO MIENTRAS SE CONFIGURA
$pagina_actual = 'croquis.php';

try {
    // Primero verificar si las tablas de permisos existen
    $stmt = $conexion->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'permisos_menu'
        ) as tabla_existe
    ");
    $stmt->execute();
    $tabla_existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tabla_existe['tabla_existe'] == 't' || $tabla_existe['tabla_existe'] === true) {
        // La tabla existe, verificar permisos
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as tiene_permiso 
            FROM permisos_menu pm 
            INNER JOIN menu_items mi ON pm.menu_item_id = mi.id 
            WHERE pm.id_usuario = ? AND mi.url = ? AND pm.activo = true
        ");
        $stmt->execute([$_SESSION['id_usuario'], $pagina_actual]);
        $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

        // DEBUG: Mostrar resultado de la consulta
        error_log("Permiso verificado: " . print_r($permiso, true));

        if (!$permiso || $permiso['tiene_permiso'] == 0) {
            // Si no tiene permiso específico, verificar si es administrador
            if ($_SESSION['SISTEMA']['rol'] != 1) {
                header('Location: acceso_denegado.php');
                exit;
            }
            // Si es administrador, permitir acceso
        }
    } else {
        // Las tablas no existen, permitir acceso a todos (sistema en configuración)
        error_log("Tablas de permisos no existen - Acceso permitido");
    }

} catch (Exception $e) {
    // Si hay error en la consulta, permitir acceso temporalmente
    error_log("Error en verificación de permisos: " . $e->getMessage());
    // No redirigir, permitir acceso mientras se soluciona
}

// SI LLEGA AQUÍ, PERMITIR ACCESO
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Croquis Pizzería (Touch)</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #67C090;
            --secondary-color: #DDF4E7;
            --danger-color: #124170;
            --light-color: #26667F;
        }

        body {
            background-color: var(--secondary-color) !important;
            padding-top: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-custom {
            max-width: 1400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .banner {
            background-color: var(--danger-color);
            color: white;
            padding: 15px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            background-color: var(--light-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: white;
            border-bottom: 1px solid #eee;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--light-color));
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .croquis-container {
            padding: 25px;
            background: white;
            border-radius: 0 0 15px 15px;
        }

        .croquis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 20px;
            justify-content: center;
        }

        .mesa {
            width: 70px;
            height: 70px;
            border-radius: 20%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            touch-action: manipulation;
            user-select: none;
            transition: all 0.3s;
        }

        .mesa:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .mesa.ocupada {
            background: #DC3545;
        }

        .mesa.con-pedido {
            background: #FFA500;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 1050;
        }

        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-content-large {
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-top: 0;
            font-size: 1.5em;
            color: var(--danger-color);
        }

        .botones {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-content button {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            touch-action: manipulation;
            transition: all 0.3s;
        }

        .confirmar {
            background: var(--primary-color);
            color: white;
        }

        .confirmar:hover {
            background: var(--light-color);
            transform: translateY(-2px);
        }

        .cancelar {
            background: var(--danger-color);
            color: white;
        }

        .cancelar:hover {
            background: #0f3259;
            transform: translateY(-2px);
        }

        .categorias-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .categoria-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .categoria-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .categoria-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .categoria-nombre {
            font-weight: bold;
            font-size: 1.2em;
            color: var(--danger-color);
        }

        .categoria-descripcion {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .mesero-info {
            background: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .mesero-nombre {
            font-weight: bold;
            color: var(--danger-color);
            font-size: 1.1em;
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }

        .producto-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #eee;
        }

        .producto-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: var(--primary-color);
        }

        .producto-nombre {
            font-weight: bold;
            font-size: 1em;
            color: var(--danger-color);
            margin-bottom: 5px;
        }

        .producto-descripcion {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 8px;
            height: 40px;
            overflow: hidden;
        }

        .producto-precio {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        .pedidos-container {
            margin-top: 20px;
            border: 2px solid var(--secondary-color);
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
        }

        .lista-pedidos {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .item-pedido {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .item-pedido:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-producto {
            flex: 1;
            text-align: left;
        }

        .nombre-producto {
            font-weight: bold;
            color: var(--danger-color);
            margin-bottom: 5px;
        }

        .detalles-producto {
            font-size: 0.8em;
            color: #666;
        }

        .acciones-producto {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        .total-pedido {
            font-weight: bold;
            font-size: 1.2em;
            color: var(--danger-color);
            text-align: right;
            padding: 10px;
            border-top: 2px solid var(--primary-color);
            margin-top: 10px;
        }

        .estado-pedido {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .estado-recibida {
            background: #ffeb3b;
            color: #333;
        }

        .estado-preparacion {
            background: #2196f3;
            color: white;
        }

        .estado-finalizada {
            background: #4caf50;
            color: white;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 15px;
            }

            .btn-custom {
                width: 100%;
            }

            .croquis {
                grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
                gap: 15px;
            }

            .mesa {
                width: 70px;
                height: 70px;
                font-size: 1em;
            }

            .categorias-menu {
                grid-template-columns: 1fr;
            }

            .productos-grid {
                grid-template-columns: 1fr;
            }

            .item-pedido {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .acciones-producto {
                align-self: flex-end;
            }
        }

        .estado-pagado {
            background: #6f42c1;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }

        .cliente-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .cliente-item:hover {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
        }

        .cliente-nombre {
            font-weight: bold;
            color: var(--danger-color);
        }

        .cliente-telefono {
            font-size: 0.8em;
            color: #666;
        }

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .acciones-producto .btn-group {
            margin-left: 10px;
        }

        .acciones-producto .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .subtotal {
            font-weight: bold;
            color: var(--primary-color);
            min-width: 70px;
            text-align: right;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .alert-warning i {
            color: #856404;
            margin-right: 8px;
        }

        #btnAgregarMas {
            display: block !important;
        }

        #btnEnviarAdicionales {
            display: block !important;
        }

        .ticket-popup .swal2-popup {
            font-family: 'Courier New', monospace !important;
            max-width: 350px !important;
        }

        .ticket-container {
            background: white;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            border-bottom: 2px dashed #333;
            padding-bottom: 10px;
        }

        .ticket-items table {
            border-collapse: collapse;
        }

        .ticket-items td,
        .ticket-items th {
            padding: 2px 0;
            border-bottom: 1px dashed #ccc;
        }

        .ticket-totals .row {
            margin: 2px 0;
        }

        .ticket-footer {
            border-top: 2px dashed #333;
            padding-top: 10px;
        }

        @media print {
            .ticket-container {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 10px;
            }
        }

        .ticket-popup .swal2-popup {
            font-family: 'Courier New', monospace !important;
            max-width: 400px !important;
        }

        .ticket-container {
            background: white;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            font-size: 12px;
        }

        .ticket-items table {
            border-collapse: collapse;
            width: 100%;
        }

        .ticket-items td,
        .ticket-items th {
            padding: 3px 0;
            border-bottom: 1px dashed #ccc;
        }

        .ticket-totals .row {
            margin: 3px 0;
        }

        .ticket-footer {
            border-top: 2px dashed #333;
            padding-top: 10px;
        }

        .btn-pdf {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        @media print {
            .ticket-container {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10px !important;
                font-size: 10px !important;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Estilos del sidebar integrados */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background-color: var(--danger-color);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar-header {
            padding: 15px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sidebar-brand span {
            transition: opacity 0.3s ease;
            font-size: 0.9rem;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            display: none;
        }

        .sidebar-nav {
            padding: 10px 0;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            color: white !important;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--primary-color) !important;
            border-left-color: var(--primary-color);
        }

        .nav-link.active {
            background-color: rgba(103, 192, 144, 0.2);
            color: var(--primary-color) !important;
            border-left-color: var(--primary-color);
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .nav-link span {
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            display: none;
        }

        .dropdown-menu {
            background-color: var(--light-color);
            border: none;
            border-radius: 0 8px 8px 0;
            margin-left: 8px;
            min-width: 180px;
        }

        .dropdown-item {
            color: white !important;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--primary-color) !important;
        }

        .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .dropdown-item.text-danger:hover {
            color: #bd2130 !important;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .dropdown-toggle::after {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }

        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background: var(--primary-color);
            border: none;
            border-radius: 4px;
            color: white;
            padding: 6px 10px;
            cursor: pointer;
            display: none;
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: 220px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background-color: var(--secondary-color);
            padding: 15px;
        }

        .main-content.expanded {
            margin-left: 60px;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 220px;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .main-content.mobile-expanded {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .sidebar.collapsed {
                transform: translateX(-100%);
            }

            .sidebar.collapsed.mobile-open {
                transform: translateX(0);
                width: 220px;
            }
        }

        .sidebar::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 5px;
        }

        body {
            padding-top: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar-collapse-btn {
            position: absolute;
            top: 50%;
            right: -12px;
            transform: translateY(-50%);
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1002;
        }

        /* Estilos para las secciones del croquis */
        .seccion-mesas {
            transition: all 0.3s ease;
        }

        .seccion-mesas:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .etiqueta-seccion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .etiqueta-seccion h4 {
            margin: 0;
            color: var(--danger-color);
            font-size: 1.1em;
        }

        .etiqueta-seccion small {
            color: #666;
            font-weight: normal;
        }

        /* Colores diferentes para cada sección - TUS 3 ZONAS */
        .etiqueta-seccion.zona-entrada {
            background: linear-gradient(135deg, rgba(103, 192, 144, 0.2), rgba(76, 175, 80, 0.2));
            border-left: 4px solid var(--primary-color);
        }

        .etiqueta-seccion.zona-barra {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.2), rgba(25, 118, 210, 0.2));
            border-left: 4px solid #2196F3;
        }

        .etiqueta-seccion.zona-baños {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.2), rgba(245, 124, 0, 0.2));
            border-left: 4px solid #FF9800;
        }

        /* Estilos responsivos para las secciones */
        @media (max-width: 768px) {
            .seccion-mesas {
                padding: 10px;
            }

            .etiqueta-seccion {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .etiqueta-seccion h4 {
                font-size: 1em;
            }

            .mesas-container {
                grid-template-columns: repeat(auto-fit, minmax(60px, 1fr)) !important;
                gap: 10px !important;
            }
        }

        /* Efectos hover para mesas */
        .mesa {
            position: relative;
            transition: all 0.3s ease;
        }

        .mesa:hover .info-capacidad {
            background: rgba(0, 0, 0, 0.9);
        }

        .info-capacidad {
            transition: all 0.3s ease;
        }

        .nota-producto {
            font-size: 0.8em;
            color: #e67e22;
            font-style: italic;
            margin-top: 3px;
            background: rgba(230, 126, 34, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            border-left: 2px solid #e67e22;
        }

        /* Estilos para la lista de meseros por mesa */
        .mesa-mesero-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .mesa-mesero-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .mesa-mesero-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mesa-numero {
            background: var(--danger-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .mesero-nombre-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .mesa-estado {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .estado-ocupada {
            background: #dc3545;
            color: white;
        }

        .estado-con-pedido {
            background: #ffc107;
            color: #000;
        }

        .estado-disponible {
            background: #28a745;
            color: white;
        }

        .mesa-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .detalle-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }

        .detalle-item i {
            color: var(--primary-color);
            width: 16px;
        }

        .sin-mesas {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .sin-mesas i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #ccc;
        }

        /* Estilos para el buscador de productos */
        .buscador-productos {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            display: none;
            /* Inicialmente oculto */
        }

        .buscador-productos .input-group {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .buscador-productos .form-control {
            border: 2px solid var(--primary-color);
            border-right: none;
            font-size: 14px;
        }

        .buscador-productos .input-group-text {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
            border-right: none;
        }

        .buscador-productos .btn-outline-secondary {
            border: 2px solid var(--primary-color);
            border-left: none;
            color: var(--danger-color);
        }

        .buscador-productos .btn-outline-secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        .contador-productos {
            font-size: 0.85em;
            color: #666;
            text-align: center;
            margin-top: 8px;
            font-style: italic;
        }

        /* Mejoras para las tarjetas de productos */
        .producto-card mark {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 3px;
            font-weight: bold;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .buscador-productos {
                padding: 10px;
            }

            .buscador-productos .form-control {
                font-size: 16px;
                /* Para evitar zoom en iOS */
            }
        }
    </style>
</head>

<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'menu.php'; ?>
    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <div class="container container-custom">
            <div class="banner">
                <h1><i class="bi bi-geo-alt-fill"></i> Croquis de Mesas </h1>
            </div>

            <div class="header-actions">
                <div>
                    <button class="btn-custom" id="ver-estadisticas">
                        <i class="bi bi-bar-chart-fill"></i> Ver Estadísticas
                    </button>
                    <button class="btn-custom" id="ver-pedidos">
                        <i class="bi bi-list-check"></i> Ver Pedidos
                    </button>
                    <!-- NUEVO BOTÓN -->
                    <button class="btn-custom" id="ver-meseros-mesas">
                        <i class="bi bi-people-fill"></i> Ver Meseros por Mesa
                    </button>
                </div>
                <div class="d-none d-md-block">
                    <div class="d-flex gap-2">
                        <div class="stats-card">
                            <div class="stats-number" id="total-mesas">30</div>
                            <small>Mesas Totales</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="mesas-ocupadas">0</div>
                            <small>Mesas Ocupadas</small>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number" id="mesas-disponibles">30</div>
                            <small>Mesas Disponibles</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="croquis-container">
                <div class="croquis" id="croquis"></div>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div class="modal" id="modalConfirmacion">
            <div class="modal-content">
                <h2 id="mesaSeleccionadaConfirm">Mesa X</h2>
                <p>¿Desea atender esta mesa?</p>
                <div class="botones">
                    <button type="button" class="confirmar" id="btnConfirmarAtencion">✅ Sí, Atender Mesa</button>
                    <button type="button" class="cancelar" id="btnCancelarConfirmacion">❌ Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Selección de Cliente -->
        <div class="modal" id="modalCliente">
            <div class="modal-content">
                <h2>Seleccionar Cliente - Mesa <span id="mesaSeleccionadaCliente">X</span></h2>
                <div class="mb-3">
                    <label for="buscarCliente" class="form-label">Buscar Cliente:</label>
                    <input type="text" id="buscarCliente" class="form-control"
                        placeholder="Ingrese nombre del cliente...">
                    <div id="resultadosClientes" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="text-center my-3">
                    <strong>O</strong>
                </div>
                <div class="mb-3">
                    <label for="clienteTemporal" class="form-label">Agregar Cliente Temporal:</label>
                    <input type="text" id="clienteTemporal" class="form-control"
                        placeholder="Nombre del cliente (opcional)">
                    <small class="text-muted">Si no encuentra al cliente, puede ingresar un nombre temporal</small>
                </div>
                <div class="botones">
                    <button type="button" class="confirmar" id="btnConfirmarCliente">✅ Continuar</button>
                    <button type="button" class="cancelar" id="btnCancelarCliente">❌ Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Menú -->
        <div class="modal" id="modalMenu">
            <div class="modal-content modal-content-large">
                <h2 id="mesaSeleccionadaMenu">Mesa X</h2>
                <!-- Información del mesero -->
                <div class="mesero-info">
                    <i class="bi bi-person-circle"></i>
                    <span class="mesero-nombre" id="nombreMesero">
                        <?php echo $_SESSION['SISTEMA']['nombre'] ?? 'Mesero'; ?>
                    </span> <br>
                    <small>Atendiendo esta mesa</small>
                    <div id="infoClienteMesa" style="margin-top: 8px;"></div>
                </div>

                <!-- Categorías -->
                <h4>Seleccione una Categoría:</h4>
                <div class="categorias-menu" id="categoriasMenu"></div>

                <!-- Productos (INICIALMENTE OCULTO) -->
                <div id="productosContainer" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 id="tituloCategoria">Productos de <span id="nombreCategoria"></span></h4>
                        <button class="btn btn-secondary btn-sm" id="btnVolverCategorias">
                            <i class="bi bi-arrow-left"></i> Volver a Categorías
                        </button>
                    </div>

                    <!-- BUSCADOR DE PRODUCTOS (SOLO APARECE EN PRODUCTOS) -->
                    <div class="buscador-productos mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="buscadorProductos" class="form-control"
                                placeholder="Buscar productos...">
                            <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusquedaProductos">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        <div class="contador-productos" id="contadorProductos"></div>
                    </div>

                    <!-- Grid de productos -->
                    <div class="productos-grid" id="productosGrid"></div>
                </div>

                <!-- Lista de Pedidos -->
                <div id="pedidosContainer" class="pedidos-container" style="display: none;">
                    <h4>📋 Lista de Pedidos - Mesa <span id="numeroMesaPedido"></span></h4>
                    <div class="lista-pedidos" id="listaPedidos"></div>
                    <div class="total-pedido" id="totalPedido">Total: $0.00</div>
                    <div class="botones mt-3">
                        <button type="button" class="confirmar" id="btnEnviarOrden">📤 Enviar Orden a Cocina</button>
                        <button type="button" class="btn-custom" id="btnAgregarMas">➕ Agregar Más Productos</button>
                        <button type="button" class="btn btn-warning" id="btnEnviarAdicionales"
                            style="display: none;">📤 Enviar Productos Adicionales</button>
                    </div>
                </div>
                <div class="botones mt-3">
                    <button type="button" class="cancelar" id="btnCerrarMenu">❌ Cerrar Menú</button>
                </div>
            </div>
        </div>

        <!-- Modal de Pedidos Activos -->
        <div class="modal" id="modalPedidos">
            <div class="modal-content modal-content-large">
                <h2>📋 Pedidos Activos</h2>
                <div id="listaPedidosActivos"></div>
                <div class="botones mt-3">
                    <button type="button" class="cancelar" id="btnCerrarPedidos">❌ Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Meseros por Mesa -->
        <div class="modal" id="modalMeserosMesas">
            <div class="modal-content modal-content-large">
                <h2>👥 Meseros Atendiendo Mesas</h2>
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" id="buscarMesero" class="form-control" placeholder="Buscar por mesero...">
                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                <div id="listaMeserosMesas"></div>
                <div class="botones mt-3">
                    <button type="button" class="cancelar" id="btnCerrarMeserosMesas">❌ Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        // ========== FUNCIONES DEL SIDEBAR ==========
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('mobile-open');
            mainContent.classList.toggle('mobile-expanded');
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('mobile-open');
                mainContent.classList.remove('mobile-expanded');
            }
        }
        // ========== INICIALIZACIÓN DEL SIDEBAR ==========
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', toggleMobileSidebar);
            mainContent.addEventListener('click', closeMobileSidebar);

            function handleResize() {
                const sidebar = document.getElementById('sidebar');
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('mobile-open');
                    mainContent.classList.remove('mobile-expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                }
            }

            window.addEventListener('resize', handleResize);

            // Auto-colapsar en móvil
            if (window.innerWidth <= 992) {
                closeMobileSidebar();
            }
        });


        // ========== VARIABLES GLOBALES DEL CROQUIS ==========
        let mesaActual = null;
        let mesasOcupadas = 0;
        let idMesaActual = 0;
        let numeroMesaActual = 0;
        let categoriasData = [];
        let pedidos = [];
        let pedidoActualId = null;
        let mesasData = [];
        let clienteSeleccionado = null;
        let productosPendientesEnvio = [];
        let productosActuales = [];
        let categoriaActual = '';

        // Iconos para categorías
        const iconosCategorias = {
            'Bebidas': '🥤',
            'Pizzas': '🍕',
            'Postres': '🍰',
            'Complementos': '🍟'
        };

        // ========== FUNCIONES GLOBALES ==========

        function actualizarEstadisticas() {
            const totalMesas = mesasData.length;
            const ocupadas = mesasData.filter(mesa =>
                mesa.estado === 'ocupada' || mesa.estado === 'con-pedido'
            ).length;

            document.getElementById('mesas-ocupadas').textContent = ocupadas;
            document.getElementById('mesas-disponibles').textContent = totalMesas - ocupadas;
            document.getElementById('total-mesas').textContent = totalMesas;
        }

        // ========== FUNCIONES DE CLIENTES ==========

        // Función para cargar meseros por mesa
        function cargarMeserosPorMesa() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerMeserosPorMesa'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        mostrarMeserosMesas([]);
                        return;
                    }
                    mostrarMeserosMesas(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    mostrarMeserosMesas([]);
                }
            });
        }

        // Función para mostrar meseros por mesa
        function mostrarMeserosMesas(mesas) {
            const lista = document.getElementById('listaMeserosMesas');

            if (mesas.length === 0) {
                lista.innerHTML = `
            <div class="sin-mesas">
                <i class="bi bi-person-x"></i>
                <p>No hay mesas siendo atendidas en este momento</p>
                <small class="text-muted">Todas las mesas están disponibles</small>
            </div>
        `;
                return;
            }

            lista.innerHTML = '';

            // Ordenar mesas por número
            mesas.sort((a, b) => a.numero_mesa - b.numero_mesa);

            mesas.forEach(mesa => {
                const card = document.createElement('div');
                card.className = 'mesa-mesero-item';

                let estadoClass = '';
                let estadoText = '';

                switch (mesa.estado) {
                    case 'ocupada':
                        estadoClass = 'estado-ocupada';
                        estadoText = 'OCUPADA';
                        break;
                    case 'con-pedido':
                        estadoClass = 'estado-con-pedido';
                        estadoText = 'CON PEDIDO';
                        break;
                    default:
                        estadoClass = 'estado-disponible';
                        estadoText = 'DISPONIBLE';
                }

                card.innerHTML = `
            <div class="mesa-mesero-header">
                <div class="mesa-mesero-info">
                    <span class="mesa-numero">Mesa ${mesa.numero_mesa}</span>
                    ${mesa.nombre_mesero ? `<span class="mesero-nombre-badge">${mesa.nombre_mesero}</span>` : ''}
                </div>
                <span class="mesa-estado ${estadoClass}">${estadoText}</span>
            </div>
            
            <div class="mesa-detalles">
                <div class="detalle-item">
                    <i class="bi bi-geo-alt"></i>
                    <span><strong>Ubicación:</strong> ${mesa.ubicacion || 'No especificada'}</span>
                </div>
                <div class="detalle-item">
                    <i class="bi bi-people"></i>
                    <span><strong>Capacidad:</strong> ${mesa.capacidad} personas</span>
                </div>
                ${mesa.nombre_cliente && mesa.nombre_cliente !== 'Cliente Temporal' ? `
                <div class="detalle-item">
                    <i class="bi bi-person-check"></i>
                    <span><strong>Cliente:</strong> ${mesa.nombre_cliente}</span>
                </div>
                ` : ''}
                ${mesa.ultima_actualizacion ? `
                <div class="detalle-item">
                    <i class="bi bi-clock"></i>
                    <span><strong>Última actualización:</strong> ${new Date(mesa.ultima_actualizacion).toLocaleString()}</span>
                </div>
                ` : ''}
            </div>
        `;

                lista.appendChild(card);
            });
        }

        // Función para filtrar mesas por mesero
        function filtrarMesasPorMesero(termino) {
            const items = document.querySelectorAll('.mesa-mesero-item');
            let encontrados = 0;

            items.forEach(item => {
                const texto = item.textContent.toLowerCase();
                if (texto.includes(termino.toLowerCase())) {
                    item.style.display = 'block';
                    encontrados++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Mostrar mensaje si no hay resultados
            const lista = document.getElementById('listaMeserosMesas');
            const mensajeNoResultados = lista.querySelector('.no-resultados');

            if (encontrados === 0 && termino !== '') {
                if (!mensajeNoResultados) {
                    const mensaje = document.createElement('div');
                    mensaje.className = 'no-resultados text-center p-4';
                    mensaje.innerHTML = `
                <i class="bi bi-search"></i>
                <p>No se encontraron mesas para "${termino}"</p>
            `;
                    lista.appendChild(mensaje);
                }
            } else if (mensajeNoResultados) {
                mensajeNoResultados.remove();
            }
        }

        function buscarClientes(termino) {
            if (termino.length < 2) {
                document.getElementById('resultadosClientes').innerHTML = '';
                return;
            }

            document.getElementById('resultadosClientes').innerHTML = `
        <div class="text-center p-2">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <small class="text-muted ms-2">Buscando clientes...</small>
        </div>
    `;

            if (window.busquedaClientesTimeout) {
                clearTimeout(window.busquedaClientesTimeout);
            }

            window.busquedaClientesTimeout = setTimeout(() => {
                $.ajax({
                    url: 'funciones/croquis.php',
                    type: 'POST',
                    data: {
                        funcion: 'BuscarClientes',
                        termino: termino
                    },
                    dataType: 'json',
                    timeout: 10000,
                    success: function (data) {
                        mostrarResultadosClientes(data);
                    },
                    error: function (xhr, status, error) {
                        if (status === 'timeout') {
                            document.getElementById('resultadosClientes').innerHTML = `
                        <div class="alert alert-warning p-2">
                            <small>La búsqueda tardó demasiado. Intente con menos caracteres.</small>
                        </div>
                    `;
                        } else {
                            console.error('Error al buscar clientes:', error);
                            document.getElementById('resultadosClientes').innerHTML = `
                        <div class="alert alert-danger p-2">
                            <small>Error al buscar clientes. Intente nuevamente.</small>
                        </div>
                    `;
                        }
                    },
                    complete: function () {
                        window.busquedaClientesTimeout = null;
                    }
                });
            }, 500);
        }

        function mostrarResultadosClientes(clientes) {
            const resultados = document.getElementById('resultadosClientes');

            if (clientes.length === 0) {
                resultados.innerHTML = `
            <div class="text-muted p-2">
                <small>No se encontraron clientes. Puede agregar un cliente temporal abajo.</small>
            </div>
        `;
                return;
            }

            resultados.innerHTML = '';
            clientes.forEach(cliente => {
                const item = document.createElement('div');
                item.className = 'cliente-item';
                item.innerHTML = `
            <div class="cliente-nombre">${cliente.nombre} ${cliente.apellidos || ''}</div>
            ${cliente.telefono ? `<div class="cliente-telefono">📞 ${cliente.telefono}</div>` : ''}
        `;
                item.addEventListener('click', () => {
                    seleccionarCliente(cliente);
                });
                resultados.appendChild(item);
            });
        }

        function seleccionarCliente(cliente) {
            // Asegurarse de capturar todos los datos del cliente
            clienteSeleccionado = {
                id_cliente: cliente.id_cliente,
                nombre: cliente.nombre,
                apellidos: cliente.apellidos || '',
                telefono: cliente.telefono || ''
            };

            document.getElementById('clienteTemporal').value = `${cliente.nombre} ${cliente.apellidos || ''}`;
            document.getElementById('resultadosClientes').innerHTML = `
        <div class="alert alert-success p-2">
            <strong>Cliente seleccionado:</strong> ${cliente.nombre} ${cliente.apellidos || ''}
            ${cliente.telefono ? `<br><small>Tel: ${cliente.telefono}</small>` : ''}
            <br><small><strong>ID:</strong> ${cliente.id_cliente}</small>
        </div>
    `;

            console.log('Cliente seleccionado en modal:', clienteSeleccionado);
        }

        function abrirModalCliente() {
            // Solo reiniciar si no hay cliente seleccionado
            // clienteSeleccionado = null; // ← COMENTA O ELIMINA ESTA LÍNEA

            document.getElementById('mesaSeleccionadaCliente').textContent = numeroMesaActual;
            document.getElementById('buscarCliente').value = '';

            // Si ya hay un cliente seleccionado, mostrarlo en el campo temporal
            if (clienteSeleccionado) {
                document.getElementById('clienteTemporal').value = `${clienteSeleccionado.nombre} ${clienteSeleccionado.apellidos || ''}`;
            } else {
                document.getElementById('clienteTemporal').value = '';
            }

            document.getElementById('resultadosClientes').innerHTML = `
        <div class="text-muted p-2">
            <small>Ingrese al menos 2 caracteres para buscar clientes...</small>
        </div>
    `;

            setTimeout(() => {
                document.getElementById('buscarCliente').focus();
            }, 100);

            document.getElementById('modalCliente').style.display = "flex";
        }

        function cerrarModalCliente() {
            document.getElementById('modalCliente').style.display = "none";
            // NO reiniciar clienteSeleccionado aquí, mantenerlo
            // clienteSeleccionado = null; // ← ELIMINA ESTA LÍNEA
        }

        function confirmarCliente() {
            const clienteTemporal = document.getElementById('clienteTemporal').value.trim();

            console.log('=== DEBUG CONFIRMAR CLIENTE ===');
            console.log('Cliente seleccionado antes de confirmar:', clienteSeleccionado);
            console.log('Cliente temporal ingresado:', clienteTemporal);

            // Si se seleccionó un cliente de la BD, mantenerlo
            if (clienteSeleccionado && clienteSeleccionado.id_cliente) {
                console.log('✅ Cliente de BD confirmado:', clienteSeleccionado);
            }
            // Si no se seleccionó un cliente de la BD pero se ingresó uno temporal
            else if (!clienteSeleccionado && clienteTemporal) {
                clienteSeleccionado = {
                    id_cliente: null,
                    nombre: clienteTemporal,
                    apellidos: '',
                    telefono: ''
                };
                console.log('✅ Cliente temporal creado:', clienteSeleccionado);
            }
            // Si no hay cliente seleccionado ni temporal, usar "Cliente Temporal"
            else if (!clienteSeleccionado && !clienteTemporal) {
                clienteSeleccionado = {
                    id_cliente: null,
                    nombre: 'Cliente Temporal',
                    apellidos: '',
                    telefono: ''
                };
                console.log('✅ Cliente temporal por defecto:', clienteSeleccionado);
            }

            console.log('Cliente final confirmado para pasar a modal menu:', clienteSeleccionado);
            cerrarModalCliente();
            procederConAtencionMesa();
        }

        // ========== FUNCIONES DE MESAS ==========

        function abrirModalConfirmacion(idMesa, numMesa, mesaElement) {
            mesaActual = mesaElement;
            idMesaActual = idMesa;
            numeroMesaActual = numMesa;
            document.getElementById('mesaSeleccionadaConfirm').textContent = "Mesa " + numMesa;
            document.getElementById('modalConfirmacion').style.display = "flex";
        }

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').style.display = "none";
            mesaActual = null;
        }

        function confirmarAtencion() {
            cerrarModalConfirmacion();
            abrirModalCliente();
        }

        function procederConAtencionMesa() {
            if (mesaActual && !mesaActual.classList.contains("ocupada")) {
                const datos = {
                    funcion: 'ActualizarEstadoMesa',
                    id_mesa: idMesaActual,
                    estado: 'ocupada'
                };

                $.ajax({
                    url: 'funciones/croquis.php',
                    type: 'POST',
                    data: datos,
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            mesaActual.classList.add("ocupada");

                            const mesaIndex = mesasData.findIndex(m => m.id_mesa === idMesaActual);
                            if (mesaIndex !== -1) {
                                mesasData[mesaIndex].estado = 'ocupada';
                            }

                            actualizarEstadisticas();
                            limpiarPedidosAnteriores(idMesaActual);

                            Swal.fire({
                                title: '¡Mesa Atendida!',
                                html: `Mesa ${numeroMesaActual} lista para ordenar`,
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#67C090'
                            }).then(() => {
                                // PASAR EL CLIENTE AL MODAL DEL MENÚ
                                abrirModalMenu(idMesaActual, numeroMesaActual, mesaActual, clienteSeleccionado);
                            });
                        }
                    }
                });
            } else {
                // PASAR EL CLIENTE AL MODAL DEL MENÚ
                abrirModalMenu(idMesaActual, numeroMesaActual, mesaActual, clienteSeleccionado);
            }
        }

        function abrirModalMenu(idMesa, numMesa, mesaElement, cliente = null) {
            idMesaActual = idMesa;
            numeroMesaActual = numMesa;
            mesaActual = mesaElement;

            console.log('=== DEBUG ABRIR MODAL MENU ===');
            console.log('Cliente recibido como parámetro:', cliente);
            console.log('Cliente seleccionado actual:', clienteSeleccionado);

            // PRIMERO: Verificar pedido activo para recuperar el cliente
            verificarPedidoActivoParaCliente(idMesa);

            // ESPERAR un momento para que la llamada AJAX termine y luego mostrar el modal
            setTimeout(() => {
                console.log('Cliente seleccionado después de verificar:', clienteSeleccionado);

                // SOLO si no hay cliente seleccionado y se pasa un cliente nuevo, usarlo
                if (cliente && !clienteSeleccionado) {
                    clienteSeleccionado = cliente;
                    console.log('✅ Usando cliente nuevo recibido como parámetro:', clienteSeleccionado);
                }

                mostrarModalMenuConCliente();
            }, 300);
        }

        // FUNCIÓN para mostrar el modal una vez que tenemos el cliente
        function mostrarModalMenuConCliente() {
            document.getElementById('mesaSeleccionadaMenu').textContent = "Mesa " + numeroMesaActual;
            document.getElementById('numeroMesaPedido').textContent = numeroMesaActual;

            // Mostrar información del cliente si existe
            const infoClienteElement = document.getElementById('infoClienteMesa');
            if (infoClienteElement && clienteSeleccionado) {
                if (clienteSeleccionado.id_cliente) {
                    infoClienteElement.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(103, 192, 144, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid var(--primary-color); margin-top: 8px;">
                    <i class="bi bi-person-check" style="color: var(--primary-color);"></i>
                    <div>
                        <strong style="color: var(--danger-color);">Cliente:</strong> ${clienteSeleccionado.nombre} ${clienteSeleccionado.apellidos || ''}
                        <br>
                        <small class="text-muted" style="font-size: 0.8em;">
                            <i class="bi bi-star-fill" style="color: gold;"></i> Cliente registrado (ID: ${clienteSeleccionado.id_cliente})
                        </small>
                    </div>
                </div>
            `;
                } else {
                    infoClienteElement.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(108, 117, 125, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #6c757d; margin-top: 8px;">
                    <i class="bi bi-person" style="color: #6c757d;"></i>
                    <div>
                        <strong style="color: var(--danger-color);">Cliente:</strong> ${clienteSeleccionado.nombre}
                        <br>
                        <small class="text-muted" style="font-size: 0.8em;">
                            <i class="bi bi-clock"></i> Cliente temporal
                        </small>
                    </div>
                </div>
            `;
                }
                infoClienteElement.style.display = 'block';
            } else {
                // Si no hay cliente, ocultar el elemento
                if (infoClienteElement) {
                    infoClienteElement.style.display = 'none';
                }
            }

            // LIMPIAR INDICADORES ANTERIORES
            const existingIndicator = document.getElementById('indicadorFinalizado');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            if (mesaActual && !mesaActual.classList.contains("ocupada") && !mesaActual.classList.contains("con-pedido")) {
                // Si es una mesa nueva, limpiar todo
                productosPendientesEnvio = [];
                pedidos = [];
                pedidoActualId = null;
                actualizarListaPedidos();
                limpiarPedidosAnteriores(idMesaActual);

                // Mostrar botones para nuevo pedido
                document.getElementById('btnEnviarAdicionales').style.display = 'none';
                document.getElementById('btnEnviarOrden').style.display = 'block';
                document.getElementById('btnAgregarMas').style.display = 'block';
            } else {
                // Si es una mesa existente, cargar el pedido
                verificarPedidoActivo(idMesaActual);
            }

            // SIEMPRE MOSTRAR CATEGORÍAS PRIMERO
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';

            cargarCategoriasDesdeBD();
            document.getElementById('modalMenu').style.display = "flex";
        }

        // NUEVA FUNCIÓN para obtener información del cliente del pedido activo
        function verificarPedidoActivoParaCliente(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidoActivoConCliente',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    console.log('=== DEBUG CLIENTE PEDIDO ACTIVO ===');
                    console.log('Datos recibidos:', data);

                    const infoClienteElement = document.getElementById('infoClienteMesa');
                    if (infoClienteElement && data.existe) {
                        // ACTUALIZAR LA VARIABLE GLOBAL clienteSeleccionado
                        if (data.id_cliente && data.nombre_cliente !== 'Cliente Temporal') {
                            clienteSeleccionado = {
                                id_cliente: data.id_cliente,
                                nombre: data.nombre_cliente,
                                apellidos: '',
                                telefono: ''
                            };
                            console.log('✅ Cliente recuperado del pedido activo (REGISTRADO):', clienteSeleccionado);
                        } else {
                            clienteSeleccionado = {
                                id_cliente: null,
                                nombre: data.nombre_cliente,
                                apellidos: '',
                                telefono: ''
                            };
                            console.log('✅ Cliente recuperado del pedido activo (TEMPORAL):', clienteSeleccionado);
                        }

                        if (data.id_cliente && data.nombre_cliente !== 'Cliente Temporal') {
                            infoClienteElement.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 8px; background: rgba(103, 192, 144, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid var(--primary-color); margin-top: 8px;">
                            <i class="bi bi-person-check" style="color: var(--primary-color);"></i>
                            <div>
                                <strong style="color: var(--danger-color);">Cliente:</strong> ${data.nombre_cliente}
                                <br>
                                <small class="text-muted" style="font-size: 0.8em;">
                                    <i class="bi bi-star-fill" style="color: gold;"></i> Cliente registrado
                                </small>
                            </div>
                        </div>
                    `;
                        } else {
                            infoClienteElement.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 8px; background: rgba(108, 117, 125, 0.1); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #6c757d; margin-top: 8px;">
                            <i class="bi bi-person" style="color: #6c757d;"></i>
                            <div>
                                <strong style="color: var(--danger-color);">Cliente:</strong> ${data.nombre_cliente}
                                <br>
                                <small class="text-muted" style="font-size: 0.8em;">
                                    <i class="bi bi-clock"></i> Cliente temporal
                                </small>
                            </div>
                        </div>
                    `;
                        }
                        infoClienteElement.style.display = 'block';
                    } else {
                        // Si no hay pedido activo, limpiar clienteSeleccionado
                        clienteSeleccionado = null;
                        console.log('⚠️ No hay pedido activo, clienteSeleccionado limpiado');
                    }
                }
            });
        }

        function cerrarModalMenu() {
            document.getElementById('modalMenu').style.display = "none";
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';
        }

        function volverACategorias() {
            // Limpiar el buscador
            const buscador = document.getElementById('buscadorProductos');
            if (buscador) {
                buscador.value = '';
            }

            // Limpiar productos actuales
            productosActuales = [];
            categoriaActual = '';

            // Ocultar productos y mostrar categorías
            document.getElementById('productosContainer').style.display = 'none';
            document.getElementById('categoriasMenu').style.display = 'grid';
            document.getElementById('pedidosContainer').style.display = 'none';
        }

        // ========== FUNCIONES DE PEDIDOS ==========

        function verificarPedidoActivo(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'VerificarPedidoActivo',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    if (data.existe) {
                        pedidoActualId = data.id_pedido;
                        // VERIFICAR SI EL PEDIDO ESTÁ FINALIZADO
                        verificarEstadoPedido(pedidoActualId);
                        cargarDetallesPedido(pedidoActualId);
                        mostrarListaPedidos();

                        // MOSTRAR BOTONES DEPENDIENDO DEL ESTADO
                        if (data.estado === 'finalizada') {
                            // Si el pedido está finalizado, mostrar solo botón para agregar más
                            document.getElementById('btnEnviarAdicionales').style.display = 'block';
                            document.getElementById('btnEnviarOrden').style.display = 'none';
                            document.getElementById('btnAgregarMas').style.display = 'block';

                            // Mostrar mensaje de que el pedido está finalizado
                            Swal.fire({
                                title: 'Pedido Finalizado',
                                html: `El pedido de la <strong>Mesa ${numeroMesaActual}</strong> está marcado como finalizado.<br>Puede agregar productos adicionales si es necesario.`,
                                icon: 'info',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#67C090'
                            });
                        } else {
                            // Si el pedido está activo, mostrar botones normales
                            document.getElementById('btnEnviarAdicionales').style.display = 'block';
                            document.getElementById('btnEnviarOrden').style.display = 'none';
                            document.getElementById('btnAgregarMas').style.display = 'block';
                        }
                    } else {
                        // No hay pedido activo
                        productosPendientesEnvio = [];
                        pedidos = [];
                        pedidoActualId = null;
                        actualizarListaPedidos();
                        document.getElementById('btnEnviarAdicionales').style.display = 'none';
                        document.getElementById('btnEnviarOrden').style.display = 'block';
                        document.getElementById('btnAgregarMas').style.display = 'block';
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // NUEVA FUNCIÓN para verificar el estado del pedido
        function verificarEstadoPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerEstadoPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        // Actualizar la interfaz según el estado
                        actualizarInterfazSegunEstado(data.estado);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al verificar estado:', error);
                }
            });
        }

        // FUNCIÓN para actualizar la interfaz según el estado del pedido
        function actualizarInterfazSegunEstado(estado) {
            const btnEnviarAdicionales = document.getElementById('btnEnviarAdicionales');
            const btnEnviarOrden = document.getElementById('btnEnviarOrden');
            const btnAgregarMas = document.getElementById('btnAgregarMas');

            switch (estado) {
                case 'recibida':
                case 'en_preparacion':
                    // Pedido activo - mostrar botones normales
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';
                    break;

                case 'finalizada':
                    // Pedido finalizado - permitir agregar más productos
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';

                    // Mostrar indicador de pedido finalizado
                    mostrarIndicadorFinalizado();
                    break;

                case 'pagado':
                case 'cancelado':
                    // Pedido cerrado - no permitir modificaciones
                    btnEnviarAdicionales.style.display = 'none';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'none';
                    break;

                default:
                    // Estado desconocido - mostrar botones por defecto
                    btnEnviarAdicionales.style.display = 'block';
                    btnEnviarOrden.style.display = 'none';
                    btnAgregarMas.style.display = 'block';
            }
        }
        function mostrarIndicadorFinalizado() {
            const pedidosContainer = document.getElementById('pedidosContainer');
            const existingIndicator = document.getElementById('indicadorFinalizado');

            if (!existingIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'indicadorFinalizado';
                indicator.className = 'alert alert-warning text-center';
                indicator.innerHTML = `
            <i class="bi bi-check-circle-fill"></i>
            <strong>Pedido Finalizado</strong> - Puede agregar productos adicionales si es necesario
        `;
                pedidosContainer.insertBefore(indicator, pedidosContainer.firstChild);
            }
        }

        function cargarDetallesPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerDetallesPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    // IMPORTANTE: NO cargar los productos existentes en productosPendientesEnvio
                    // Solo mantenerlos en la variable pedidos para referencia
                    pedidos = data.map(pedido => ({
                        ...pedido,
                        precio_unitario: parseFloat(pedido.precio_unitario) || 0,
                        cantidad: parseInt(pedido.cantidad) || 0,
                        subtotal: parseFloat(pedido.subtotal) || 0
                    }));

                    // Limpiar productos pendientes cuando se carga un pedido existente
                    productosPendientesEnvio = [];
                    actualizarListaPedidos();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // ========== FUNCIONES DE PEDIDOS ==========

        function agregarProductoAOrden(producto, cantidad, nota = '') {
            const productoExistente = productosPendientesEnvio.find(p =>
                p.id_platillo === producto.id_platillo && p.nota === nota
            );

            const precioUnitario = parseFloat(producto.precio) || 0;
            const cantidadNum = parseInt(cantidad) || 0;
            const subtotal = precioUnitario * cantidadNum;

            if (productoExistente && nota === productoExistente.nota) {
                // Si es el mismo producto con la misma nota, aumentar cantidad
                productoExistente.cantidad += cantidadNum;
                productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio_unitario;
            } else {
                // Si es producto diferente o con nota diferente, agregar nuevo
                const nuevoPedido = {
                    id_platillo: producto.id_platillo,
                    nombre_platillo: producto.nombre,
                    precio_unitario: precioUnitario,
                    cantidad: cantidadNum,
                    subtotal: subtotal,
                    nota: nota // AGREGAR LA NOTA
                };
                productosPendientesEnvio.push(nuevoPedido);
            }

            actualizarListaPedidos();
            mostrarListaPedidos();

            let mensaje = `<strong>${cantidad} x ${producto.nombre}</strong><br>Agregado a la orden`;
            if (nota) {
                mensaje += `<br><small><i>"${nota}"</i></small>`;
            }

            Swal.fire({
                title: '¡Producto agregado!',
                html: mensaje,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#67C090',
                timer: 2000
            });
        }

        function eliminarProducto(index) {
            // Si la cantidad es mayor a 1, preguntar si quiere eliminar uno o todos
            if (productosPendientesEnvio[index].cantidad > 1) {
                Swal.fire({
                    title: 'Eliminar producto',
                    html: `¿Qué desea hacer con <strong>${productosPendientesEnvio[index].nombre_platillo}</strong>?`,
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Eliminar 1',
                    denyButtonText: 'Eliminar todos',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#67C090',
                    denyButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Eliminar solo una unidad
                        if (productosPendientesEnvio[index].cantidad > 1) {
                            productosPendientesEnvio[index].cantidad -= 1;
                            productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
                        } else {
                            productosPendientesEnvio.splice(index, 1);
                        }
                        actualizarListaPedidos();

                        if (productosPendientesEnvio.length === 0) {
                            ocultarListaPedidos();
                        }
                    } else if (result.isDenied) {
                        // Eliminar todas las unidades
                        productosPendientesEnvio.splice(index, 1);
                        actualizarListaPedidos();

                        if (productosPendientesEnvio.length === 0) {
                            ocultarListaPedidos();
                        }
                    }
                });
            } else {
                // Si solo hay una unidad, eliminar directamente
                productosPendientesEnvio.splice(index, 1);
                actualizarListaPedidos();

                if (productosPendientesEnvio.length === 0) {
                    ocultarListaPedidos();
                }
            }
        }

        function actualizarListaPedidos() {
            const listaPedidos = document.getElementById('listaPedidos');
            const totalPedido = document.getElementById('totalPedido');

            listaPedidos.innerHTML = '';

            let total = 0;

            productosPendientesEnvio.forEach((pedido, index) => {
                const precioUnitario = parseFloat(pedido.precio_unitario) || 0;
                const cantidad = parseInt(pedido.cantidad) || 0;
                const subtotal = parseFloat(pedido.subtotal) || 0;

                total += subtotal;

                const itemPedido = document.createElement('div');
                itemPedido.className = 'item-pedido';

                let notaHTML = '';
                if (pedido.nota) {
                    notaHTML = `<div class="nota-producto" style="font-size: 0.8em; color: #e67e22; font-style: italic; margin-top: 3px;">
                <i class="bi bi-chat-left-text"></i> ${pedido.nota}
            </div>`;
                }

                itemPedido.innerHTML = `
            <div class="info-producto">
                <div class="nombre-producto">${pedido.nombre_platillo}</div>
                <div class="detalles-producto">
                    Cantidad: ${cantidad} | Precio: $${precioUnitario.toFixed(2)} c/u
                </div>
                ${notaHTML}
            </div>
            <div class="acciones-producto">
                <span class="subtotal">$${subtotal.toFixed(2)}</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="disminuirCantidad(${index})" ${cantidad <= 1 ? 'disabled' : ''}>
                        <i class="bi bi-dash"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="aumentarCantidad(${index})">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="editarNota(${index})" title="Editar nota">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="eliminarProducto(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
                listaPedidos.appendChild(itemPedido);
            });

            totalPedido.textContent = `Total: $${total.toFixed(2)}`;
        }

        function editarNota(index) {
            const producto = productosPendientesEnvio[index];

            Swal.fire({
                title: 'Editar nota',
                html: `
            <p><strong>${producto.nombre_platillo}</strong></p>
            <div class="mt-3">
                <label for="editarNotaProducto" class="form-label">Nota especial:</label>
                <textarea id="editarNotaProducto" class="form-control" placeholder="Ej: Sin crema, sin mayonesa, bien cocido, etc." rows="3" maxlength="100">${producto.nota || ''}</textarea>
                <small class="text-muted">Máximo 100 caracteres</small>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Actualizar nota',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#67C090',
                preConfirm: () => {
                    const nota = document.getElementById('editarNotaProducto').value.trim();
                    return { nota };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { nota } = result.value;
                    productosPendientesEnvio[index].nota = nota;
                    actualizarListaPedidos();

                    Swal.fire({
                        title: '¡Nota actualizada!',
                        text: 'La nota se ha actualizado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#67C090',
                        timer: 1500
                    });
                }
            });
        }

        // Nuevas funciones para modificar cantidades
        function aumentarCantidad(index) {
            productosPendientesEnvio[index].cantidad += 1;
            productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
            actualizarListaPedidos();
        }

        function disminuirCantidad(index) {
            if (productosPendientesEnvio[index].cantidad > 1) {
                productosPendientesEnvio[index].cantidad -= 1;
                productosPendientesEnvio[index].subtotal = productosPendientesEnvio[index].cantidad * productosPendientesEnvio[index].precio_unitario;
                actualizarListaPedidos();
            }
        }

        function agregarMasProductos() {
            // NO limpiar productosPendientesEnvio aquí, mantener los que el usuario está agregando
            volverACategorias();
            document.getElementById('pedidosContainer').style.display = 'none';

            // Asegurarse de que las categorías se muestren
            document.getElementById('categoriasMenu').style.display = 'grid';
            document.getElementById('productosContainer').style.display = 'none';
        }

        function mostrarListaPedidos() {
            document.getElementById('pedidosContainer').style.display = 'block';
            document.getElementById('productosContainer').style.display = 'none';
            document.getElementById('categoriasMenu').style.display = 'none';
        }

        function ocultarListaPedidos() {
            document.getElementById('pedidosContainer').style.display = 'none';
        }

        function enviarOrden() {
            if (productosPendientesEnvio.length === 0) {
                Swal.fire({
                    title: 'Orden vacía',
                    text: 'Agregue al menos un producto a la orden',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090'
                });
                return;
            }

            // DEBUG: Verificar qué cliente tenemos
            console.log('=== DEBUG CLIENTE EN ENVIAR ORDEN ===');
            console.log('Cliente seleccionado en enviarOrden:', clienteSeleccionado);
            console.log('ID Mesa:', idMesaActual);
            console.log('Número Mesa:', numeroMesaActual);

            let id_cliente = null;
            let nombre_cliente = 'Cliente Temporal';

            if (clienteSeleccionado) {
                id_cliente = clienteSeleccionado.id_cliente;
                nombre_cliente = clienteSeleccionado.nombre;
                if (clienteSeleccionado.apellidos) {
                    nombre_cliente += ' ' + clienteSeleccionado.apellidos;
                }
                console.log('✅ Usando cliente seleccionado:', nombre_cliente, 'ID:', id_cliente);
            } else {
                console.log('⚠️ No hay cliente seleccionado, usando Cliente Temporal');
            }

            const datos = {
                funcion: pedidoActualId ? 'ActualizarPedido' : 'CrearPedido',
                id_mesa: idMesaActual,
                id_mesero: <?php echo $_SESSION['SISTEMA']['id_usuario'] ?? 1; ?>,
                nombre_mesero: '<?php echo $_SESSION['SISTEMA']['nombre'] ?? 'Mesero'; ?>',
                pedidos: JSON.stringify(productosPendientesEnvio),
                id_pedido: pedidoActualId,
                id_cliente: id_cliente,
                nombre_cliente: nombre_cliente
            };

            console.log('Datos completos a enviar:', datos);

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: datos,
                dataType: 'json',
                success: function (data) {
                    console.log('Respuesta del servidor:', data);
                    if (data.success) {
                        pedidoActualId = data.id_pedido;

                        // LIMPIAR LOS PRODUCTOS PENDIENTES DESPUÉS DE ENVIAR
                        productosPendientesEnvio = [];

                        if (mesaActual) {
                            mesaActual.classList.add('con-pedido');
                            const mesaIndex = mesasData.findIndex(m => m.id_mesa === idMesaActual);
                            if (mesaIndex !== -1) {
                                mesasData[mesaIndex].estado = 'ocupada';
                            }
                            actualizarEstadisticas();
                        }

                        Swal.fire({
                            title: '¡Orden enviada!',
                            html: `Orden de la <strong>Mesa ${numeroMesaActual}</strong> enviada a cocina correctamente<br>
                           <small>Cliente: ${nombre_cliente}</small>`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090'
                        }).then(() => {
                            cerrarModalMenu();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al enviar la orden', 'error');
                }
            });
        }

        function cargarPedidosActivos() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosActivos'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    mostrarPedidosActivos(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        function mostrarPedidosActivos(pedidos) {
            const lista = document.getElementById('listaPedidosActivos');
            lista.innerHTML = '';

            if (pedidos.length === 0) {
                lista.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                <p class="mt-2">No hay pedidos activos en este momento</p>
                <small class="text-muted">Todos los pedidos han sido completados</small>
            </div>
        `;
                return;
            }

            pedidos.forEach(pedido => {
                const card = document.createElement('div');
                card.className = 'pedidos-container mb-3';
                card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h5 class="mb-2">🍽️ Mesa ${pedido.numero_mesa}</h5>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div>
                            <i class="bi bi-person"></i>
                            <strong>Mesero:</strong> ${pedido.nombre_mesero}
                        </div>
                        <div>
                            <i class="bi bi-clock"></i>
                            <strong>Hora:</strong> ${new Date(pedido.fecha_creacion).toLocaleTimeString()}
                        </div>
                        <div>
                            <i class="bi bi-cash-coin"></i>
                            <strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="estado-pedido estado-${pedido.estado}">
                            ${pedido.estado.replace('_', ' ').toUpperCase()}
                        </span>
                        <small class="ms-2 text-muted">
                            ${new Date(pedido.fecha_creacion).toLocaleDateString()}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetallesPedido(${pedido.id_pedido})">
                        <i class="bi bi-eye"></i> Ver
                    </button>
                </div>
            </div>
        `;
                lista.appendChild(card);
            });
        }

        function verDetallesPedido(idPedido) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerDetallesPedido',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    let detallesHTML = '<div class="mt-3"><h6>📋 Productos del Pedido:</h6>';

                    if (data.length === 0) {
                        detallesHTML += '<p class="text-muted">No hay productos en este pedido</p>';
                    } else {
                        detallesHTML += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        detallesHTML += '<thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';

                        data.forEach(detalle => {
                            detallesHTML += `
                        <tr>
                            <td>${detalle.nombre_platillo}</td>
                            <td class="text-center">${detalle.cantidad}</td>
                            <td class="text-end">$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(detalle.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                        });

                        const total = data.reduce((sum, detalle) => sum + parseFloat(detalle.subtotal), 0);
                        detallesHTML += `
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong>$${total.toFixed(2)}</strong></td>
                        </tr>
                    </tfoot>
                </table></div>`;
                    }

                    detallesHTML += '</div>';

                    Swal.fire({
                        title: 'Detalles del Pedido',
                        html: detallesHTML,
                        width: 800,
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#67C090'
                    });
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar los detalles del pedido', 'error');
                }
            });
        }

        // ========== FUNCIONES DE CUENTA Y LIBERACIÓN ==========

        function cargarPedidosFinalizados() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerPedidosFinalizados'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    mostrarPedidosFinalizados(data);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        function mostrarPedidosFinalizados(pedidos) {
            const lista = document.getElementById('listaPedidosActivos');
            lista.innerHTML = '';

            if (pedidos.length === 0) {
                lista.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-check-circle" style="font-size: 3em; color: #28a745;"></i>
                <p class="mt-2">No hay pedidos finalizados pendientes de pago</p>
                <small class="text-muted">Todos los pedidos han sido pagados</small>
            </div>
        `;
                return;
            }

            pedidos.forEach(pedido => {
                const card = document.createElement('div');
                card.className = 'pedidos-container mb-3';
                card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h5 class="mb-2">🍽️ Mesa ${pedido.numero_mesa}</h5>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div>
                            <i class="bi bi-person"></i>
                            <strong>Mesero:</strong> ${pedido.nombre_mesero}
                        </div>
                        <div>
                            <i class="bi bi-clock"></i>
                            <strong>Finalizado:</strong> ${new Date(pedido.fecha_actualizacion).toLocaleTimeString()}
                        </div>
                        <div>
                            <i class="bi bi-cash-coin"></i>
                            <strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="estado-pedido estado-${pedido.estado}">
                            ${pedido.estado.replace('_', ' ').toUpperCase()}
                        </span>
                        <small class="ms-2 text-muted">
                            ${new Date(pedido.fecha_actualizacion).toLocaleDateString()}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                        <button class="btn btn-sm btn-success mb-1" onclick="generarCuenta(${pedido.id_pedido}, ${pedido.numero_mesa})">
                            <i class="bi bi-cash"></i> Cobrar
                </button>
                    <br>
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetallesPedido(${pedido.id_pedido})">
                        <i class="bi bi-eye"></i> Ver
                    </button>
                </div>
            </div>
        `;
                lista.appendChild(card);
            });
        }

        function mostrarTicketYCobrar(idPedido, numeroMesa) {
            generarCuenta(idPedido, numeroMesa); // Esta función ahora muestra el ticket primero
        }

        function generarCuenta(idPedido, numeroMesa) {
            // Primero obtener los datos para el ticket
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerDatosTicket',
                    id_pedido: idPedido
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        mostrarTicket(data, idPedido, numeroMesa);
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al obtener datos del ticket', 'error');
                }
            });
        }

        function mostrarTicket(data, idPedido, numeroMesa) {
            const pedido = data.pedido;
            const detalles = data.detalles;

            // Formatear fecha correctamente
            const fecha = new Date(pedido.fecha_creacion);
            const fechaFormateada = fecha.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            const horaFormateada = fecha.toLocaleTimeString('es-MX', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // CALCULAR EL TOTAL CORRECTAMENTE - SUMAR TODOS LOS PRODUCTOS
            let total = 0;
            detalles.forEach(detalle => {
                const subtotalItem = parseFloat(detalle.subtotal) || 0;
                total += subtotalItem;
            });

            // También usar el total de la base de datos como respaldo
            const totalBD = parseFloat(pedido.total) || 0;
            if (totalBD > total) {
                total = totalBD; // Usar el mayor de los dos cálculos
            }

            // Crear HTML del ticket
            const ticketId = 'ticket-' + Date.now();
            let ticketHTML = `
        <div id="${ticketId}" class="ticket-container" style="font-family: 'Courier New', monospace; max-width: 300px; margin: 0 auto; background: white; padding: 20px; border: 2px solid #000;">
            <div class="ticket-header text-center mb-3">
                <h4 style="margin: 0; font-weight: bold; font-size: 18px;">🍕 PIZZERÍA DEL CENTRO</h4>
                <p style="margin: 2px 0; font-size: 12px;">Av. Principal #123, Ciudad</p>
                <p style="margin: 2px 0; font-size: 12px;">Tel: (555) 123-4567</p>
                <p style="margin: 2px 0; font-size: 12px;">RFC: PIZ123456789</p>
                <hr style="margin: 8px 0; border-top: 2px dashed #000;">
            </div>
            
            <div class="ticket-info mb-3" style="font-size: 12px;">
                <div class="row">
                    <div class="col-6">
                        <strong>TICKET:</strong> #${pedido.id_pedido || 'N/A'}
                    </div>
                    <div class="col-6 text-end">
                        <strong>MESA:</strong> ${pedido.numero_mesa || numeroMesa || 'N/A'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <strong>FECHA:</strong> ${fechaFormateada}
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <strong>HORA:</strong> ${horaFormateada}
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <strong>MESERO:</strong> ${pedido.nombre_mesero || 'No especificado'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <strong>CLIENTE:</strong> ${pedido.nombre_cliente || 'Cliente Temporal'}
                    </div>
                </div>
                ${pedido.ubicacion ? `<div class="row">
                    <div class="col-12">
                        <strong>UBICACIÓN:</strong> ${pedido.ubicacion}
                    </div>
                </div>` : ''}
            </div>
            
            <hr style="margin: 8px 0; border-top: 1px solid #000;">
            
            <div class="ticket-items mb-3">
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px dashed #000;">
                            <th style="text-align: left; width: 45%; padding: 2px 0;">PRODUCTO</th>
                            <th style="text-align: center; width: 15%; padding: 2px 0;">CANT</th>
                            <th style="text-align: right; width: 20%; padding: 2px 0;">PRECIO</th>
                            <th style="text-align: right; width: 20%; padding: 2px 0;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

            // Agregar items y calcular total nuevamente
            let totalCalculado = 0;
            detalles.forEach(detalle => {
                const precio = parseFloat(detalle.precio_unitario) || 0;
                const subtotalItem = parseFloat(detalle.subtotal) || 0;
                totalCalculado += subtotalItem;

                ticketHTML += `
            <tr style="border-bottom: 1px dashed #ccc;">
                <td style="text-align: left; padding: 3px 0;">${detalle.nombre_platillo || 'Producto'}</td>
                <td style="text-align: center; padding: 3px 0;">${detalle.cantidad || 0}</td>
                <td style="text-align: right; padding: 3px 0;">$${precio.toFixed(2)}</td>
                <td style="text-align: right; padding: 3px 0;">$${subtotalItem.toFixed(2)}</td>
            </tr>
        `;
            });

            // Usar el total calculado si es mayor que el de la BD
            if (totalCalculado > total) {
                total = totalCalculado;
            }

            ticketHTML += `
                    </tbody>
                </table>
            </div>
            
            <hr style="margin: 8px 0; border-top: 1px solid #000;">
            
            <div class="ticket-totals" style="font-size: 12px;">
                <div class="row" style="font-size: 14px; font-weight: bold; border-top: 2px solid #000; padding-top: 8px; margin-top: 5px;">
                    <div class="col-8 text-end"><strong>TOTAL:</strong></div>
                    <div class="col-4 text-end">$${total.toFixed(2)}</div>
                </div>
                <div class="row" style="font-size: 10px; color: #666; margin-top: 3px;">
                    <div class="col-12 text-center">
                        <em>IVA INCLUIDO</em>
                    </div>
                </div>
            </div>
            
            <hr style="margin: 15px 0; border-top: 2px dashed #000;">
            
            <div class="ticket-footer text-center" style="font-size: 11px;">
                <p style="margin: 5px 0;">
                    <strong>¡GRACIAS POR SU PREFERENCIA!</strong>
                </p>
                <p style="margin: 5px 0;">
                    Este ticket es su comprobante de pago
                </p>
                <p style="margin: 5px 0;">
                    *** ${fechaFormateada} ${horaFormateada} ***
                </p>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <button onclick="imprimirTicket('${ticketId}')" class="btn btn-primary btn-sm ms-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    `;

            // Mostrar el ticket en SweetAlert2
            Swal.fire({
                title: 'TICKET DE VENTA',
                html: ticketHTML,
                width: 400,
                showCancelButton: true,
                confirmButtonText: '✅ Confirmar Pago',
                cancelButtonText: '✖️ Cancelar',
                confirmButtonColor: '#28a745',
                customClass: {
                    popup: 'ticket-popup'
                },
                didOpen: () => {
                    // Agregar estilos adicionales para el ticket
                    const popup = Swal.getPopup();
                    popup.style.fontFamily = 'Courier New, monospace';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceder con el pago
                    confirmarPago(idPedido, numeroMesa);
                }
            });
        }

        function confirmarPago(idPedido, numeroMesa) {
            const mesa = mesasData.find(m => m.numero_mesa == numeroMesa);
            if (!mesa) {
                Swal.fire('Error', 'No se encontró la mesa', 'error');
                return;
            }

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'GenerarCuenta',
                    id_pedido: idPedido,
                    id_mesa: mesa.id_mesa
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        const mesaElement = document.querySelector(`.mesa[data-numero="${numeroMesa}"]`);
                        if (mesaElement) {
                            mesaElement.classList.remove('ocupada', 'con-pedido');
                        }

                        const mesaIndex = mesasData.findIndex(m => m.numero_mesa == numeroMesa);
                        if (mesaIndex !== -1) {
                            mesasData[mesaIndex].estado = 'disponible';
                        }

                        actualizarEstadisticas();

                        Swal.fire({
                            title: '¡Pago Confirmado!',
                            html: `Cuenta de la <strong>Mesa ${numeroMesa}</strong> pagada correctamente.<br>La mesa ha sido liberada.`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090'
                        }).then(() => {
                            cargarPedidosFinalizados();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al procesar el pago', 'error');
                }
            });
        }

        // Función para imprimir el ticket
        function imprimirTicket(ticketId) {
            const ticketElement = document.getElementById(ticketId);
            const ventanaImpresion = window.open('', '_blank');

            ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket #${ticketId}</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    margin: 0; 
                    padding: 10px;
                    background: white;
                }
                @media print {
                    body { margin: 0; }
                    .ticket-container { 
                        border: none !important; 
                        box-shadow: none !important;
                    }
                }
                .ticket-container {
                    max-width: 300px;
                    margin: 0 auto;
                    border: 2px solid #000;
                    padding: 15px;
                }
            </style>
        </head>
        <body>
            ${ticketElement.outerHTML}
        </body>
        </html>
    `);

            ventanaImpresion.document.close();

            // Esperar a que cargue el contenido y luego imprimir
            ventanaImpresion.onload = function () {
                ventanaImpresion.print();
                // Cerrar la ventana después de imprimir
                setTimeout(() => {
                    ventanaImpresion.close();
                }, 500);
            };
        }

        function limpiarPedidosAnteriores(idMesa) {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'LimpiarPedidosAnteriores',
                    id_mesa: idMesa
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        console.log('Pedidos anteriores limpiados para mesa:', idMesa);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al limpiar pedidos anteriores:', error);
                }
            });
        }

        function enviarProductosAdicionales() {
            if (productosPendientesEnvio.length === 0) {
                Swal.fire({
                    title: 'No hay productos',
                    text: 'Agregue al menos un producto a la orden',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090'
                });
                return;
            }

            // IMPORTANTE: No enviar datos de cliente para productos adicionales
            // El servidor mantendrá el cliente original del pedido
            console.log('=== DEBUG ENVIAR ADICIONALES ===');
            console.log('NO se envían datos de cliente - se mantiene el original');

            const datos = {
                funcion: 'AgregarProductosPedido',
                id_pedido: pedidoActualId,
                pedidos: JSON.stringify(productosPendientesEnvio)
                // ELIMINAR: id_cliente y nombre_cliente
            };

            console.log('Datos adicionales a enviar (sin cliente):', datos);

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: datos,
                dataType: 'json',
                success: function (data) {
                    console.log('Respuesta del servidor (adicionales):', data);
                    if (data.success) {
                        // LIMPIAR LOS PRODUCTOS PENDIENTES DESPUÉS DE ENVIAR
                        productosPendientesEnvio = [];
                        actualizarListaPedidos();

                        // Mostrar el nombre del cliente que se mantuvo
                        const nombreClienteMostrar = data.nombre_cliente || 'Cliente Temporal';

                        Swal.fire({
                            title: '¡Productos agregados!',
                            html: `Productos agregados al pedido de la <strong>Mesa ${numeroMesaActual}</strong> correctamente<br>
                           <small>Cliente: ${nombreClienteMostrar}</small>`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#67C090'
                        }).then(() => {
                            cerrarModalMenu();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'Error al agregar productos', 'error');
                }
            });
        }

        // ========== FUNCIONES DE CARGA DE DATOS ==========

        function cargarMesasDesdeBD() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerMesas'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    mesasData = data;
                    mostrarMesasEnCroquis();
                    actualizarEstadisticas();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar las mesas: ' + error, 'error');
                }
            });
        }

        function mostrarMesasEnCroquis() {
            const croquis = document.getElementById("croquis");
            croquis.innerHTML = '';

            // Agrupar mesas por ubicación - SOLO TUS 3 ZONAS
            const mesasPorUbicacion = {
                'Zona entrada': [],
                'Zona barra': [],
                'Zona baños': []
            };

            // Organizar mesas por ubicación
            mesasData.forEach(mesa => {
                if (mesasPorUbicacion[mesa.ubicacion]) {
                    mesasPorUbicacion[mesa.ubicacion].push(mesa);
                } else {
                    // Si la ubicación no existe, ponerla en "Zona entrada" por defecto
                    mesasPorUbicacion['Zona entrada'].push(mesa);
                }
            });

            // Ordenar cada grupo por número de mesa
            Object.keys(mesasPorUbicacion).forEach(ubicacion => {
                mesasPorUbicacion[ubicacion].sort((a, b) => a.numero_mesa - b.numero_mesa);
            });

            // Crear el layout del croquis con secciones
            crearCroquisConSecciones(croquis, mesasPorUbicacion);
        }

        function crearCroquisConSecciones(croquis, mesasPorUbicacion) {
            // Configurar el grid del croquis
            croquis.style.display = 'grid';
            croquis.style.gap = '20px';
            croquis.style.padding = '20px';

            // Definir el orden de las secciones - SOLO TUS 3 ZONAS
            const ordenSecciones = ['Zona entrada', 'Zona barra', 'Zona baños'];

            ordenSecciones.forEach(ubicacion => {
                const mesasEnUbicacion = mesasPorUbicacion[ubicacion];
                if (mesasEnUbicacion.length > 0) {
                    // Crear contenedor de sección
                    const seccion = document.createElement('div');
                    seccion.className = 'seccion-mesas';
                    seccion.innerHTML = `
                <div class="etiqueta-seccion ${ubicacion.toLowerCase().replace(' ', '-')}">
                    <h4>${ubicacion}</h4>
                    <small>${mesasEnUbicacion.length} mesa${mesasEnUbicacion.length !== 1 ? 's' : ''}</small>
                </div>
                <div class="mesas-container" id="mesas-${ubicacion.toLowerCase().replace(' ', '-')}"></div>
            `;

                    // Estilos de la sección
                    seccion.style.border = '2px solid #e0e0e0';
                    seccion.style.borderRadius = '10px';
                    seccion.style.padding = '15px';
                    seccion.style.background = 'rgba(248, 249, 250, 0.5)';

                    croquis.appendChild(seccion);

                    // Crear contenedor de mesas para esta sección
                    const mesasContainer = seccion.querySelector('.mesas-container');
                    mesasContainer.style.display = 'grid';
                    mesasContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(80px, 1fr))';
                    mesasContainer.style.gap = '15px';
                    mesasContainer.style.marginTop = '10px';

                    // Agregar mesas a la sección
                    mesasEnUbicacion.forEach(mesa => {
                        crearElementoMesa(mesa, mesasContainer);
                    });
                }
            });
        }

        function crearElementoMesa(mesa, contenedor) {
            const mesaElement = document.createElement("div");
            mesaElement.classList.add("mesa");
            mesaElement.setAttribute('data-numero', mesa.numero_mesa);
            mesaElement.setAttribute('data-ubicacion', mesa.ubicacion);

            // Aplicar estilos según capacidad
            if (mesa.capacidad <= 2) {
                mesaElement.style.width = "60px";
                mesaElement.style.height = "60px";
                mesaElement.style.fontSize = "0.9em";
            } else if (mesa.capacidad <= 4) {
                mesaElement.style.width = "70px";
                mesaElement.style.height = "60px";
                mesaElement.style.fontSize = "1em";
            } else if (mesa.capacidad <= 6) {
                mesaElement.style.width = "75px";
                mesaElement.style.height = "60px";
                mesaElement.style.fontSize = "1.1em";
            } else if (mesa.capacidad <= 8) {
                mesaElement.style.width = "90px";
                mesaElement.style.height = "60px";
                mesaElement.style.fontSize = "1.2em";
            } else {
                mesaElement.style.width = "110px";
                mesaElement.style.height = "60px";
                mesaElement.style.fontSize = "1.3em";
            }

            // Aplicar estado
            if (mesa.estado === 'ocupada' || mesa.estado === 'con-pedido') {
                mesaElement.classList.add("ocupada");
            }

            // Agregar información de capacidad
            const infoCapacidad = document.createElement('div');
            infoCapacidad.className = 'info-capacidad';
            infoCapacidad.textContent = `${mesa.capacidad}p`;
            infoCapacidad.style.position = 'absolute';
            infoCapacidad.style.bottom = '2px';
            infoCapacidad.style.right = '2px';
            infoCapacidad.style.background = 'rgba(0,0,0,0.7)';
            infoCapacidad.style.color = 'white';
            infoCapacidad.style.borderRadius = '3px';
            infoCapacidad.style.padding = '1px 4px';
            infoCapacidad.style.fontSize = '0.7em';

            mesaElement.appendChild(infoCapacidad);
            mesaElement.textContent = mesa.numero_mesa;

            // Event listener para la mesa
            mesaElement.addEventListener("click", () => {
                if (mesa.estado === 'disponible') {
                    abrirModalConfirmacion(mesa.id_mesa, mesa.numero_mesa, mesaElement);
                } else {
                    abrirModalMenu(mesa.id_mesa, mesa.numero_mesa, mesaElement);
                }
            });

            contenedor.appendChild(mesaElement);
        }

        function cargarCategoriasDesdeBD() {
            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerCategoriasMenu'
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    categoriasData = data;
                    mostrarCategorias();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar las categorías: ' + error, 'error');
                }
            });
        }

        function mostrarCategorias() {
            const categoriasMenu = document.getElementById('categoriasMenu');
            categoriasMenu.innerHTML = '';
            document.getElementById('productosContainer').style.display = 'none';

            categoriasData.forEach(categoria => {
                const icono = iconosCategorias[categoria.nombre] || '📁';
                const card = document.createElement("div");
                card.classList.add("categoria-card");
                card.innerHTML = `
            <div class="categoria-icon">${icono}</div>
            <div class="categoria-nombre">${categoria.nombre}</div>
            <div class="categoria-descripcion">${categoria.descripcion || 'Sin descripción'}</div>
        `;
                card.addEventListener('click', () => {
                    cargarProductosPorCategoria(categoria.id_categoria, categoria.nombre);
                });
                categoriasMenu.appendChild(card);
            });
        }

        function cargarProductosPorCategoria(idCategoria, nombreCategoria) {
            // Limpiar búsqueda anterior
            const buscador = document.getElementById('buscadorProductos');
            if (buscador) {
                buscador.value = '';
            }

            $.ajax({
                url: 'funciones/croquis.php',
                type: 'POST',
                data: {
                    funcion: 'ObtenerProductosPorCategoria',
                    id_categoria: idCategoria
                },
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    mostrarProductos(data, nombreCategoria);
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar los productos', 'error');
                }
            });
        }

        // ========== FUNCIONES DEL BUSCADOR DE PRODUCTOS ==========

        function mostrarProductos(productos, nombreCategoria) {
            // Ocultar categorías y mostrar productos
            document.getElementById('categoriasMenu').style.display = 'none';
            document.getElementById('productosContainer').style.display = 'block';

            // Actualizar título
            document.getElementById('nombreCategoria').textContent = nombreCategoria;

            // Guardar productos actuales para el buscador
            productosActuales = productos;
            categoriaActual = nombreCategoria;

            const productosGrid = document.getElementById('productosGrid');

            if (productos.length === 0) {
                productosGrid.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No hay productos en esta categoría</p>
                    </div>
                `;
                // Ocultar buscador si no hay productos
                document.querySelector('.buscador-productos').style.display = 'none';
            } else {
                // Mostrar buscador
                document.querySelector('.buscador-productos').style.display = 'block';

                // Actualizar placeholder del buscador
                document.getElementById('buscadorProductos').placeholder = `Buscar en ${nombreCategoria}...`;

                // Mostrar todos los productos inicialmente
                renderizarProductos(productos);

                // Actualizar contador
                actualizarContadorProductos(productos.length, productos.length);

                // Enfocar el buscador automáticamente
                setTimeout(() => {
                    document.getElementById('buscadorProductos').focus();
                }, 300);
            }
        }

        function renderizarProductos(productos, terminoBusqueda = '') {
            const productosGrid = document.getElementById('productosGrid');
            productosGrid.innerHTML = '';

            if (productos.length === 0) {
                const mensaje = terminoBusqueda ?
                    `No se encontraron productos que coincidan con "${terminoBusqueda}"` :
                    'No hay productos en esta categoría';
                productosGrid.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <i class="bi bi-search" style="font-size: 3em; color: #ccc;"></i>
                        <p class="mt-2 text-muted">${mensaje}</p>
                        <small>Intente con otros términos de búsqueda</small>
                    </div>
                `;
                return;
            }

            productos.forEach(producto => {
                const card = document.createElement("div");
                card.classList.add("producto-card");

                // Resaltar término de búsqueda si existe
                let nombreProducto = producto.nombre;
                let descripcionProducto = producto.descripcion || 'Sin descripción';

                if (terminoBusqueda) {
                    const regex = new RegExp(`(${terminoBusqueda})`, 'gi');
                    nombreProducto = nombreProducto.replace(regex, '<mark>$1</mark>');
                    descripcionProducto = descripcionProducto.replace(regex, '<mark>$1</mark>');
                }

                card.innerHTML = `
                    <div class="producto-nombre">${nombreProducto}</div>
                    <div class="producto-descripcion">${descripcionProducto}</div>
                    <div class="producto-precio">$${parseFloat(producto.precio).toFixed(2)}</div>
                `;

                card.addEventListener('click', () => {
                    seleccionarProducto(producto);
                });

                productosGrid.appendChild(card);
            });
        }

        function filtrarProductos(termino) {
            const productosGrid = document.getElementById('productosGrid');
            const contador = document.getElementById('contadorProductos');

            if (!termino.trim()) {
                // Si no hay término de búsqueda, mostrar todos los productos
                renderizarProductos(productosActuales);
                actualizarContadorProductos(productosActuales.length, productosActuales.length);
                return;
            }

            const terminoLower = termino.toLowerCase().trim();
            const productosFiltrados = productosActuales.filter(producto =>
                producto.nombre.toLowerCase().includes(terminoLower) ||
                (producto.descripcion && producto.descripcion.toLowerCase().includes(terminoLower))
            );

            renderizarProductos(productosFiltrados, termino);
            actualizarContadorProductos(productosFiltrados.length, productosActuales.length, termino);
        }

        function actualizarContadorProductos(mostrados, total, termino = '') {
            const contador = document.getElementById('contadorProductos');
            if (!contador) return;

            if (termino) {
                contador.innerHTML = `Mostrando <strong>${mostrados}</strong> de <strong>${total}</strong> productos para "<strong>${termino}</strong>"`;
            } else {
                contador.innerHTML = `Mostrando <strong>${mostrados}</strong> de <strong>${total}</strong> productos`;
            }
        }

        function seleccionarProducto(producto) {
            Swal.fire({
                title: producto.nombre,
                html: `
            <p><strong>Descripción:</strong> ${producto.descripcion || 'Sin descripción'}</p>
            <p><strong>Precio:</strong> $${parseFloat(producto.precio).toFixed(2)}</p>
            <div class="mt-3">
                <label for="cantidadProducto" class="form-label">Cantidad:</label>
                <input type="number" id="cantidadProducto" class="form-control" value="1" min="1" max="20">
            </div>
            <div class="mt-3">
                <label for="notaProducto" class="form-label">Nota especial (opcional):</label>
                <textarea id="notaProducto" class="form-control" placeholder="Ej: Sin crema, sin mayonesa, bien cocido, etc." rows="2" maxlength="100"></textarea>
                <small class="text-muted">Máximo 100 caracteres</small>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Agregar a la orden',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#67C090',
                preConfirm: () => {
                    const cantidad = document.getElementById('cantidadProducto').value;
                    if (!cantidad || cantidad < 1 || cantidad > 20) {
                        Swal.showValidationMessage('La cantidad debe ser entre 1 y 20');
                        return false;
                    }
                    const nota = document.getElementById('notaProducto').value.trim();
                    return { cantidad, nota };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { cantidad, nota } = result.value;
                    agregarProductoAOrden(producto, cantidad, nota);
                }
            });
        }

        // ========== INICIALIZACIÓN DEL BUSCADOR ==========

        function inicializarBuscadorProductos() {
            // Event listener para el buscador
            document.getElementById('buscadorProductos').addEventListener('input', function (e) {
                filtrarProductos(e.target.value);
            });

            // Event listener para limpiar búsqueda
            document.getElementById('btnLimpiarBusquedaProductos').addEventListener('click', function () {
                document.getElementById('buscadorProductos').value = '';
                filtrarProductos('');
                document.getElementById('buscadorProductos').focus();
            });

            // Buscar con Enter
            document.getElementById('buscadorProductos').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    filtrarProductos(this.value);
                }
            });
        }

        // ========== INICIALIZACIÓN ==========
        $(document).ready(function () {
            cargarMesasDesdeBD();

            // Inicializar el buscador de productos
            inicializarBuscadorProductos();

            document.getElementById('btnConfirmarAtencion').addEventListener('click', confirmarAtencion);
            document.getElementById('btnCancelarConfirmacion').addEventListener('click', cerrarModalConfirmacion);
            document.getElementById('btnCerrarMenu').addEventListener('click', cerrarModalMenu);
            document.getElementById('btnVolverCategorias').addEventListener('click', volverACategorias);
            document.getElementById('btnEnviarOrden').addEventListener('click', enviarOrden);
            document.getElementById('btnAgregarMas').addEventListener('click', agregarMasProductos);
            document.getElementById('btnEnviarAdicionales').addEventListener('click', enviarProductosAdicionales);
            document.getElementById('btnCerrarPedidos').addEventListener('click', () => {
                document.getElementById('modalPedidos').style.display = 'none';
            });
            document.getElementById('ver-meseros-mesas').addEventListener('click', () => {
                cargarMeserosPorMesa();
                document.getElementById('modalMeserosMesas').style.display = 'flex';
            });

            document.getElementById('btnCerrarMeserosMesas').addEventListener('click', () => {
                document.getElementById('modalMeserosMesas').style.display = 'none';
            });

            // Event listener para la búsqueda
            document.getElementById('buscarMesero').addEventListener('input', function (e) {
                filtrarMesasPorMesero(e.target.value);
            });

            document.getElementById('btnLimpiarBusqueda').addEventListener('click', function () {
                document.getElementById('buscarMesero').value = '';
                filtrarMesasPorMesero('');
            });

            document.getElementById('buscarCliente').addEventListener('input', function (e) {
                buscarClientes(e.target.value);
            });

            document.getElementById('btnConfirmarCliente').addEventListener('click', confirmarCliente);
            document.getElementById('btnCancelarCliente').addEventListener('click', cerrarModalCliente);

            document.getElementById('clienteTemporal').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    confirmarCliente();
                }
            });

            document.getElementById('ver-pedidos').addEventListener('click', () => {
                cargarPedidosFinalizados();
                document.getElementById('modalPedidos').style.display = 'flex';
            });

            $('#ver-estadisticas').click(function () {
                const totalMesas = mesasData.length;
                const ocupadas = mesasData.filter(mesa =>
                    mesa.estado === 'ocupada' || mesa.estado === 'con-pedido'
                ).length;
                const porcentaje = totalMesas > 0 ? Math.round((ocupadas / totalMesas) * 100) : 0;

                // Calcular estadísticas por zona - SOLO TUS 3 ZONAS
                let statsZonasHTML = '';
                const zonas = {};

                mesasData.forEach(mesa => {
                    if (!zonas[mesa.ubicacion]) {
                        zonas[mesa.ubicacion] = { total: 0, ocupadas: 0 };
                    }
                    zonas[mesa.ubicacion].total++;
                    if (mesa.estado === 'ocupada' || mesa.estado === 'con-pedido') {
                        zonas[mesa.ubicacion].ocupadas++;
                    }
                });

                // Mostrar solo las 3 zonas específicas
                const zonasEspecificas = ['Zona entrada', 'Zona barra', 'Zona baños'];

                zonasEspecificas.forEach(zona => {
                    if (zonas[zona]) {
                        const stats = zonas[zona];
                        const porcentajeZona = stats.total > 0 ? Math.round((stats.ocupadas / stats.total) * 100) : 0;
                        statsZonasHTML += `
                <div class="mb-2 p-2" style="background: rgba(0,0,0,0.05); border-radius: 5px;">
                    <strong>${zona}:</strong> ${stats.ocupadas}/${stats.total} mesas ocupadas (${porcentajeZona}%)
                </div>
            `;
                    }
                });

                Swal.fire({
                    title: 'Estadísticas de Mesas',
                    html: `<div style="text-align: left; margin-top: 15px;">
            <p><strong>Mesas totales:</strong> ${totalMesas}</p>
            <p><strong>Mesas ocupadas:</strong> ${ocupadas}</p>
            <p><strong>Mesas disponibles:</strong> ${totalMesas - ocupadas}</p>
            <p><strong>Porcentaje de ocupación:</strong> ${porcentaje}%</p>
            <div class="mt-3">
                <strong>Estadísticas por Zona:</strong>
                ${statsZonasHTML}
            </div>
        </div>`,
                    icon: 'info',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#67C090',
                    width: 500
                });
            });
        });

    </script>
</body>

</html>