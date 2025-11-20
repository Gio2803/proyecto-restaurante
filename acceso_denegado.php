<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4>Acceso Denegado</h4>
                    </div>
                    <div class="card-body">
                        <p>No tienes permisos para acceder a esta página.</p>
                        <a href="croquis.php" class="btn btn-primary">Volver al Inicio</a>
                        <a href="logout.php" class="btn btn-secondary">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>