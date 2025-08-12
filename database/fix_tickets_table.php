<?php
/**
 * Script para verificar y corregir la tabla tickets
 * para asegurar que el campo id sea AUTO_INCREMENT
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    echo "Verificando estructura de la tabla tickets...\n";
    
    // Verificar la estructura actual de la tabla tickets
    $stmt = $pdo->query("SHOW CREATE TABLE tickets");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Estructura actual:\n" . $result['Create Table'] . "\n\n";
    
    // Verificar columnas específicamente
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas de la tabla tickets:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} | {$column['Extra']}\n";
    }
    echo "\n";
    
    // Verificar si el campo id tiene AUTO_INCREMENT
    $idColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if (!$idColumn) {
        echo "❌ Campo 'id' no encontrado en la tabla tickets\n";
        exit(1);
    }
    
    if (stripos($idColumn['Extra'], 'auto_increment') === false) {
        echo "❌ Campo 'id' no tiene AUTO_INCREMENT\n";
        echo "Corrigiendo...\n";
        
        // Modificar la tabla para agregar AUTO_INCREMENT
        $pdo->exec("ALTER TABLE tickets MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
        echo "✓ Campo 'id' corregido con AUTO_INCREMENT\n";
    } else {
        echo "✓ Campo 'id' ya tiene AUTO_INCREMENT\n";
    }
    
    // Verificar AUTO_INCREMENT value
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'tickets'");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Auto_increment actual: " . ($status['Auto_increment'] ?? 'N/A') . "\n";
    
    echo "\n✓ Verificación completada\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
