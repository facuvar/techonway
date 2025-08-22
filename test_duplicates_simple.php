<?php
/**
 * Test simple para verificar duplicados
 */
require_once 'includes/init.php';

$db = Database::getInstance();
$technicianId = 5; // El técnico con más tickets en la base local

echo "=== TEST DE DUPLICADOS - TÉCNICO ID: {$technicianId} ===\n\n";

try {
    // 1. Verificar estructura de campos
    echo "1. Verificando campos en tabla tickets...\n";
    $columns = $db->select("SHOW COLUMNS FROM tickets");
    
    $hasAssignedTo = false;
    $hasTechnicianId = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'assigned_to') {
            $hasAssignedTo = true;
            echo "   ✓ Campo assigned_to: {$col['Type']}\n";
        }
        if ($col['Field'] === 'technician_id') {
            $hasTechnicianId = true;
            echo "   ✓ Campo technician_id: {$col['Type']}\n";
        }
    }
    
    if (!$hasAssignedTo) {
        echo "   ❌ Campo assigned_to NO EXISTE\n";
        exit(1);
    }
    
    // 2. Contar tickets con la consulta anterior (problematica)
    echo "\n2. Consulta anterior (con technician_id)...\n";
    $oldQuery = $db->select("
        SELECT t.id, t.description, t.status, t.created_at, 
               c.name as client_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        WHERE t.technician_id = ?
        ORDER BY t.id
    ", [$technicianId]);
    
    echo "   Resultados con technician_id: " . count($oldQuery) . "\n";
    
    // 3. Contar tickets con la consulta nueva (corregida)
    echo "\n3. Consulta nueva (con assigned_to)...\n";
    $newQuery = $db->select("
        SELECT DISTINCT t.id, t.description, t.status, t.created_at, 
               c.name as client_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        WHERE t.assigned_to = ?
        ORDER BY t.id
    ", [$technicianId]);
    
    echo "   Resultados con assigned_to + DISTINCT: " . count($newQuery) . "\n";
    
    // 4. Verificar si hay diferencia
    if (count($oldQuery) === count($newQuery)) {
        echo "\n✅ PERFECTO: No hay diferencia entre las consultas\n";
        echo "   Los duplicados se han resuelto!\n";
    } else {
        echo "\n⚠️ DIFERENCIA DETECTADA:\n";
        echo "   technician_id: " . count($oldQuery) . " resultados\n";
        echo "   assigned_to: " . count($newQuery) . " resultados\n";
    }
    
    // 5. Mostrar primeros resultados para verificar
    echo "\n4. Primeros 5 tickets del técnico:\n";
    for ($i = 0; $i < min(5, count($newQuery)); $i++) {
        $ticket = $newQuery[$i];
        echo "   - Ticket #{$ticket['id']}: {$ticket['client_name']} ({$ticket['status']})\n";
    }
    
    // 6. Verificar valores de ambos campos
    echo "\n5. Verificando sincronización de campos...\n";
    $syncCheck = $db->select("
        SELECT id, technician_id, assigned_to 
        FROM tickets 
        WHERE assigned_to = ? 
        LIMIT 5
    ", [$technicianId]);
    
    echo "   Verificación de sincronización:\n";
    foreach ($syncCheck as $ticket) {
        $syncStatus = ($ticket['technician_id'] == $ticket['assigned_to']) ? "✓" : "❌";
        echo "   {$syncStatus} Ticket #{$ticket['id']}: technician_id={$ticket['technician_id']}, assigned_to={$ticket['assigned_to']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
