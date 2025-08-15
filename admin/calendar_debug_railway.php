<?php
// Script de debug específico para el calendario en Railway
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug Calendar Railway</h1>";

try {
    echo "<h2>1. Archivos básicos</h2>";
    
    // Verificar archivos críticos
    $files = [
        '../includes/init.php',
        '../includes/Auth.php', 
        '../includes/Database.php',
        '../config/database.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "✅ $file existe<br>";
        } else {
            echo "❌ $file NO EXISTE<br>";
        }
    }
    
    echo "<h2>2. Includes</h2>";
    require_once '../includes/init.php';
    echo "✅ init.php cargado<br>";
    
    require_once '../includes/Auth.php';
    echo "✅ Auth.php cargado<br>";
    
    require_once '../includes/Database.php';
    echo "✅ Database.php cargado<br>";
    
    echo "<h2>3. Conexión DB</h2>";
    $db = new Database();
    $pdo = $db->getConnection();
    echo "✅ Conexión DB establecida<br>";
    
    echo "<h2>4. Variables de entorno críticas</h2>";
    $envVars = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
    foreach ($envVars as $var) {
        $value = $_ENV[$var] ?? 'NO DEFINIDA';
        if ($var === 'DB_PASSWORD') {
            $value = $value ? '***' : 'NO DEFINIDA';
        }
        echo "$var: $value<br>";
    }
    
    echo "<h2>5. Sesión</h2>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Usuario logueado: " . ($_SESSION['user_id'] ?? 'NO') . "<br>";
    echo "Rol: " . ($_SESSION['role'] ?? 'NO DEFINIDO') . "<br>";
    
    echo "<h2>6. Test Auth</h2>";
    $auth = new Auth();
    if (isset($_SESSION['user_id'])) {
        echo "✅ Usuario en sesión<br>";
        if ($_SESSION['role'] === 'admin') {
            echo "✅ Es admin<br>";
        } else {
            echo "❌ No es admin (rol: " . $_SESSION['role'] . ")<br>";
        }
    } else {
        echo "❌ No hay usuario en sesión<br>";
    }
    
    echo "<h2>7. Test query citas</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE scheduled_date IS NOT NULL");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Tickets con citas: " . $result['total'] . "<br>";
    
    echo "<h2>8. Cookies</h2>";
    foreach ($_COOKIE as $name => $value) {
        echo "$name: " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "<br>";
    }
    
    echo "<h2>✅ Diagnóstico completado - Calendario debería funcionar</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR ENCONTRADO:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
