<!DOCTYPE html>
<html lang="es">

<head>
    <style>
        :root {
            --primary-color: #67C090;
            --secondary-color: #DDF4E7;
            --danger-color: #124170;
            --light-color: #26667F;
            --sidebar-width: 220px;
            /* Más pequeño */
            --sidebar-collapsed: 60px;
            /* Más compacto */
        }

        /* Estilos para el sidebar compacto */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--danger-color);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-header {
            padding: 15px 10px;
            /* Más compacto */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.1rem;
            /* Más pequeño */
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
            /* Más compacto */
        }

        .nav-item {
            margin-bottom: 2px;
            /* Más compacto */
        }

        .nav-link {
            color: white !important;
            padding: 10px 15px;
            /* Más compacto */
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
            /* Texto más pequeño */
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
            /* Iconos más pequeños */
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
            /* Más compacto */
        }

        .dropdown-item {
            color: white !important;
            padding: 8px 12px;
            /* Más compacto */
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            /* Texto más pequeño */
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

        /* Botón toggle más pequeño */
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

        /* Contenido principal con más espacio */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background-color: var(--secondary-color);
            padding: 15px;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed);
        }

        /* Responsive para móviles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
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
                width: var(--sidebar-width);
            }
        }

        /* Scrollbar personalizado más delgado */
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

        /* Estilo para el botón de toggle manual en desktop */
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
    </style>
</head>

<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar -->
    <!-- Sidebar -->
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="croquis.php" class="sidebar-brand">
                <i class="bi bi-house-door-fill"></i>
                <span></span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="croquis.php">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Croquis</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="clientes.php">
                        <i class="bi bi-people-fill"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="mesas.php">
                        <i class="bi bi-people-fill"></i>
                        <span>Mesas</span>
                    </a>
                </li>

                <!-- MENÚ DE PRODUCTOS -->
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Productos</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="menu_platillos.php">
                                <i class="bi bi-circle"></i>
                                <span>Menu/Platillos/Bebidas</span>
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="categorias.php">
                                <i class="bi bi-tags"></i>
                                <span>Categorías</span>
                            </a></li>
                        <li><a class="dropdown-item" href="unidades_medida.php">
                                <i class="bi bi-rulers"></i>
                                <span>Unidades de Medida</span>
                            </a></li>
                    </ul>
                </li>

                <!-- COCINA Y BARRA (ACTIVO) -->
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold active dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i>
                        <span>Cocina y barra</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="cocina.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Cocina</span>
                            </a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i>
                        <span>Ventas</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="estadisticas.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Estadisticas de Ventas</span>
                            </a></li>
                        <li><a class="dropdown-item" href="historial_ventas.php">
                                <i class="bi bi-receipt"></i>
                                <span>Historial de Ventas</span>
                            </a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav mt-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span>Mi Cuenta</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="usuarios.php">
                                <i class="bi bi-people"></i>
                                <span>Usuarios</span>
                            </a></li>
                        <li><a class="dropdown-item" href="roles.php">
                                <i class="bi bi-shield-lock"></i>
                                <span>Roles</span>
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Cerrar sesión</span>
                            </a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
    <!-- Contenido principal -->
    <div class="main-content" id="mainContent">
        <!-- El contenido de cada página se insertará aquí -->
        <?php
        // Detectar la página actual y mostrar contenido dinámicamente
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page != 'menu.php') {
            // El contenido se carga desde la página específica
        }
        ?>
    </div>

    <script>
        // Toggle sidebar en desktop
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const collapseIcon = document.getElementById('collapseIcon');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            // Cambiar icono
            if (sidebar.classList.contains('collapsed')) {
                collapseIcon.className = 'bi bi-chevron-right';
            } else {
                collapseIcon.className = 'bi bi-chevron-left';
            }
        }

        // Toggle sidebar en móvil
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            sidebar.classList.toggle('mobile-open');
            mainContent.classList.toggle('mobile-expanded');
        }

        // Cerrar sidebar en móvil al hacer clic fuera
        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth <= 992) {
                sidebar.classList.remove('mobile-open');
                mainContent.classList.remove('mobile-expanded');
            }
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            // Event listeners
            sidebarToggle.addEventListener('click', toggleMobileSidebar);

            // Cerrar sidebar al hacer clic en el contenido principal en móvil
            mainContent.addEventListener('click', closeMobileSidebar);

            // Ajustar según el tamaño de pantalla
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

            // Activar elemento del menú según la página actual
            function setActiveMenu() {
                const currentPage = window.location.pathname.split('/').pop();
                const navLinks = document.querySelectorAll('.nav-link');

                navLinks.forEach(link => {
                    const linkHref = link.getAttribute('href');
                    if (linkHref === currentPage) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }

            setActiveMenu();

            // Auto-colapsar en móvil
            if (window.innerWidth <= 992) {
                closeMobileSidebar();
            }
        });

        function confirmLogout() {
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "¿Estás seguro de que quieres salir del sistema?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>

</html>