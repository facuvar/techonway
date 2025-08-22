<?php
/**
 * Script para sincronizar campos technician_id y assigned_to en la tabla tickets
 * 
 * Este script resuelve las inconsistencias entre estos dos campos que pueden
 * estar causando duplicados en el dashboard del técnico.
 */

require_once 'includes/init.php';

// Obtener conexión a la base de datos
$db = Database::getInstance();
$pdo = $db->getConnection();

echo "=== SINCRONIZACIÓN DE CAMPOS TÉCNICO ===\n\n";

try {
    // 1. Verificar la estructura actual
    echo "1. Verificando estructura de la tabla tickets...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_ASSOC);
    
    $hasTechnicianId = false;
    $hasAssignedTo = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'technician_id') {
            $hasTechnicianId = true;
            echo "   ✓ Campo technician_id encontrado\n";
        }
        if ($column['Field'] === 'assigned_to') {
            $hasAssignedTo = true;
            echo "   ✓ Campo assigned_to encontrado\n";
        }
    }
    
    if (!$hasTechnicianId && !$hasAssignedTo) {
        echo "   ❌ ERROR: No se encontraron campos de técnico en la tabla\n";
        exit(1);
    }
    
    // 2. Verificar inconsistencias
    echo "\n2. Verificando inconsistencias...\n";
    
    if ($hasTechnicianId && $hasAssignedTo) {
        // Verificar registros donde los campos no coinciden
        $inconsistentTickets = $pdo->query("
            SELECT id, technician_id, assigned_to 
            FROM tickets 
            WHERE technician_id != assigned_to OR 
                  (technician_id IS NULL AND assigned_to IS NOT NULL) OR
                  (technician_id IS NOT NULL AND assigned_to IS NULL)
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Tickets con inconsistencias: " . count($inconsistentTickets) . "\n";
        
        if (count($inconsistentTickets) > 0) {
            echo "   Registros inconsistentes:\n";
            foreach ($inconsistentTickets as $ticket) {
                echo "     - Ticket #{$ticket['id']}: technician_id={$ticket['technician_id']}, assigned_to={$ticket['assigned_to']}\n";
            }
            
            // 3. Sincronizar datos
            echo "\n3. Sincronizando datos...\n";
            echo "   Usando assigned_to como campo principal...\n";
            
            $syncedCount = 0;
            
            foreach ($inconsistentTickets as $ticket) {
                $targetValue = $ticket['assigned_to'] ?: $ticket['technician_id'];
                
                if ($targetValue) {
                    $stmt = $pdo->prepare("
                        UPDATE tickets 
                        SET technician_id = ?, assigned_to = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$targetValue, $targetValue, $ticket['id']]);
                    $syncedCount++;
                    echo "     ✓ Ticket #{$ticket['id']} sincronizado con técnico ID: {$targetValue}\n";
                }
            }
            
            echo "   Total sincronizados: {$syncedCount}\n";
        } else {
            echo "   ✓ No se encontraron inconsistencias\n";
        }
    } elseif ($hasTechnicianId && !$hasAssignedTo) {
        echo "   Solo existe technician_id. Creando campo assigned_to...\n";
        
        $pdo->exec("ALTER TABLE tickets ADD COLUMN assigned_to INT NULL AFTER technician_id");
        $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
        
        echo "   ✓ Campo assigned_to creado y sincronizado\n";
    } elseif (!$hasTechnicianId && $hasAssignedTo) {
        echo "   Solo existe assigned_to. Creando campo technician_id...\n";
        
        $pdo->exec("ALTER TABLE tickets ADD COLUMN technician_id INT NOT NULL AFTER client_id");
        $pdo->exec("UPDATE tickets SET technician_id = assigned_to WHERE assigned_to IS NOT NULL");
        
        echo "   ✓ Campo technician_id creado y sincronizado\n";
    }
    
    // 4. Verificar duplicados potenciales
    echo "\n4. Verificando tickets duplicados...\n";
    
    $duplicateTickets = $pdo->query("
        SELECT assigned_to, client_id, description, COUNT(*) as count
        FROM tickets 
        GROUP BY assigned_to, client_id, description
        HAVING count > 1
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Grupos de tickets potencialmente duplicados: " . count($duplicateTickets) . "\n";
    
    if (count($duplicateTickets) > 0) {
        foreach ($duplicateTickets as $group) {
            echo "     - Técnico {$group['assigned_to']}, Cliente {$group['client_id']}: {$group['count']} tickets similares\n";
        }
    }
    
    // 5. Estadísticas finales
    echo "\n5. Estadísticas finales...\n";
    
    $totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    echo "   Total de tickets: {$totalTickets}\n";
    
    $ticketsWithTechnician = $pdo->query("SELECT COUNT(*) FROM tickets WHERE assigned_to IS NOT NULL")->fetchColumn();
    echo "   Tickets con técnico asignado: {$ticketsWithTechnician}\n";
    
    $ticketsWithoutTechnician = $totalTickets - $ticketsWithTechnician;
    echo "   Tickets sin técnico: {$ticketsWithoutTechnician}\n";
    
    echo "\n✅ Sincronización completada exitosamente!\n";
    echo "\nRecomendaciones:\n";
    echo "- Usar 'assigned_to' como campo principal en todas las consultas\n";
    echo "- Mantener 'technician_id' como compatibilidad hasta migrar completamente\n";
    echo "- Ejecutar debug_duplicates.php para verificar que no hay más duplicados\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}
?>
