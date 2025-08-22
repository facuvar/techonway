<?php
/**
 * Fix rápido para columnas faltantes en local
 */

$pdo = new PDO('mysql:host=localhost;dbname=techonway', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Reparando columnas faltantes...\n\n";

try {
    // 1. Agregar last_name a users
    echo "1. Agregando users.last_name...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER name");
        echo "   ✅ last_name agregado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ last_name ya existe\n";
        } else {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Agregar priority a tickets
    echo "\n2. Agregando tickets.priority...\n";
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium'");
        echo "   ✅ priority agregado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ priority ya existe\n";
        } else {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Agregar scheduled_date y scheduled_time
    echo "\n3. Agregando campos de cita...\n";
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN scheduled_date DATE NULL");
        echo "   ✅ scheduled_date agregado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ scheduled_date ya existe\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN scheduled_time TIME NULL");
        echo "   ✅ scheduled_time agregado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ scheduled_time ya existe\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN security_code VARCHAR(10) NULL");
        echo "   ✅ security_code agregado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ security_code ya existe\n";
        }
    }
    
    // 4. Verificar assigned_to
    echo "\n4. Verificando assigned_to...\n";
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN assigned_to INT NULL AFTER technician_id");
        $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
        echo "   ✅ assigned_to agregado y sincronizado\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ assigned_to ya existe\n";
        }
    }
    
    echo "\n=== TESTING CONSULTAS ===\n";
    
    // Test consulta de tickets.php
    echo "\n5. Test admin/tickets.php:\n";
    try {
        $stmt = $pdo->query("SELECT id, name, COALESCE(last_name, '') as last_name FROM users WHERE role = 'technician' LIMIT 1");
        $result = $stmt->fetch();
        echo "   ✅ Consulta tickets.php OK\n";
    } catch (Exception $e) {
        echo "   ❌ Error tickets.php: " . $e->getMessage() . "\n";
    }
    
    // Test consulta de calendar.php
    echo "\n6. Test admin/calendar.php:\n";
    try {
        $stmt = $pdo->query("SELECT t.id, t.priority, t.scheduled_date FROM tickets t LIMIT 1");
        $result = $stmt->fetch();
        echo "   ✅ Consulta calendar.php OK\n";
    } catch (Exception $e) {
        echo "   ❌ Error calendar.php: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ REPARACIÓN COMPLETADA\n";
    echo "Prueba ahora:\n";
    echo "- http://localhost/sistema-techonway/admin/tickets.php\n";
    echo "- http://localhost/sistema-techonway/admin/calendar.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
