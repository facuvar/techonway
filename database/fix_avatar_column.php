<?php
/**
 * Script para verificar y corregir la columna avatar en la tabla users
 * para soportar data URIs largos en Railway
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    echo "Verificando estructura de la tabla users...\n";
    
    // Verificar si la columna avatar existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        echo "Agregando columna avatar...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar MEDIUMTEXT NULL AFTER phone");
        echo "✓ Columna avatar agregada como MEDIUMTEXT\n";
    } else {
        echo "Columna avatar encontrada. Tipo actual: " . $column['Type'] . "\n";
        
        // Si no es MEDIUMTEXT, actualizarla
        if (stripos($column['Type'], 'mediumtext') === false) {
            echo "Actualizando tipo de columna avatar a MEDIUMTEXT...\n";
            $pdo->exec("ALTER TABLE users MODIFY COLUMN avatar MEDIUMTEXT NULL");
            echo "✓ Columna avatar actualizada a MEDIUMTEXT\n";
        } else {
            echo "✓ La columna avatar ya es MEDIUMTEXT\n";
        }
    }
    
    // Verificar configuración de memoria y upload en Railway
    echo "\nConfiguraciones de PHP:\n";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "post_max_size: " . ini_get('post_max_size') . "\n";
    echo "memory_limit: " . ini_get('memory_limit') . "\n";
    echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
    
    // Verificar que fileinfo esté disponible
    if (extension_loaded('fileinfo')) {
        echo "✓ Extensión fileinfo disponible\n";
    } else {
        echo "✗ Extensión fileinfo NO disponible\n";
    }
    
    echo "\n✓ Verificación completada\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
