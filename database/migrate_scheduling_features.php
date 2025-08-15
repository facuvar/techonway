<?php
/**
 * Migración para agregar funcionalidades de programación de citas
 * 
 * Agrega:
 * - Campo 'email' a la tabla clients
 * - Campos 'scheduled_date', 'scheduled_time', 'security_code' a la tabla tickets
 */

require_once __DIR__ . '/../includes/init.php';

// No requerir autenticación para poder ejecutar desde línea de comandos
// $auth->requireAdmin();

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    echo "=== MIGRACIÓN: FUNCIONALIDADES DE PROGRAMACIÓN DE CITAS ===\n\n";
    
    // 1. Agregar campo email a la tabla clients
    echo "1. Verificando campo 'email' en tabla clients...\n";
    $emailColumnExists = $pdo->query("SHOW COLUMNS FROM clients LIKE 'email'")->rowCount() > 0;
    
    if (!$emailColumnExists) {
        echo "   Agregando campo 'email' a tabla clients...\n";
        $pdo->exec("ALTER TABLE clients ADD COLUMN email VARCHAR(100) NULL AFTER address");
        echo "   ✅ Campo 'email' agregado correctamente\n";
    } else {
        echo "   ⚠️ Campo 'email' ya existe en tabla clients\n";
    }
    
    // 2. Agregar campos de programación a la tabla tickets
    echo "\n2. Verificando campos de programación en tabla tickets...\n";
    
    $scheduledDateExists = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'scheduled_date'")->rowCount() > 0;
    $scheduledTimeExists = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'scheduled_time'")->rowCount() > 0;
    $securityCodeExists = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'security_code'")->rowCount() > 0;
    
    if (!$scheduledDateExists) {
        echo "   Agregando campo 'scheduled_date' a tabla tickets...\n";
        $pdo->exec("ALTER TABLE tickets ADD COLUMN scheduled_date DATE NULL AFTER status");
        echo "   ✅ Campo 'scheduled_date' agregado correctamente\n";
    } else {
        echo "   ⚠️ Campo 'scheduled_date' ya existe en tabla tickets\n";
    }
    
    if (!$scheduledTimeExists) {
        echo "   Agregando campo 'scheduled_time' a tabla tickets...\n";
        $pdo->exec("ALTER TABLE tickets ADD COLUMN scheduled_time TIME NULL AFTER scheduled_date");
        echo "   ✅ Campo 'scheduled_time' agregado correctamente\n";
    } else {
        echo "   ⚠️ Campo 'scheduled_time' ya existe en tabla tickets\n";
    }
    
    if (!$securityCodeExists) {
        echo "   Agregando campo 'security_code' a tabla tickets...\n";
        $pdo->exec("ALTER TABLE tickets ADD COLUMN security_code VARCHAR(4) NULL AFTER scheduled_time");
        echo "   ✅ Campo 'security_code' agregado correctamente\n";
    } else {
        echo "   ⚠️ Campo 'security_code' ya existe en tabla tickets\n";
    }
    
    // 3. Verificar la estructura final
    echo "\n3. Verificando estructura final de las tablas...\n";
    
    echo "\n   Tabla clients:\n";
    $clientColumns = $pdo->query("SHOW COLUMNS FROM clients")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($clientColumns as $column) {
        $indicator = $column['Field'] === 'email' ? '📧' : '📝';
        echo "   $indicator {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n   Tabla tickets:\n";
    $ticketColumns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ticketColumns as $column) {
        $indicator = in_array($column['Field'], ['scheduled_date', 'scheduled_time', 'security_code']) ? '🗓️' : '📝';
        echo "   $indicator {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n";
    echo "\nNuevas funcionalidades disponibles:\n";
    echo "✅ Clientes pueden recibir emails de notificación\n";
    echo "✅ Tickets pueden tener fecha y hora programada\n";
    echo "✅ Tickets tienen código de seguridad de 4 dígitos\n";
    echo "✅ Base de datos lista para sistema de citas programadas\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
