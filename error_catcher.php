<?php
/**
 * Capturador de errores simple para VPS
 */

// Configurar para mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/vps_errors.log');

// Función para capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $log = date('Y-m-d H:i:s') . " FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
        file_put_contents(__DIR__ . '/vps_errors.log', $log, FILE_APPEND);
        
        echo "<!DOCTYPE html><html><head><title>Error Capturado</title></head><body>";
        echo "<h1 style='color: red;'>Error Fatal Capturado</h1>";
        echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($error['message']) . "</p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($error['file']) . "</p>";
        echo "<p><strong>Línea:</strong> " . $error['line'] . "</p>";
        echo "<p><strong>Tipo:</strong> " . $error['type'] . "</p>";
        echo "<hr>";
        echo "<p>Error guardado en: vps_errors.log</p>";
        echo "</body></html>";
    }
});

// Handler para errores no fatales
set_error_handler(function($severity, $message, $file, $line) {
    $log = date('Y-m-d H:i:s') . " ERROR [$severity]: $message in $file on line $line\n";
    file_put_contents(__DIR__ . '/vps_errors.log', $log, FILE_APPEND);
    
    // Mostrar en pantalla también
    echo "<div style='background: #ffeeee; border: 1px solid red; padding: 10px; margin: 5px;'>";
    echo "<strong>Error [$severity]:</strong> " . htmlspecialchars($message);
    echo "<br><strong>Archivo:</strong> " . htmlspecialchars($file);
    echo "<br><strong>Línea:</strong> $line";
    echo "</div>";
    
    return true; // No ejecutar el handler interno de PHP
});

echo "<h1>🔍 Error Catcher Activado</h1>";
echo "<p>Ahora prueba cargar cualquier página. Los errores aparecerán aquí y se guardarán en vps_errors.log</p>";

// Probar diferentes archivos del sistema
echo "<h2>Probando archivos del sistema:</h2>";

$files_to_test = [
    'login.php',
    'dashboard.php', 
    'includes/init.php',
    'admin/dashboard.php'
];

foreach ($files_to_test as $file) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Probando: $file</h3>";
    
    if (!file_exists($file)) {
        echo "<p style='color: orange;'>⚠️ Archivo no existe: $file</p>";
        continue;
    }
    
    echo "<p style='color: green;'>✅ Archivo existe: $file</p>";
    
    // Verificar sintaxis
    $output = shell_exec("php -l \"$file\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>✅ Sintaxis correcta</p>";
        
        // Intentar cargar (pero capturar output)
        echo "<p>🧪 Intentando cargar...</p>";
        ob_start();
        try {
            include $file;
            $content = ob_get_contents();
            ob_end_clean();
            echo "<p style='color: green;'>✅ Cargado exitosamente</p>";
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p style='color: red;'>❌ Error al cargar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error de sintaxis:</p>";
        echo "<pre style='background: #ffeeee; padding: 10px;'>" . htmlspecialchars($output) . "</pre>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='vps_errors.log' target='_blank'>📄 Ver log de errores completo</a></p>";
echo "<p><a href='debug_vps.php'>🔍 Ir a debug completo</a></p>";
?>
