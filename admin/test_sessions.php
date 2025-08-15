<?php
session_start();

echo "<h1>🔍 Diagnóstico de Sesiones Railway</h1>";

echo "<h2>Estado de Sesión:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NO DEFINIDO') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'NO DEFINIDO') . "<br>";

echo "<h2>Todas las variables de sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Configuración de sesión:</h2>";
echo "Session save path: " . session_save_path() . "<br>";
echo "Session name: " . session_name() . "<br>";
echo "Session cookie params: ";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<h2>Headers de cookies:</h2>";
if (isset($_SERVER['HTTP_COOKIE'])) {
    echo "Cookies recibidas: " . $_SERVER['HTTP_COOKIE'] . "<br>";
} else {
    echo "No hay cookies<br>";
}

echo "<h2>Enlaces de prueba:</h2>";
echo '<a href="/admin/dashboard.php">Dashboard</a> | ';
echo '<a href="/admin/clients.php">Clientes</a> | ';
echo '<a href="/admin/calendar.php">Calendar</a>';
?>
