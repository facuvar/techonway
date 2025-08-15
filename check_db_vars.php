<?php
echo "<h1>🔍 Variables de DB en Railway</h1>";

echo "<h2>Variables de entorno:</h2>";
$dbVars = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'DATABASE_URL'];
foreach ($dbVars as $var) {
    $value = $_ENV[$var] ?? 'NO DEFINIDA';
    if (strpos($var, 'PASSWORD') !== false) {
        $value = $value ? '*** (definida)' : 'NO DEFINIDA';
    }
    echo "$var: $value<br>";
}

echo "<h2>Config/database.php:</h2>";
try {
    $config = require 'config/database.php';
    echo "Host: " . ($config['host'] ?? 'NO DEFINIDO') . "<br>";
    echo "Database: " . ($config['database'] ?? 'NO DEFINIDO') . "<br>";
    echo "Username: " . ($config['username'] ?? 'NO DEFINIDO') . "<br>";
    echo "Password: " . (isset($config['password']) ? '*** (definida)' : 'NO DEFINIDA') . "<br>";
} catch (Exception $e) {
    echo "❌ Error cargando config: " . $e->getMessage();
}

echo "<h2>Test conexión directa:</h2>";
try {
    if (isset($_ENV['DATABASE_URL'])) {
        $dbUrl = $_ENV['DATABASE_URL'];
        $dbParts = parse_url($dbUrl);
        $host = $dbParts['host'];
        $port = $dbParts['port'] ?? 3306;
        $dbname = ltrim($dbParts['path'], '/');
        $username = $dbParts['user'];
        $password = $dbParts['pass'];
        
        echo "Intentando conexión con DATABASE_URL...<br>";
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        echo "✅ Conexión exitosa con DATABASE_URL<br>";
    } else {
        echo "❌ DATABASE_URL no está definida<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>
