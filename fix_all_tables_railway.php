<?php
/**
 * Script para arreglar TODAS las tablas en Railway
 * Agrega columnas faltantes a clients y tickets
 */

require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>🔧 Reparando TODAS las tablas en Railway</h1>";
    
    // ==================== TABLA CLIENTS ====================
    echo "<h2>👥 Reparando tabla CLIENTS</h2>";
    
    $clientsColumns = $db->select("DESCRIBE clients");
    $existingClientsColumns = array_column($clientsColumns, 'Field');
    
    echo "<p>📋 Columnas actuales en clients: " . implode(', ', $existingClientsColumns) . "</p>";
    
    $requiredClientsColumns = [
        'email' => "VARCHAR(255) NULL",
        'phone' => "VARCHAR(50) NULL", 
        'business_name' => "VARCHAR(255) NULL",
        'address' => "TEXT NULL",
        'zone' => "VARCHAR(100) NULL"
    ];
    
    foreach ($requiredClientsColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingClientsColumns)) {
            echo "<p>➕ Agregando a clients: <strong>$columnName</strong></p>";
            $sql = "ALTER TABLE clients ADD COLUMN $columnName $columnDefinition";
            $db->query($sql);
            echo "<p style='color: green;'>✅ Columna clients.$columnName agregada</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna clients.$columnName ya existe</p>";
        }
    }
    
    // ==================== TABLA TICKETS ====================
    echo "<h2>🎫 Reparando tabla TICKETS</h2>";
    
    $ticketsColumns = $db->select("DESCRIBE tickets");
    $existingTicketsColumns = array_column($ticketsColumns, 'Field');
    
    echo "<p>📋 Columnas actuales en tickets: " . implode(', ', $existingTicketsColumns) . "</p>";
    
    $requiredTicketsColumns = [
        'assigned_to' => "INT NULL",
        'priority' => "ENUM('low', 'medium', 'high') DEFAULT 'medium'",
        'scheduled_date' => "DATE NULL",
        'scheduled_time' => "TIME NULL",
        'security_code' => "VARCHAR(10) NULL COMMENT 'Código de seguridad para citas'",
        'notes' => "TEXT NULL",
        'start_notes' => "TEXT NULL",
        'phone' => "VARCHAR(50) NULL"
    ];
    
    foreach ($requiredTicketsColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingTicketsColumns)) {
            echo "<p>➕ Agregando a tickets: <strong>$columnName</strong></p>";
            $sql = "ALTER TABLE tickets ADD COLUMN $columnName $columnDefinition";
            $db->query($sql);
            echo "<p style='color: green;'>✅ Columna tickets.$columnName agregada</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna tickets.$columnName ya existe</p>";
        }
    }
    
    // ==================== PRUEBAS ====================
    echo "<h2>🧪 Probando funcionalidad</h2>";
    
    // Probar consulta de clientes con email
    try {
        $testClient = $db->selectOne("SELECT id, name, email FROM clients LIMIT 1");
        if ($testClient) {
            echo "<p style='color: green;'>✅ Query de clients con email funciona</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en clients: " . $e->getMessage() . "</p>";
    }
    
    // Probar consulta de tickets con security_code
    try {
        $testTicket = $db->selectOne("SELECT id, security_code, scheduled_date FROM tickets LIMIT 1");
        echo "<p style='color: green;'>✅ Query de tickets con security_code funciona</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en tickets: " . $e->getMessage() . "</p>";
    }
    
    // Probar crear un código de seguridad
    function generateSecurityCode() {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    }
    
    $testCode = generateSecurityCode();
    echo "<p style='color: green;'>✅ Generación de código de seguridad funciona: <strong>$testCode</strong></p>";
    
    // ==================== RESUMEN FINAL ====================
    echo "<h2>📊 Estructura final de tablas</h2>";
    
    // Mostrar estructura final de clients
    echo "<h3>👥 CLIENTS</h3>";
    $finalClientsColumns = $db->select("DESCRIBE clients");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    foreach ($finalClientsColumns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar estructura final de tickets
    echo "<h3>🎫 TICKETS</h3>";
    $finalTicketsColumns = $db->select("DESCRIBE tickets");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    foreach ($finalTicketsColumns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 ¡Reparación completada!</h2>";
    echo "<p>✅ Tabla clients tiene todas las columnas necesarias</p>";
    echo "<p>✅ Tabla tickets tiene security_code y otras columnas</p>";
    echo "<p>✅ El código de seguridad se generará automáticamente para citas</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔗 Probar Clientes</a></p>";
    echo "<p><a href='/admin/tickets.php?action=create' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔗 Crear Ticket con Cita</a></p>";
    echo "<p><a href='/admin/clients.php?action=edit&id=2666' style='padding: 10px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px; margin: 5px;'>🔗 Editar Cliente 2666</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 1200px; 
    margin: 20px auto; 
    padding: 20px; 
    background: #f8f9fa;
}
h1 { color: #2D3142; border-bottom: 3px solid #2D3142; padding-bottom: 10px; }
h2 { color: #5B6386; border-left: 4px solid #5B6386; padding-left: 10px; }
h3 { color: #2D3142; }
table { 
    width: 100%; 
    background: white; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 10px 0;
}
th { 
    background: #2D3142; 
    color: white; 
    padding: 10px; 
    text-align: left;
}
td { 
    padding: 8px; 
    border-bottom: 1px solid #ddd;
}
p { 
    margin: 10px 0; 
    padding: 5px;
    line-height: 1.4;
}
a {
    display: inline-block;
    margin: 5px;
}
</style>
