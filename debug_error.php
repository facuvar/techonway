<?php
// Debug específico para Internal Server Error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "=== DEBUG INTERNAL SERVER ERROR ===\n";

try {
    echo "1. Iniciando debug...\n";
    
    echo "2. Cargando init.php...\n";
    require_once 'includes/init.php';
    echo "✅ init.php cargado\n";
    
    echo "3. Probando database...\n";
    $db = Database::getInstance();
    echo "✅ Database instanciado\n";
    
    echo "4. Probando consulta...\n";
    $users = $db->select("SELECT COUNT(*) as count FROM users");
    echo "✅ Consulta exitosa: " . $users[0]['count'] . " usuarios\n";
    
    echo "5. Probando auth...\n";
    $auth = new Auth();
    echo "✅ Auth instanciado\n";
    
    echo "6. Probando sesión...\n";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Sesión activa\n";
    } else {
        echo "⚠️ Sesión no activa\n";
    }
    
    echo "7. Probando traducciones...\n";
    $translation = __('sidebar.dashboard');
    echo "✅ Traducción: " . $translation . "\n";
    
    echo "\n🎉 TODO FUNCIONA CORRECTAMENTE\n";
    echo "El problema del Internal Server Error debe ser otro...\n";
    
} catch (Exception $e) {
    echo "❌ ERROR ENCONTRADO:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ FATAL ERROR:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== INFORMACIÓN ADICIONAL ===\n";
echo "Error reporting: " . error_reporting() . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "Log errors: " . ini_get('log_errors') . "\n";
echo "Error log: " . ini_get('error_log') . "\n";
?>
