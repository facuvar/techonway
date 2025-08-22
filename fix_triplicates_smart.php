<?php
/**
 * Solución inteligente para triplicados
 * Usar array_unique en PHP para eliminar duplicados después de la consulta
 */

require_once 'includes/init.php';

$db = Database::getInstance();
$technicianId = 5; // ID de prueba

echo "=== ANÁLISIS DE TRIPLICADOS ===\n\n";

try {
    // 1. Consulta actual que genera triplicados
    echo "1. Consulta actual (problemática):\n";
    $currentQuery = $db->select("
        SELECT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
               c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
        FROM tickets t
        LEFT JOIN clients c ON t.client_id = c.id
        WHERE t.assigned_to = ? 
        AND t.scheduled_date IS NOT NULL 
        AND t.scheduled_date >= CURDATE()
        ORDER BY t.scheduled_date, t.scheduled_time, t.id
        LIMIT 15
    ", [$technicianId]);
    
    echo "Resultados totales: " . count($currentQuery) . "\n";
    
    // Agrupar por ID para ver duplicados
    $groupedById = [];
    foreach ($currentQuery as $ticket) {
        $id = $ticket['id'];
        if (!isset($groupedById[$id])) {
            $groupedById[$id] = 0;
        }
        $groupedById[$id]++;
    }
    
    echo "\nAnálisis por ID:\n";
    foreach ($groupedById as $id => $count) {
        echo "  Ticket #{$id}: aparece {$count} veces\n";
        if ($count > 1) {
            echo "    ❌ DUPLICADO/TRIPLICADO\n";
        }
    }
    
    // 2. Solución: usar array_unique por ID
    echo "\n2. Solución con array_unique:\n";
    
    $uniqueTickets = [];
    $seenIds = [];
    
    foreach ($currentQuery as $ticket) {
        $id = $ticket['id'];
        if (!in_array($id, $seenIds)) {
            $uniqueTickets[] = $ticket;
            $seenIds[] = $id;
        }
    }
    
    echo "Resultados únicos: " . count($uniqueTickets) . "\n";
    echo "\nTickets únicos encontrados:\n";
    foreach ($uniqueTickets as $ticket) {
        echo "  - Ticket #{$ticket['id']}: {$ticket['client_name']} - {$ticket['scheduled_date']} {$ticket['scheduled_time']}\n";
    }
    
    // 3. Verificar si hay datos realmente duplicados en la DB
    echo "\n3. Verificando duplicados reales en base de datos:\n";
    
    $realDuplicates = $db->select("
        SELECT t.id, COUNT(*) as count
        FROM tickets t
        WHERE t.assigned_to = ?
        GROUP BY t.id
        HAVING COUNT(*) > 1
    ", [$technicianId]);
    
    if (count($realDuplicates) > 0) {
        echo "❌ HAY REGISTROS DUPLICADOS REALES:\n";
        foreach ($realDuplicates as $dup) {
            echo "  - Ticket ID {$dup['id']}: {$dup['count']} registros\n";
        }
    } else {
        echo "✅ No hay duplicados reales. El problema es en el JOIN.\n";
    }
    
    // 4. Probar consulta alternativa
    echo "\n4. Consulta alternativa (más restrictiva):\n";
    
    $alternativeQuery = $db->select("
        SELECT DISTINCT t.id, 
               MAX(t.description) as description, 
               MAX(t.status) as status, 
               MAX(t.scheduled_date) as scheduled_date, 
               MAX(t.scheduled_time) as scheduled_time, 
               MAX(t.security_code) as security_code,
               MAX(c.name) as client_name, 
               MAX(c.business_name) as business_name, 
               MAX(c.address) as address, 
               MAX(c.latitude) as latitude, 
               MAX(c.longitude) as longitude
        FROM tickets t
        LEFT JOIN clients c ON t.client_id = c.id
        WHERE t.assigned_to = ? 
        AND t.scheduled_date IS NOT NULL 
        AND t.scheduled_date >= CURDATE()
        GROUP BY t.id
        ORDER BY MAX(t.scheduled_date), MAX(t.scheduled_time)
        LIMIT 5
    ", [$technicianId]);
    
    echo "Resultados consulta alternativa: " . count($alternativeQuery) . "\n";
    
    echo "\n=== RECOMENDACIÓN ===\n";
    echo "Usar array_unique en PHP es la solución más segura.\n";
    echo "Modificar dashboard para eliminar duplicados después de la consulta.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
