<?php
/**
 * Script para corregir la tabla service_requests - problema con AUTO_INCREMENT
 * Ejecutar desde: https://demo.techonway.com/fix_service_requests_table.php
 */

// Solo permitir ejecución desde el servidor (no localhost)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    die('Este script solo puede ejecutarse en el servidor de producción.');
}

require_once __DIR__ . '/includes/init.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>Corrección de tabla service_requests</h2>\n";
    echo "<p>Iniciando corrección del problema AUTO_INCREMENT...</p>\n";

    // Verificar estructura actual
    echo "<h3>1. Estructura actual:</h3>\n";
    $columns = $pdo->query("DESCRIBE service_requests")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>" . ($column['Extra'] ?? '') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // Buscar si el campo id tiene AUTO_INCREMENT
    $idField = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $idField = $column;
            break;
        }
    }

    if ($idField && strpos($idField['Extra'] ?? '', 'auto_increment') === false) {
        echo "<p>❌ Problema encontrado: Campo 'id' sin AUTO_INCREMENT</p>\n";
        echo "<p>🔧 Corrigiendo...</p>\n";
        
        // Corregir el campo id para que sea AUTO_INCREMENT
        $pdo->exec("ALTER TABLE service_requests MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
        echo "<p>✅ Campo 'id' corregido con AUTO_INCREMENT</p>\n";
        
        // Verificar que se aplicó la corrección
        $columns2 = $pdo->query("DESCRIBE service_requests")->fetchAll();
        $idField2 = null;
        foreach ($columns2 as $column) {
            if ($column['Field'] === 'id') {
                $idField2 = $column;
                break;
            }
        }
        
        if ($idField2 && strpos($idField2['Extra'] ?? '', 'auto_increment') !== false) {
            echo "<p>✅ Corrección verificada exitosamente</p>\n";
        } else {
            echo "<p>❌ La corrección no se aplicó correctamente</p>\n";
        }
    } else {
        echo "<p>✅ Campo 'id' ya tiene AUTO_INCREMENT correctamente configurado</p>\n";
    }

    echo "<h3>2. Probando inserción...</h3>\n";
    
    // Probar inserción sin especificar id
    $testData = [
        'type' => 'Test Fix',
        'name' => 'Test Auto Increment',
        'phone' => '123456789',
        'address' => 'Test Address',
        'detail' => 'Test Detail',
        'status' => 'pending'
    ];
    
    $insertId = $db->insert('service_requests', $testData);
    echo "<p>✅ INSERT exitoso - ID generado automáticamente: " . $insertId . "</p>\n";
    
    // Limpiar el registro de prueba
    $db->delete('service_requests', 'id = ?', [$insertId]);
    echo "<p>✅ Registro de prueba eliminado</p>\n";

    echo "<h3>✅ CORRECCIÓN COMPLETADA</h3>\n";
    echo "<p><strong>La tabla service_requests ha sido corregida exitosamente.</strong></p>\n";
    echo "<p>Ahora el formulario debería funcionar correctamente: <a href='service_request.php'>service_request.php</a></p>\n";

} catch (Exception $e) {
    echo "<h3>❌ Error durante la corrección</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>\n";
    error_log("Fix service_requests error: " . $e->getMessage());
}
?>
