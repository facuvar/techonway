<?php
/**
 * Test para verificar si las páginas locales funcionan después de la migración
 */

require_once 'includes/init.php';

echo "=== TEST DE PÁGINAS LOCALES ===\n\n";

$db = Database::getInstance();

try {
    // Test 1: Verificar columnas faltantes
    echo "1. Verificando columnas:\n";
    
    $userCols = $db->select("SHOW COLUMNS FROM users");
    $hasLastName = false;
    foreach ($userCols as $col) {
        if ($col['Field'] === 'last_name') {
            $hasLastName = true;
            break;
        }
    }
    echo "   users.last_name: " . ($hasLastName ? "✅ Existe" : "❌ Falta") . "\n";
    
    $ticketCols = $db->select("SHOW COLUMNS FROM tickets");
    $hasPriority = false;
    foreach ($ticketCols as $col) {
        if ($col['Field'] === 'priority') {
            $hasPriority = true;
            break;
        }
    }
    echo "   tickets.priority: " . ($hasPriority ? "✅ Existe" : "❌ Falta") . "\n";
    
    // Test 2: Probar consulta que fallaba en tickets.php
    echo "\n2. Probando consulta de admin/tickets.php:\n";
    try {
        $technicians = $db->select("
            SELECT id, name, COALESCE(last_name, '') as last_name, email 
            FROM users 
            WHERE role = 'technician' 
            LIMIT 3
        ");
        echo "   ✅ Consulta de técnicos: " . count($technicians) . " resultados\n";
    } catch (Exception $e) {
        echo "   ❌ Error en consulta de técnicos: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Probar consulta que fallaba en calendar.php
    echo "\n3. Probando consulta de admin/calendar.php:\n";
    try {
        $appointments = $db->select("
            SELECT t.id, t.priority, t.scheduled_date, t.scheduled_time, t.description,
                   c.name as client_name, c.address,
                   CONCAT(u.name, ' ', COALESCE(u.last_name, '')) as technician_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.scheduled_date IS NOT NULL
            LIMIT 3
        ");
        echo "   ✅ Consulta de calendario: " . count($appointments) . " resultados\n";
    } catch (Exception $e) {
        echo "   ❌ Error en consulta de calendario: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Verificar duplicados en dashboard técnico
    echo "\n4. Verificando duplicados en dashboard:\n";
    try {
        $technicianTickets = $db->select("
            SELECT DISTINCT t.id, t.description, t.status,
                   c.name as client_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            WHERE t.assigned_to = 5
            LIMIT 5
        ");
        echo "   ✅ Dashboard técnico: " . count($technicianTickets) . " tickets únicos\n";
    } catch (Exception $e) {
        echo "   ❌ Error en dashboard: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== RESULTADO ===\n";
    if ($hasLastName && $hasPriority) {
        echo "✅ Base de datos local sincronizada correctamente\n";
        echo "Puedes probar las páginas ahora:\n";
        echo "- http://localhost/sistema-techonway/admin/tickets.php\n";
        echo "- http://localhost/sistema-techonway/admin/calendar.php\n";
    } else {
        echo "❌ Faltan columnas. Ejecuta: php migrate_local_db.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
