<?php
/**
 * Script de migración SOLO para base de datos LOCAL
 * 
 * IMPORTANTE: Este script NO debe ejecutarse en Railway
 * Solo sirve para sincronizar la estructura local con la de producción
 */

// Verificar que estamos en entorno local
if (!isset($_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die("❌ ERROR: Este script solo debe ejecutarse en entorno LOCAL\n");
}

require_once 'includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "=== MIGRACIÓN BASE DE DATOS LOCAL ===\n\n";
echo "⚠️  IMPORTANTE: Solo para entorno local\n\n";

try {
    echo "1. Verificando y agregando columnas faltantes...\n\n";
    
    // Verificar tabla users
    echo "   Tabla USERS:\n";
    $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('last_name', $userColumns)) {
        echo "     + Agregando columna 'last_name'\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER name");
    } else {
        echo "     ✓ Columna 'last_name' ya existe\n";
    }
    
    // Verificar tabla tickets
    echo "\n   Tabla TICKETS:\n";
    $ticketColumns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
    
    $ticketColumnsToAdd = [
        'priority' => "ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium'",
        'scheduled_date' => "DATE NULL",
        'scheduled_time' => "TIME NULL", 
        'security_code' => "VARCHAR(10) NULL",
        'notes' => "TEXT NULL"
    ];
    
    foreach ($ticketColumnsToAdd as $column => $definition) {
        if (!in_array($column, $ticketColumns)) {
            echo "     + Agregando columna '{$column}'\n";
            $pdo->exec("ALTER TABLE tickets ADD COLUMN {$column} {$definition}");
        } else {
            echo "     ✓ Columna '{$column}' ya existe\n";
        }
    }
    
    // Verificar si assigned_to ya fue agregado anteriormente
    if (!in_array('assigned_to', $ticketColumns)) {
        echo "     + Agregando columna 'assigned_to'\n";
        $pdo->exec("ALTER TABLE tickets ADD COLUMN assigned_to INT NULL AFTER technician_id");
        $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
    } else {
        echo "     ✓ Columna 'assigned_to' ya existe\n";
    }
    
    // Verificar tabla visits (si necesita mejoras)
    echo "\n   Tabla VISITS:\n";
    $visitColumns = $pdo->query("SHOW COLUMNS FROM visits")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('start_notes', $visitColumns)) {
        echo "     + Agregando columna 'start_notes'\n";
        $pdo->exec("ALTER TABLE visits ADD COLUMN start_notes TEXT NULL AFTER start_time");
    } else {
        echo "     ✓ Columna 'start_notes' ya existe\n";
    }
    
    echo "\n2. Verificando estructura final...\n";
    
    // Verificar que todo esté bien
    $finalCheck = [
        'users' => ['last_name'],
        'tickets' => ['priority', 'scheduled_date', 'scheduled_time', 'security_code', 'assigned_to'],
        'visits' => ['start_notes']
    ];
    
    $allGood = true;
    foreach ($finalCheck as $table => $columns) {
        $tableColumns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns as $column) {
            if (!in_array($column, $tableColumns)) {
                echo "   ❌ FALTA: {$table}.{$column}\n";
                $allGood = false;
            }
        }
    }
    
    if ($allGood) {
        echo "   ✅ Todas las columnas necesarias están presentes\n";
    }
    
    echo "\n3. Estadísticas de la base de datos:\n";
    
    $stats = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'tickets' => $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(), 
        'visits' => $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn(),
        'clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn()
    ];
    
    foreach ($stats as $table => $count) {
        echo "   {$table}: {$count} registros\n";
    }
    
    echo "\n✅ MIGRACIÓN LOCAL COMPLETADA\n";
    echo "\nAhora puedes probar:\n";
    echo "- http://localhost/sistema-techonway/admin/tickets.php\n";
    echo "- http://localhost/sistema-techonway/admin/calendar.php\n";
    echo "- http://localhost/sistema-techonway/technician/dashboard.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
