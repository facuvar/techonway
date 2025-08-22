<?php
/**
 * Fix de emergencia para el dashboard técnico
 * Revierte a una solución más simple y segura
 */

require_once 'includes/init.php';

echo "<h1>🚨 Fix de Emergencia Dashboard</h1>";

// Verificar que estamos en Railway
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    echo "<p style='color: red;'>❌ Este script es para Railway. Usa: https://demo.techonway.com/emergency_fix_dashboard.php</p>";
    exit;
}

echo "<p>🔧 Diagnosticando el problema...</p>";

try {
    $db = Database::getInstance();
    
    // Test 1: Verificar conexión básica
    echo "<h2>1. Test de Conexión</h2>";
    $testConnection = $db->selectOne("SELECT 1 as test");
    echo "<p>✅ Conexión a base de datos: OK</p>";
    
    // Test 2: Verificar campo assigned_to
    echo "<h2>2. Verificar Estructura</h2>";
    $columns = $db->select("SHOW COLUMNS FROM tickets");
    $hasAssignedTo = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'assigned_to') {
            $hasAssignedTo = true;
            break;
        }
    }
    echo "<p>✅ Campo assigned_to: " . ($hasAssignedTo ? "Existe" : "NO EXISTE") . "</p>";
    
    // Test 3: Consulta simple de tickets
    echo "<h2>3. Test de Consulta Simple</h2>";
    try {
        $simpleTest = $db->select("
            SELECT COUNT(*) as total 
            FROM tickets 
            WHERE assigned_to IS NOT NULL
        ");
        echo "<p>✅ Consulta básica: " . $simpleTest[0]['total'] . " tickets con técnico</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error consulta básica: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Probar consulta problemática
    echo "<h2>4. Test de Consulta Dashboard</h2>";
    try {
        // Consulta segura sin GROUP BY
        $dashboardTest = $db->select("
            SELECT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time,
                   c.name as client_name
            FROM tickets t
            LEFT JOIN clients c ON t.client_id = c.id
            WHERE t.assigned_to IS NOT NULL 
            AND t.scheduled_date IS NOT NULL 
            AND t.scheduled_date >= CURDATE()
            LIMIT 3
        ");
        echo "<p>✅ Consulta dashboard: " . count($dashboardTest) . " citas encontradas</p>";
        
        if (count($dashboardTest) > 0) {
            echo "<ul>";
            foreach ($dashboardTest as $item) {
                echo "<li>Ticket #{$item['id']}: {$item['client_name']} - {$item['scheduled_date']}</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error consulta dashboard: " . $e->getMessage() . "</p>";
        echo "<p>Detalle: " . $e->getFile() . " línea " . $e->getLine() . "</p>";
    }
    
    echo "<h2>5. ✅ Diagnóstico Completado</h2>";
    echo "<p>Si ves este mensaje, la base de datos funciona correctamente.</p>";
    echo "<p>El problema puede estar en el código PHP del dashboard.</p>";
    echo "<p><a href='/technician/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none;'>🔄 Probar Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error Crítico</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "<p>Traza:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
