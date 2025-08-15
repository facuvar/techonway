<?php
/**
 * Script para exportar clientes desde la base de datos local
 * Ejecutar este script EN LOCAL para generar un archivo JSON
 */

try {
    echo "<h1>📤 Exportando clientes desde local</h1>";
    
    // Usar configuración local existente
    require_once 'config/database.php';
    $dbConfig = require 'config/database.php';
    
    echo "<p>📡 Conectando a base de datos local...</p>";
    echo "<p>🔧 Base de datos: " . $dbConfig['dbname'] . "</p>";
    
    $localPdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $localPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Conectado a base de datos local</p>";
    
    // Obtener estructura de la tabla clients
    echo "<h2>🔍 Verificando estructura de tabla clients</h2>";
    
    $columns = $localPdo->query("SHOW COLUMNS FROM clients")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    echo "<p>Columnas disponibles: " . implode(', ', $columnNames) . "</p>";
    
    // Construir SELECT dinámico basado en columnas disponibles
    $selectColumns = [];
    $possibleColumns = ['id', 'name', 'email', 'phone', 'business_name', 'address', 'latitude', 'longitude', 'zone', 'created_at', 'updated_at'];
    
    foreach ($possibleColumns as $col) {
        if (in_array($col, $columnNames)) {
            $selectColumns[] = $col;
        }
    }
    
    $selectSQL = "SELECT " . implode(', ', $selectColumns) . " FROM clients ORDER BY id";
    
    echo "<p>📋 SQL Query: <code>$selectSQL</code></p>";
    
    // Obtener clientes
    echo "<h2>📋 Obteniendo clientes...</h2>";
    
    $clients = $localPdo->query($selectSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>📊 Encontrados " . count($clients) . " clientes</p>";
    
    if (empty($clients)) {
        echo "<p style='color: orange;'>⚠️ No hay clientes en la base local</p>";
        exit;
    }
    
    // Mostrar muestra
    echo "<h3>📋 Muestra de primeros 3 clientes:</h3>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2D3142; color: white;'>";
    foreach ($selectColumns as $col) {
        echo "<th style='padding: 8px;'>" . ucfirst($col) . "</th>";
    }
    echo "</tr>";
    
    for ($i = 0; $i < min(3, count($clients)); $i++) {
        echo "<tr>";
        foreach ($selectColumns as $col) {
            $value = $clients[$i][$col] ?? 'NULL';
            if (strlen($value) > 30) $value = substr($value, 0, 30) . '...';
            echo "<td style='padding: 8px;'>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Generar archivo JSON
    echo "<h2>💾 Generando archivo JSON...</h2>";
    
    $exportData = [
        'exported_at' => date('Y-m-d H:i:s'),
        'total_clients' => count($clients),
        'columns' => $selectColumns,
        'clients' => $clients
    ];
    
    $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $filename = 'clients_export_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($filename, $jsonData);
    
    echo "<p style='color: green; font-size: 1.2em;'>✅ Archivo generado: <strong>$filename</strong></p>";
    echo "<p>📁 Tamaño: " . number_format(filesize($filename) / 1024, 2) . " KB</p>";
    
    echo "<h2>📋 Instrucciones:</h2>";
    echo "<ol>";
    echo "</ol>";
    
    echo "<h2>📋 Datos JSON para Railway</h2>";
    echo "<p>Como Railway no permite subir archivos, copia estos datos:</p>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<h3>📋 Pasos para importar en Railway:</h3>";
    echo "<ol>";
    echo "<li>Ve a: <strong>https://demo.techonway.com/create_clients_json_railway.php</strong></li>";
    echo "<li>Edita el archivo y pega los datos JSON de abajo</li>";
    echo "<li>Ejecuta el script para crear el archivo JSON</li>";
    echo "<li>Ejecuta el importador</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo "<h3>📝 Datos JSON para copiar:</h3>";
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace; font-size: 11px; padding: 10px;' readonly>";
    echo htmlspecialchars($jsonData);
    echo "</textarea>";
    echo "<p><small>Copia todo el contenido del textarea de arriba</small></p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<p><a href='$filename' download style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>💾 Descargar JSON (backup)</a></p>";
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
ol {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

    margin: 8px 0; 
    line-height: 1.4;
}
code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
ol {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
