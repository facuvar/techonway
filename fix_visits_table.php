<?php
/**
 * Script para corregir la tabla visits - problema con AUTO_INCREMENT
 * Ejecutar desde: https://demo.techonway.com/fix_visits_table.php
 */

// Solo permitir ejecución desde el servidor (no localhost)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    die('Este script solo puede ejecutarse en el servidor de producción.');
}

require_once __DIR__ . '/includes/init.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>Corrección de tabla visits</h2>\n";
    echo "<p>Iniciando corrección del problema AUTO_INCREMENT...</p>\n";

    // Verificar estructura actual
    echo "<h3>1. Estructura actual:</h3>\n";
    $columns = $pdo->query("DESCRIBE visits")->fetchAll();
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
        $pdo->exec("ALTER TABLE visits MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
        echo "<p>✅ Campo 'id' corregido con AUTO_INCREMENT</p>\n";
        
        // Verificar que se aplicó la corrección
        $columns2 = $pdo->query("DESCRIBE visits")->fetchAll();
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

    echo "<h3>2. Verificando otros campos necesarios...</h3>\n";
    
    // Verificar si existe el campo start_notes
    $hasStartNotes = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'start_notes') {
            $hasStartNotes = true;
            break;
        }
    }
    
    if (!$hasStartNotes) {
        echo "<p>🔧 Agregando campo start_notes...</p>\n";
        $pdo->exec("ALTER TABLE visits ADD COLUMN start_notes TEXT AFTER start_time");
        echo "<p>✅ Campo start_notes agregado</p>\n";
    } else {
        echo "<p>✅ Campo start_notes ya existe</p>\n";
    }
    
    // Verificar campos de geolocalización
    $hasLatitude = false;
    $hasLongitude = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'latitude') {
            $hasLatitude = true;
        }
        if ($column['Field'] === 'longitude') {
            $hasLongitude = true;
        }
    }
    
    if (!$hasLatitude || !$hasLongitude) {
        echo "<p>🔧 Agregando campos de geolocalización...</p>\n";
        if (!$hasLatitude) {
            $pdo->exec("ALTER TABLE visits ADD COLUMN latitude DECIMAL(10, 8) NULL");
            echo "<p>✅ Campo latitude agregado</p>\n";
        }
        if (!$hasLongitude) {
            $pdo->exec("ALTER TABLE visits ADD COLUMN longitude DECIMAL(11, 8) NULL");
            echo "<p>✅ Campo longitude agregado</p>\n";
        }
    } else {
        echo "<p>✅ Campos de geolocalización ya existen</p>\n";
    }

    echo "<h3>3. Probando inserción...</h3>\n";
    
    // Obtener un ticket válido para la prueba
    $testTicket = $pdo->query("SELECT id FROM tickets LIMIT 1")->fetch();
    
    if ($testTicket) {
        // Probar inserción sin especificar id
        $testData = [
            'ticket_id' => $testTicket['id'],
            'start_time' => date('Y-m-d H:i:s'),
            'start_notes' => 'Test de corrección AUTO_INCREMENT',
            'latitude' => '-34.6037',
            'longitude' => '-58.3816'
        ];
        
        $insertId = $db->insert('visits', $testData);
        echo "<p>✅ INSERT exitoso - ID generado automáticamente: " . $insertId . "</p>\n";
        
        // Limpiar el registro de prueba
        $db->delete('visits', 'id = ?', [$insertId]);
        echo "<p>✅ Registro de prueba eliminado</p>\n";
    } else {
        echo "<p>⚠️ No hay tickets disponibles para probar la inserción</p>\n";
    }

    echo "<h3>✅ CORRECCIÓN COMPLETADA</h3>\n";
    echo "<p><strong>La tabla visits ha sido corregida exitosamente.</strong></p>\n";
    echo "<p>Ahora el inicio de visitas debería funcionar correctamente.</p>\n";
    echo "<p><a href='technician/dashboard.php'>Ir al Dashboard del Técnico</a></p>\n";

} catch (Exception $e) {
    echo "<h3>❌ Error durante la corrección</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>\n";
    error_log("Fix visits table error: " . $e->getMessage());
}
?>
