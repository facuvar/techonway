<?php
/**
 * Solución agresiva para duplicados
 * Modifica directamente las consultas para usar GROUP BY
 */

require_once 'includes/init.php';

$db = Database::getInstance();
$technicianId = $_SESSION['user_id'] ?? 5; // Usar ID de prueba si no hay sesión

echo "=== SOLUCIÓN AGRESIVA DUPLICADOS ===\n\n";

try {
    echo "1. Probando consultas alternativas...\n";
    
    // Método 1: GROUP BY directo
    echo "\nMétodo 1 - GROUP BY:\n";
    $method1 = $db->select("
        SELECT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
               c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
        FROM tickets t
        INNER JOIN clients c ON t.client_id = c.id
        WHERE t.assigned_to = ?
        AND t.scheduled_date IS NOT NULL 
        AND t.scheduled_date >= CURDATE()
        GROUP BY t.id
        ORDER BY t.scheduled_date, t.scheduled_time
        LIMIT 5
    ", [$technicianId]);
    
    echo "Resultados Método 1: " . count($method1) . "\n";
    
    // Método 2: Solo seleccionar campos de tickets
    echo "\nMétodo 2 - Sin JOIN:\n";
    $ticketIds = $db->select("
        SELECT DISTINCT id 
        FROM tickets 
        WHERE assigned_to = ?
        AND scheduled_date IS NOT NULL 
        AND scheduled_date >= CURDATE()
        ORDER BY scheduled_date, scheduled_time
        LIMIT 5
    ", [$technicianId]);
    
    $method2 = [];
    foreach ($ticketIds as $ticketRow) {
        $ticket = $db->selectOne("
            SELECT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
                   c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
            FROM tickets t
            INNER JOIN clients c ON t.client_id = c.id
            WHERE t.id = ?
        ", [$ticketRow['id']]);
        
        if ($ticket) {
            $method2[] = $ticket;
        }
    }
    
    echo "Resultados Método 2: " . count($method2) . "\n";
    
    // Método 3: Verificar duplicados reales en DB
    echo "\nMétodo 3 - Verificar duplicados reales:\n";
    $duplicateCheck = $db->select("
        SELECT id, COUNT(*) as count
        FROM tickets 
        WHERE assigned_to = ?
        GROUP BY id
        HAVING count > 1
    ", [$technicianId]);
    
    echo "Tickets duplicados reales: " . count($duplicateCheck) . "\n";
    
    if (count($duplicateCheck) > 0) {
        echo "¡PROBLEMA! Hay tickets con IDs duplicados en la base de datos:\n";
        foreach ($duplicateCheck as $dup) {
            echo "  - Ticket ID {$dup['id']}: {$dup['count']} veces\n";
        }
    }
    
    // Mostrar resultados del mejor método
    if (count($method1) > 0) {
        echo "\n4. Resultados únicos encontrados:\n";
        foreach ($method1 as $appointment) {
            echo "  - Ticket #{$appointment['id']}: {$appointment['client_name']} - {$appointment['scheduled_date']} {$appointment['scheduled_time']}\n";
        }
    }
    
    echo "\n=== RECOMENDACIÓN ===\n";
    if (count($duplicateCheck) > 0) {
        echo "❌ HAY DUPLICADOS REALES en la base de datos. Necesitas limpiar los datos duplicados.\n";
    } elseif (count($method1) === count($method2)) {
        echo "✅ Las consultas GROUP BY funcionan. Usar este método en el dashboard.\n";
    } else {
        echo "⚠️ Investigar más - hay inconsistencias entre métodos.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
