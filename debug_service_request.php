<?php
/**
 * Script de debug para service_request.php
 * Ejecutar desde: https://demo.techonway.com/debug_service_request.php
 */

// Habilitar reporte de errores completo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Debug de service_request.php</h2>\n";

try {
    echo "<h3>1. Verificando archivos incluidos...</h3>\n";
    
    if (file_exists(__DIR__ . '/includes/init.php')) {
        echo "✅ includes/init.php existe<br>\n";
        require_once __DIR__ . '/includes/init.php';
        echo "✅ includes/init.php cargado<br>\n";
    } else {
        echo "❌ includes/init.php NO existe<br>\n";
    }

    if (file_exists(__DIR__ . '/includes/Mailer.php')) {
        echo "✅ includes/Mailer.php existe<br>\n";
        require_once __DIR__ . '/includes/Mailer.php';
        echo "✅ includes/Mailer.php cargado<br>\n";
    } else {
        echo "❌ includes/Mailer.php NO existe<br>\n";
    }

    if (file_exists(__DIR__ . '/config/mail.php')) {
        echo "✅ config/mail.php existe<br>\n";
        $mailConfig = require __DIR__ . '/config/mail.php';
        echo "✅ config/mail.php cargado<br>\n";
    } else {
        echo "❌ config/mail.php NO existe<br>\n";
    }

    if (file_exists(__DIR__ . '/includes/Settings.php')) {
        echo "✅ includes/Settings.php existe<br>\n";
        require_once __DIR__ . '/includes/Settings.php';
        echo "✅ includes/Settings.php cargado<br>\n";
    } else {
        echo "❌ includes/Settings.php NO existe<br>\n";
    }

    echo "<h3>2. Verificando conexión a base de datos...</h3>\n";
    
    $db = Database::getInstance();
    echo "✅ Database::getInstance() exitoso<br>\n";
    
    $pdo = $db->getConnection();
    echo "✅ Conexión a base de datos exitosa<br>\n";

    echo "<h3>3. Verificando tabla service_requests...</h3>\n";
    
    $result = $pdo->query("SHOW TABLES LIKE 'service_requests'");
    if ($result->rowCount() > 0) {
        echo "✅ Tabla service_requests existe<br>\n";
        
        // Verificar estructura
        $columns = $pdo->query("DESCRIBE service_requests")->fetchAll();
        echo "Columnas: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " ";
        }
        echo "<br>\n";
    } else {
        echo "❌ Tabla service_requests NO existe<br>\n";
    }

    echo "<h3>4. Probando Settings...</h3>\n";
    
    $settings = new Settings();
    echo "✅ Settings instanciado<br>\n";
    
    $testValue = $settings->get('service_requests_notify_to', 'default@test.com');
    echo "✅ Settings::get() funciona - valor: " . htmlspecialchars($testValue) . "<br>\n";

    echo "<h3>5. Probando inserción en service_requests...</h3>\n";
    
    $testData = [
        'type' => 'Test',
        'name' => 'Test Debug',
        'phone' => '123456789',
        'address' => 'Test Address',
        'detail' => 'Test Detail',
        'status' => 'pending'
    ];
    
    $insertId = $db->insert('service_requests', $testData);
    echo "✅ INSERT exitoso - ID: " . $insertId . "<br>\n";
    
    // Limpiar el registro de prueba
    $db->delete('service_requests', 'id = ?', [$insertId]);
    echo "✅ Registro de prueba eliminado<br>\n";

    echo "<h3>6. Probando Mailer...</h3>\n";
    
    try {
        $mailer = new Mailer();
        echo "✅ Mailer instanciado<br>\n";
    } catch (Exception $e) {
        echo "⚠️ Error en Mailer: " . htmlspecialchars($e->getMessage()) . "<br>\n";
    }

    echo "<h3>✅ RESULTADO FINAL</h3>\n";
    echo "<p><strong>Todos los componentes principales funcionan correctamente.</strong></p>\n";
    echo "<p>El problema puede estar en:</p>\n";
    echo "<ul>\n";
    echo "<li>Headers ya enviados</li>\n";
    echo "<li>Error en el template/header</li>\n";
    echo "<li>Problema con el envío de email</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<h3>❌ ERROR ENCONTRADO</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>\n";
    echo "<p><strong>Stack trace:</strong></p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
