<?php
session_start();
echo "<h2>Estado de la Sesión</h2>";
echo "<pre>";
echo "SESSION ID: " . session_id() . "\n";
echo "SESSION STATUS: " . session_status() . "\n";
echo "SESSION DATA:\n";
print_r($_SESSION);
echo "</pre>";

// Verificar si pasarías check_session.php
if (isset($_SESSION['SISTEMA']['id_usuario'])) {
    echo "<p style='color: green;'>✓ check_session.php: PASARÍA</p>";
} else {
    echo "<p style='color: red;'>✗ check_session.php: NO PASARÍA</p>";
}
?>