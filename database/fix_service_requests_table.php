<?php
/**
 * Script para verificar y corregir la tabla service_requests
 * para asegurar que tenga todas las columnas necesarias
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    echo "Verificando estructura de la tabla service_requests...\n";
    
    // Verificar la estructura actual de la tabla service_requests
    $stmt = $pdo->query("SHOW CREATE TABLE service_requests");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Estructura actual:\n" . $result['Create Table'] . "\n\n";
    
    // Verificar columnas específicamente
    $stmt = $pdo->query("SHOW COLUMNS FROM service_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas de la tabla service_requests:\n";
    $columnNames = [];
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} | {$column['Extra']}\n";
        $columnNames[] = $column['Field'];
    }
    echo "\n";
    
    // Verificar si falta la columna ticket_id
    if (!in_array('ticket_id', $columnNames)) {
        echo "❌ Columna 'ticket_id' no encontrada\n";
        echo "Agregando columna ticket_id...\n";
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN ticket_id INT NULL AFTER detail");
        echo "✓ Columna 'ticket_id' agregada\n";
    } else {
        echo "✓ Columna 'ticket_id' existe\n";
    }
    
    // Verificar foreign keys
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'service_requests'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Foreign keys actuales:\n";
    if (empty($foreignKeys)) {
        echo "- Ninguna\n";
    } else {
        foreach ($foreignKeys as $fk) {
            echo "- {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
    }
    echo "\n";
    
    // Verificar si existe la foreign key para ticket_id
    $hasTicketFK = false;
    foreach ($foreignKeys as $fk) {
        if ($fk['COLUMN_NAME'] === 'ticket_id' && $fk['REFERENCED_TABLE_NAME'] === 'tickets') {
            $hasTicketFK = true;
            break;
        }
    }
    
    if (!$hasTicketFK) {
        echo "❌ Foreign key para ticket_id no encontrada\n";
        echo "Intentando agregar foreign key...\n";
        try {
            $pdo->exec("ALTER TABLE service_requests ADD CONSTRAINT fk_service_requests_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL");
            echo "✓ Foreign key agregada\n";
        } catch (Exception $ex) {
            echo "⚠️ No se pudo agregar foreign key (puede existir): " . $ex->getMessage() . "\n";
        }
    } else {
        echo "✓ Foreign key para ticket_id existe\n";
    }
    
    // Probar una actualización simple
    echo "\nProbando actualización de service_requests...\n";
    try {
        // Buscar un registro de prueba
        $testRecord = $pdo->query("SELECT id FROM service_requests LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($testRecord) {
            $testId = $testRecord['id'];
            $stmt = $pdo->prepare("UPDATE service_requests SET status = 'pending' WHERE id = ?");
            $result = $stmt->execute([$testId]);
            if ($result) {
                echo "✓ Prueba de actualización exitosa\n";
            } else {
                echo "❌ Prueba de actualización falló\n";
                print_r($stmt->errorInfo());
            }
        } else {
            echo "- No hay registros para probar\n";
        }
    } catch (Exception $ex) {
        echo "❌ Error en prueba de actualización: " . $ex->getMessage() . "\n";
    }
    
    echo "\n✓ Verificación completada\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
