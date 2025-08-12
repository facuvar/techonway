<?php
/**
 * Diagnóstico específico para Dashboard - Debug error 500
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Dashboard Debug - TechonWay</h1>";

try {
    // Cargar init
    require_once 'includes/init.php';
    echo "✅ Init loaded successfully<br>";

    // Test de autenticación
    echo "<h2>👤 Auth Test</h2>";
    echo "Auth object: " . (isset($auth) ? "✅ Created" : "❌ Missing") . "<br>";
    
    if (isset($auth)) {
        echo "User logged: " . ($auth->isLoggedIn() ? "✅ Yes" : "❌ No") . "<br>";
        if ($auth->isLoggedIn()) {
            echo "User role: " . ($auth->isAdmin() ? "Admin" : ($auth->isTechnician() ? "Technician" : "Unknown")) . "<br>";
        }
    }

    // Test de base de datos
    echo "<h2>🗄️ Database Connection Test</h2>";
    $db = Database::getInstance();
    echo "Database instance: ✅ Created<br>";
    
    // Test básico de consulta
    echo "<h3>📋 Table Existence Check</h3>";
    $tables_to_check = ['users', 'clients', 'tickets', 'visits'];
    
    foreach ($tables_to_check as $table) {
        try {
            $result = $db->selectOne("SHOW TABLES LIKE '{$table}'");
            echo "<strong>{$table}:</strong> " . ($result ? "✅ Exists" : "❌ Missing") . "<br>";
        } catch (Exception $e) {
            echo "<strong>{$table}:</strong> ❌ Error: " . $e->getMessage() . "<br>";
        }
    }

    // Test de consultas del dashboard
    echo "<h3>📊 Dashboard Queries Test</h3>";
    
    try {
        $clientsCount = $db->selectOne("SELECT COUNT(*) as count FROM clients");
        echo "<strong>Clients count query:</strong> ✅ Success - Count: " . ($clientsCount['count'] ?? 'NULL') . "<br>";
    } catch (Exception $e) {
        echo "<strong>Clients count query:</strong> ❌ Error: " . $e->getMessage() . "<br>";
    }

    try {
        $techniciansCount = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'technician'");
        echo "<strong>Technicians count query:</strong> ✅ Success - Count: " . ($techniciansCount['count'] ?? 'NULL') . "<br>";
    } catch (Exception $e) {
        echo "<strong>Technicians count query:</strong> ❌ Error: " . $e->getMessage() . "<br>";
    }

    try {
        $ticketsCount = $db->selectOne("SELECT COUNT(*) as count FROM tickets");
        echo "<strong>Tickets count query:</strong> ✅ Success - Count: " . ($ticketsCount['count'] ?? 'NULL') . "<br>";
    } catch (Exception $e) {
        echo "<strong>Tickets count query:</strong> ❌ Error: " . $e->getMessage() . "<br>";
    }

    echo "<h3>🔧 Create Missing Tables</h3>";
    echo "<a href='/import_database.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🗄️ Import Database Schema</a><br><br>";

    echo "<h3>🔗 Test Links</h3>";
    echo "<a href='/admin/dashboard.php'>🎯 Try Dashboard Again</a><br>";
    echo "<a href='/login.php'>🔐 Back to Login</a><br>";

} catch (Exception $e) {
    echo "<h2>❌ Critical Error</h2>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " (Line " . $e->getLine() . ")<br>";
    echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
