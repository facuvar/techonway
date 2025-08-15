<?php
/**
 * Script para restaurar clientes desde la base de datos local
 * También asegura que tengan los campos latitude y longitude
 */

require_once 'includes/Database.php';

try {
    echo "<h1>🔄 Restaurando clientes desde local</h1>";
    
    // Conexión a base de datos local
    $localHost = 'localhost';
    $localDb = 'techonway_db';
    $localUser = 'root';
    $localPass = '';
    
    echo "<p>📡 Conectando a base de datos local...</p>";
    
    $localPdo = new PDO("mysql:host=$localHost;dbname=$localDb;charset=utf8", $localUser, $localPass);
    $localPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Conectado a base de datos local</p>";
    
    // Conexión a Railway (actual)
    $db = Database::getInstance();
    
    echo "<p>🚄 Conectando a Railway...</p>";
    echo "<p style='color: green;'>✅ Conectado a Railway</p>";
    
    // Verificar estructura de tabla clients en Railway
    echo "<h2>🔍 Verificando estructura de tabla clients</h2>";
    
    $columns = $db->select("SHOW COLUMNS FROM clients");
    $columnNames = array_column($columns, 'Field');
    
    echo "<p>Columnas actuales: " . implode(', ', $columnNames) . "</p>";
    
    // Agregar columnas faltantes si no existen
    if (!in_array('latitude', $columnNames)) {
        echo "<p>➕ Agregando columna latitude...</p>";
        $db->query("ALTER TABLE clients ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER address");
    }
    
    if (!in_array('longitude', $columnNames)) {
        echo "<p>➕ Agregando columna longitude...</p>";
        $db->query("ALTER TABLE clients ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
    }
    
    if (!in_array('zone', $columnNames)) {
        echo "<p>➕ Agregando columna zone...</p>";
        $db->query("ALTER TABLE clients ADD COLUMN zone VARCHAR(100) NULL AFTER longitude");
    }
    
    echo "<p style='color: green;'>✅ Estructura de tabla verificada</p>";
    
    // Obtener clientes desde local
    echo "<h2>📋 Obteniendo clientes desde local</h2>";
    
    $localClients = $localPdo->query("
        SELECT id, name, email, phone, business_name, address, latitude, longitude, zone, created_at 
        FROM clients 
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>📊 Encontrados " . count($localClients) . " clientes en local</p>";
    
    if (empty($localClients)) {
        echo "<p style='color: orange;'>⚠️ No hay clientes en la base local</p>";
        exit;
    }
    
    // Limpiar tabla clients en Railway
    echo "<h2>🧹 Limpiando tabla clients en Railway</h2>";
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    $db->query("DELETE FROM clients");
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p style='color: green;'>✅ Tabla clients limpiada</p>";
    
    // Insertar clientes desde local
    echo "<h2>📥 Insertando clientes...</h2>";
    
    $insertedCount = 0;
    
    foreach ($localClients as $client) {
        echo "<p>👤 Insertando: " . htmlspecialchars($client['name']) . "</p>";
        
        $db->query("
            INSERT INTO clients (id, name, email, phone, business_name, address, latitude, longitude, zone, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $client['id'],
            $client['name'],
            $client['email'],
            $client['phone'],
            $client['business_name'],
            $client['address'],
            $client['latitude'],
            $client['longitude'],
            $client['zone'],
            $client['created_at']
        ]);
        
        $insertedCount++;
    }
    
    echo "<h2>🎉 ¡Restauración completada!</h2>";
    echo "<p style='color: green; font-size: 1.2em;'>✅ Insertados $insertedCount clientes</p>";
    
    // Verificar resultado
    $totalClients = $db->selectOne("SELECT COUNT(*) as count FROM clients");
    echo "<p><strong>👥 Total de clientes restaurados:</strong> " . $totalClients['count'] . "</p>";
    
    // Mostrar algunos ejemplos
    echo "<h3>📋 Muestra de clientes restaurados:</h3>";
    $sampleClients = $db->select("SELECT id, name, email, latitude, longitude FROM clients LIMIT 5");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2D3142; color: white;'>";
    echo "<th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Nombre</th><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Lat</th><th style='padding: 8px;'>Lng</th>";
    echo "</tr>";
    
    foreach ($sampleClients as $client) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $client['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($client['name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($client['email'] ?? 'Sin email') . "</td>";
        echo "<td style='padding: 8px;'>" . ($client['latitude'] ?? 'Sin lat') . "</td>";
        echo "<td style='padding: 8px;'>" . ($client['longitude'] ?? 'Sin lng') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🔗 Ver Clientes Restaurados</a></p>";
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
h1 { 
    color: #2D3142; 
    border-bottom: 3px solid #2D3142; 
    padding-bottom: 10px; 
}
h2 { 
    color: #5B6386; 
    border-left: 4px solid #5B6386; 
    padding-left: 10px; 
}
table { 
    background: white; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 10px 0;
}
th, td { 
    text-align: left; 
    border-bottom: 1px solid #ddd;
}
p { 
    margin: 8px 0; 
    line-height: 1.4;
}
</style>
