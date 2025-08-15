<?php
/**
 * Script para agregar columnas faltantes a la tabla clients en Railway
 */

require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>🔧 Reparando tabla CLIENTS en Railway</h1>";
    
    // Verificar estructura actual
    echo "<h2>📋 Estructura actual de clients:</h2>";
    $currentColumns = $db->select("DESCRIBE clients");
    
    $existingColumns = [];
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($currentColumns as $column) {
        $existingColumns[] = $column['Field'];
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Key'] ?? '') . "</td>";
        echo "<td>" . ($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🔧 Agregando columnas faltantes:</h2>";
    
    // Lista de columnas que deberían existir
    $requiredColumns = [
        'email' => "VARCHAR(255) NULL",
        'phone' => "VARCHAR(50) NULL", 
        'business_name' => "VARCHAR(255) NULL",
        'address' => "TEXT NULL",
        'zone' => "VARCHAR(100) NULL"
    ];
    
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            echo "<p>➕ Agregando columna: <strong>$columnName</strong></p>";
            
            $sql = "ALTER TABLE clients ADD COLUMN $columnName $columnDefinition";
            $db->query($sql);
            
            echo "<p style='color: green;'>✅ Columna $columnName agregada exitosamente</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna $columnName ya existe</p>";
        }
    }
    
    echo "<h2>📊 Verificando estructura final:</h2>";
    $finalColumns = $db->select("DESCRIBE clients");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($finalColumns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Key'] ?? '') . "</td>";
        echo "<td>" . ($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🧪 Probando consulta de email:</h2>";
    
    // Probar la consulta que estaba fallando
    try {
        $testQuery = $db->selectOne("SELECT id, name, email FROM clients WHERE id = 2666");
        if ($testQuery) {
            echo "<p style='color: green;'>✅ Query de email funciona correctamente</p>";
            echo "<p>Cliente encontrado: " . htmlspecialchars($testQuery['name']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($testQuery['email'] ?? 'Sin email') . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Cliente ID 2666 no encontrado, pero la query funciona</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en query de email: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎉 Reparación completada</h2>";
    echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🔗 Ir a Clientes</a></p>";
    echo "<p><a href='/admin/clients.php?action=edit&id=2666' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🔗 Editar Cliente 2666</a></p>";
    
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
    max-width: 1000px; 
    margin: 20px auto; 
    padding: 20px; 
    background: #f8f9fa;
}
h1 { color: #2D3142; }
h2 { color: #5B6386; }
table { 
    width: 100%; 
    background: white; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th { 
    background: #2D3142; 
    color: white; 
    padding: 10px; 
}
td { 
    padding: 8px; 
    border-bottom: 1px solid #ddd;
}
p { 
    margin: 10px 0; 
    padding: 5px;
}
</style>
