<?php
/**
 * Diagnóstico para Railway - Debug de errores
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 TechonWay Railway Diagnóstico</h1>";

echo "<h2>✅ PHP Info</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Current Dir:</strong> " . getcwd() . "<br>";

echo "<h2>📁 Files Check</h2>";
$files_to_check = [
    'includes/init.php',
    'includes/Database.php', 
    'includes/Auth.php',
    'config/database.php',
    'config/local.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = is_readable($file);
    echo "<strong>{$file}:</strong> " . ($exists ? "✅ Exists" : "❌ Missing") . 
         ($readable ? " & Readable" : " & Not Readable") . "<br>";
}

echo "<h2>🔧 Environment Variables</h2>";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'PORT'];
foreach ($env_vars as $var) {
    $value = $_ENV[$var] ?? 'Not set';
    echo "<strong>{$var}:</strong> " . ($value !== 'Not set' ? "✅ Set" : "❌ Not set") . "<br>";
}

echo "<h2>🗄️ Database Test</h2>";
try {
    // Intentar cargar configuración
    if (file_exists('config/database.php')) {
        echo "✅ Config file exists<br>";
        $config = require 'config/database.php';
        echo "✅ Config loaded: " . (is_array($config) ? "Array" : gettype($config)) . "<br>";
        
        if (is_array($config)) {
            echo "<strong>DB Host:</strong> " . ($config['host'] ?? 'Not set') . "<br>";
            echo "<strong>DB Name:</strong> " . ($config['dbname'] ?? 'Not set') . "<br>";
            echo "<strong>DB User:</strong> " . ($config['username'] ?? 'Not set') . "<br>";
            echo "<strong>DB Pass:</strong> " . (isset($config['password']) ? (empty($config['password']) ? 'Empty' : 'Set') : 'Not set') . "<br>";
        }
    } else {
        echo "❌ Config file missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
}

echo "<h2>📋 Init Test</h2>";
try {
    // Intentar cargar init.php
    require_once 'includes/init.php';
    echo "✅ Init.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading init.php: " . $e->getMessage() . "<br>";
    echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🔗 Test Links</h2>";
echo "<a href='/login.php'>🔗 Test Login</a><br>";
echo "<a href='/admin/dashboard.php'>🔗 Test Admin Dashboard</a><br>";
echo "<a href='/?debug=railway'>🔗 Debug Index</a><br>";
?>
