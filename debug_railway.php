<?php
/**
 * Script de debug para Railway
 */

echo "<h1>Debug TechOnWay en Railway</h1>";

// Verificar variables de entorno
echo "<h2>Variables de Entorno</h2>";
echo "<p>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NO DEFINIDO') . "</p>";
echo "<p>DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NO DEFINIDO') . "</p>";
echo "<p>SENDGRID_API_KEY: " . (isset($_ENV['SENDGRID_API_KEY']) ? 'CONFIGURADO' : 'NO DEFINIDO') . "</p>";

// Verificar conexión a base de datos
echo "<h2>Conexión a Base de Datos</h2>";
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'railway';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>✅ Conexión exitosa a: $dbname</p>";
    
    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas encontradas: " . implode(', ', $tables) . "</p>";
    
    // Verificar usuarios admin
    if (in_array('users', $tables)) {
        $admins = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'admin'")->fetchAll();
        echo "<h3>Usuarios Admin:</h3>";
        foreach ($admins as $admin) {
            echo "<p>ID: {$admin['id']}, Email: {$admin['email']}, Nombre: {$admin['name']}</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

// Verificar archivos importantes
echo "<h2>Archivos del Sistema</h2>";
$files = [
    'admin/calendar.php',
    'admin/tickets.php', 
    'config/database.php',
    'includes/init.php',
    'includes/Auth.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✅ $file existe</p>";
    } else {
        echo "<p style='color:red'>❌ $file NO EXISTE</p>";
    }
}

// Verificar permisos de sesión
echo "<h2>Configuración de Sesiones</h2>";
echo "<p>session.save_path: " . session_save_path() . "</p>";
echo "<p>session.cookie_domain: " . ini_get('session.cookie_domain') . "</p>";
echo "<p>session.cookie_secure: " . ini_get('session.cookie_secure') . "</p>";

// Información del servidor
echo "<h2>Información del Servidor</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p>HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</p>";

?>