<?php
/**
 * Script para importar clientes desde archivo JSON en Railway
 * Ejecutar después de subir el archivo JSON generado por export_clients_local.php
 */

require_once 'includes/Database.php';

try {
    echo "<h1>📥 Importando clientes desde JSON</h1>";
    
    // Buscar archivos JSON de clientes
    echo "<h2>🔍 Buscando archivos JSON...</h2>";
    
    $jsonFiles = glob('clients_export_*.json');
    
    if (empty($jsonFiles)) {
        echo "<p style='color: red;'>❌ No se encontraron archivos JSON de clientes</p>";
        echo "<p>Asegúrate de haber subido el archivo generado por <strong>export_clients_local.php</strong></p>";
        echo "<p>El archivo debe tener un nombre como: <code>clients_export_2024-01-15_14-30-45.json</code></p>";
        exit;
    }
    
    // Usar el archivo más reciente
    rsort($jsonFiles);
    $jsonFile = $jsonFiles[0];
    
    echo "<p style='color: green;'>✅ Encontrado: <strong>$jsonFile</strong></p>";
    echo "<p>📁 Tamaño: " . number_format(filesize($jsonFile) / 1024, 2) . " KB</p>";
    
    // Leer archivo JSON
    echo "<h2>📖 Leyendo archivo JSON...</h2>";
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (!$data) {
        echo "<p style='color: red;'>❌ Error al leer archivo JSON</p>";
        exit;
    }
    
    echo "<p>📊 Datos del export:</p>";
    echo "<ul>";
    echo "<li><strong>Fecha de export:</strong> " . $data['exported_at'] . "</li>";
    echo "<li><strong>Total de clientes:</strong> " . $data['total_clients'] . "</li>";
    echo "<li><strong>Columnas:</strong> " . implode(', ', $data['columns']) . "</li>";
    echo "</ul>";
    
    $clients = $data['clients'];
    $columns = $data['columns'];
    
    if (empty($clients)) {
        echo "<p style='color: orange;'>⚠️ No hay clientes en el archivo JSON</p>";
        exit;
    }
    
    // Conexión a Railway
    echo "<h2>🚄 Conectando a Railway...</h2>";
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Conectado a Railway</p>";
    
    // Verificar estructura de tabla clients en Railway
    echo "<h2>🔧 Verificando estructura de tabla clients</h2>";
    
    $existingColumns = $db->select("SHOW COLUMNS FROM clients");
    $existingColumnNames = array_column($existingColumns, 'Field');
    
    echo "<p>Columnas actuales en Railway: " . implode(', ', $existingColumnNames) . "</p>";
    
    // Agregar columnas faltantes
    $columnsToAdd = [
        'latitude' => 'DECIMAL(10, 8) NULL',
        'longitude' => 'DECIMAL(11, 8) NULL',
        'zone' => 'VARCHAR(100) NULL'
    ];
    
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $existingColumnNames)) {
            echo "<p>➕ Agregando columna $column...</p>";
            $db->query("ALTER TABLE clients ADD COLUMN $column $definition");
        } else {
            echo "<p>✅ Columna $column ya existe</p>";
        }
    }
    
    // Limpiar tabla clients
    echo "<h2>🧹 Limpiando tabla clients...</h2>";
    $currentCount = $db->selectOne("SELECT COUNT(*) as count FROM clients")['count'];
    echo "<p>📊 Clientes actuales: $currentCount</p>";
    
    if ($currentCount > 0) {
        echo "<p>🗑️ Eliminando clientes existentes...</p>";
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->query("DELETE FROM clients");
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        echo "<p style='color: green;'>✅ Tabla limpiada</p>";
    }
    
    // Preparar columnas para INSERT
    $insertColumns = [];
    $placeholders = [];
    
    foreach ($columns as $col) {
        if (in_array($col, $existingColumnNames) || in_array($col, array_keys($columnsToAdd))) {
            $insertColumns[] = $col;
            $placeholders[] = '?';
        }
    }
    
    $insertSQL = "INSERT INTO clients (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    echo "<p>📝 SQL Insert: <code>$insertSQL</code></p>";
    
    // Insertar clientes
    echo "<h2>📥 Insertando clientes...</h2>";
    
    $insertedCount = 0;
    $errors = [];
    
    foreach ($clients as $client) {
        try {
            $values = [];
            foreach ($insertColumns as $col) {
                $values[] = $client[$col] ?? null;
            }
            
            $db->query($insertSQL, $values);
            $insertedCount++;
            
            if ($insertedCount % 50 == 0) {
                echo "<p>📊 Insertados: $insertedCount / " . count($clients) . "</p>";
            }
            
        } catch (Exception $e) {
            $errors[] = "Cliente ID " . ($client['id'] ?? 'N/A') . ": " . $e->getMessage();
            if (count($errors) < 5) { // Solo mostrar primeros 5 errores
                echo "<p style='color: orange;'>⚠️ Error en cliente: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h2>🎉 ¡Importación completada!</h2>";
    echo "<p style='color: green; font-size: 1.2em;'>✅ Insertados: $insertedCount clientes</p>";
    
    if (!empty($errors)) {
        echo "<p style='color: orange;'>⚠️ Errores: " . count($errors) . "</p>";
    }
    
    // Verificar resultado
    echo "<h2>📊 Verificación final</h2>";
    
    $finalCount = $db->selectOne("SELECT COUNT(*) as count FROM clients")['count'];
    echo "<p><strong>👥 Total de clientes:</strong> $finalCount</p>";
    
    // Verificar clientes con coordenadas
    $withCoords = $db->selectOne("SELECT COUNT(*) as count FROM clients WHERE latitude IS NOT NULL AND longitude IS NOT NULL")['count'];
    echo "<p><strong>🗺️ Clientes con coordenadas:</strong> $withCoords</p>";
    
    // Mostrar muestra
    echo "<h3>📋 Muestra de clientes importados:</h3>";
    $sampleClients = $db->select("SELECT id, name, email, latitude, longitude, zone FROM clients LIMIT 5");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2D3142; color: white;'>";
    echo "<th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Nombre</th><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Lat</th><th style='padding: 8px;'>Lng</th><th style='padding: 8px;'>Zona</th>";
    echo "</tr>";
    
    foreach ($sampleClients as $client) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $client['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($client['name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($client['email'] ?? 'Sin email') . "</td>";
        echo "<td style='padding: 8px;'>" . ($client['latitude'] ?? 'Sin lat') . "</td>";
        echo "<td style='padding: 8px;'>" . ($client['longitude'] ?? 'Sin lng') . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($client['zone'] ?? 'Sin zona') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Limpiar archivo JSON
    echo "<h2>🧹 Limpieza</h2>";
    echo "<p>🗑️ Eliminando archivo JSON temporal...</p>";
    unlink($jsonFile);
    echo "<p style='color: green;'>✅ Archivo $jsonFile eliminado</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🔗 Ver Clientes Restaurados</a></p>";
    echo "<p><a href='/admin/visits.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🗺️ Ver Mapa de Visitas</a></p>";
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
code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
