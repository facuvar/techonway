<?php
/**
 * Script para forzar la sincronización en Railway
 * Accede a este archivo directamente desde el navegador en Railway
 */

echo "<h1>🔧 Sincronización Forzada Railway</h1>";

// Verificar entorno
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    echo "<p style='color: red;'>❌ Este script solo funciona en Railway, no en local</p>";
    echo "<p>URL correcta: https://demo.techonway.com/force_railway_sync.php</p>";
    exit;
}

echo "<p>🚀 Ejecutando en Railway...</p>";

try {
    require_once __DIR__ . '/includes/init.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>1. Verificando estructura actual</h2>";
    
    // Verificar columnas
    $columns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
    
    $hasAssignedTo = in_array('assigned_to', $columns);
    $hasTechnicianId = in_array('technician_id', $columns);
    
    echo "<p>✓ technician_id: " . ($hasTechnicianId ? "Existe" : "NO EXISTE") . "</p>";
    echo "<p>✓ assigned_to: " . ($hasAssignedTo ? "Existe" : "NO EXISTE") . "</p>";
    
    if (!$hasAssignedTo) {
        echo "<h2>2. Creando campo assigned_to</h2>";
        
        $pdo->exec("ALTER TABLE tickets ADD COLUMN assigned_to INT NULL AFTER technician_id");
        echo "<p style='color: green;'>✅ Campo assigned_to creado</p>";
        
        $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
        echo "<p style='color: green;'>✅ Datos sincronizados</p>";
    } else {
        echo "<h2>2. Verificando sincronización</h2>";
        
        $unsyncedCount = $pdo->query("
            SELECT COUNT(*) 
            FROM tickets 
            WHERE (technician_id IS NULL AND assigned_to IS NOT NULL) 
               OR (technician_id IS NOT NULL AND assigned_to IS NULL)
               OR (technician_id != assigned_to)
        ")->fetchColumn();
        
        if ($unsyncedCount > 0) {
            echo "<p style='color: orange;'>⚠️ Encontrados {$unsyncedCount} tickets desincronizados</p>";
            
            $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
            echo "<p style='color: green;'>✅ {$unsyncedCount} tickets re-sincronizados</p>";
        } else {
            echo "<p style='color: green;'>✅ Todos los tickets están sincronizados</p>";
        }
    }
    
    echo "<h2>3. Probando consultas del dashboard</h2>";
    
    // Test con técnico que tenga tickets
    $techniciansWithTickets = $pdo->query("
        SELECT assigned_to, COUNT(*) as count 
        FROM tickets 
        WHERE assigned_to IS NOT NULL 
        GROUP BY assigned_to 
        ORDER BY count DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Técnicos con tickets:</p><ul>";
    foreach ($techniciansWithTickets as $tech) {
        echo "<li>Técnico {$tech['assigned_to']}: {$tech['count']} tickets</li>";
    }
    echo "</ul>";
    
    if (count($techniciansWithTickets) > 0) {
        $techId = $techniciansWithTickets[0]['assigned_to'];
        
        // Consulta anterior (problematica)
        $oldQuery = $pdo->query("
            SELECT DISTINCT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
                   c.name as client_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            WHERE t.technician_id = {$techId}
            AND t.scheduled_date IS NOT NULL 
            AND t.scheduled_date >= CURDATE()
            ORDER BY t.scheduled_date, t.scheduled_time
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Consulta nueva (corregida)
        $newQuery = $pdo->query("
            SELECT DISTINCT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
                   c.name as client_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            WHERE t.assigned_to = {$techId}
            AND t.scheduled_date IS NOT NULL 
            AND t.scheduled_date >= CURDATE()
            ORDER BY t.scheduled_date, t.scheduled_time
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Técnico {$techId}:</strong></p>";
        echo "<p>• Consulta anterior (technician_id): " . count($oldQuery) . " citas</p>";
        echo "<p>• Consulta nueva (assigned_to): " . count($newQuery) . " citas</p>";
        
        if (count($oldQuery) === count($newQuery)) {
            echo "<p style='color: green;'>✅ Sin duplicados detectados</p>";
        } else {
            echo "<p style='color: red;'>❌ Diferencia detectada - revisar consultas del dashboard</p>";
        }
    }
    
    echo "<h2>4. ✅ Sincronización completada</h2>";
    echo "<p style='color: green; font-weight: bold;'>Recarga el dashboard del técnico para ver los cambios</p>";
    echo "<p><a href='/technician/dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Ir al Dashboard Técnico</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
?>
