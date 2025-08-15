<?php
/**
 * Script para probar acceso admin
 */
session_start();

echo "<h1>Test Admin Access</h1>";

// Mostrar información de sesión
echo "<h2>Información de Sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Probar autenticación
require_once 'includes/init.php';

echo "<h2>Estado de Autenticación:</h2>";
echo "<p>Sesión iniciada: " . (session_id() ? 'SÍ' : 'NO') . "</p>";
echo "<p>User ID en sesión: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "</p>";
echo "<p>User role en sesión: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "</p>";

// Probar conexión a base de datos
echo "<h2>Test Base de Datos:</h2>";
try {
    $db = Database::getInstance();
    echo "<p style='color:green'>✅ Conexión DB exitosa</p>";
    
    // Verificar usuarios admin
    $admins = $db->select("SELECT id, name, email, role FROM users WHERE role = 'admin'");
    echo "<h3>Usuarios Admin en BD:</h3>";
    foreach ($admins as $admin) {
        echo "<p>ID: {$admin['id']}, Email: {$admin['email']}, Nombre: {$admin['name']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error DB: " . $e->getMessage() . "</p>";
}

// Formulario de login directo
echo "<h2>Login Directo:</h2>";
echo '<form method="post" action="login.php">
    <p>Email: <input type="email" name="email" value="admin@techonway.com"></p>
    <p>Password: <input type="password" name="password" value="admin123"></p>
    <p><button type="submit">Login</button></p>
</form>';

echo "<h2>Enlaces de Prueba:</h2>";
echo '<p><a href="admin/calendar.php">Ir a Calendar</a></p>';
echo '<p><a href="login.php">Ir a Login</a></p>';
echo '<p><a href="admin/dashboard.php">Ir a Admin Dashboard</a></p>';
?>
