<?php
session_start(); // ← AGREGAR ESTO AL INICIO
?>
<head>
    <meta charset="UTF-8">
    <title>Login - Pizzería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" 
          crossorigin="anonymous">

    <style>
        :root {
            --primary-color: #67C090;   /* Verde principal */
            --secondary-color: #DDF4E7; /* Fondo claro */
            --danger-color: #124170;    /* Azul oscuro */
            --light-color: #26667F;     /* Azul medio */
        }

        body {
            background-color: var(--secondary-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-weight: bold;
        }

        .login-body {
            padding: 40px;
            background-color: white;
        }

        .form-label {
            color: var(--light-color);
            font-weight: 600;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid var(--primary-color);
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
            border: none;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background-color: var(--light-color);
        }

        .logo-section {
            background-color: var(--danger-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .logo-section img {
            max-width: 250px;
            border-radius: 15px;
        }

        .logo-section h2 {
            font-weight: bold;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card login-card">
                    <div class="row g-0">
                        <!-- Columna izquierda (formulario) -->
                        <div class="col-md-6">
                            <div class="login-header">
                                <h1>Bienvenido</h1>
                                <p>Inicia sesión en Pizzería</p>
                            </div>
                            <div class="login-body">
                                <div class="mb-4">
                                    <label for="usuario" class="form-label">Usuario</label>
                                    <input type="text" class="form-control" id="usuario" placeholder="Ingresa tu usuario" required>
                                </div>
                                <div class="mb-4">
                                    <label for="contrasena" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="contrasena" placeholder="Ingresa tu contraseña" required>
                                </div>
                                <button class="btn-custom w-100" id="iniciar">Ingresar</button>
                            </div>
                        </div>

                       
                        <div class="col-md-6 logo-section">
                            
                            <div class="text-center">
                                <img src="imagenes/logo.jpg" alt="Logo Pizzería">
                               
                                <p>🍕 ¡La mejor pizza de la ciudad!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Librerías -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            $(document).on('click', '#iniciar', function () {
                if ($("#usuario").val() == '') {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Ingresa un nombre de usuario.' });
                    $("#usuario").focus();
                    return false;
                }
                if ($("#contrasena").val() == '') {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Ingresa una contraseña.' });
                    $("#contrasena").focus();
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: "validar.php",
                    data: {
                        funcion: "iniciar",
                        usuario: $("#usuario").val(),
                        contrasena: $("#contrasena").val()
                    },
                    dataType: "html",
                    success: function (msg) {
                        if (msg === "error") {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Usuario o contraseña incorrectos.'
                            });
                            $('#usuario').val("");
                            $('#contrasena').val("");
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: 'Inicio de sesión exitoso.',
                                showConfirmButton: false,
                                timer: 1000
                            }).then(() => {
                                window.location.href = "croquis.php";
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Error al procesar la solicitud.'
                        });
                    }
                });
            });
        });
    </script>
</body>
