<?php
/**
 * Script para crear el archivo JSON de clientes directamente en Railway
 * Copia y pega los datos del export local aquí
 */

echo "<h1>📥 Crear archivo JSON de clientes</h1>";

// INSTRUCCIONES: 
// 1. Ejecuta export_clients_local.php en tu local
// 2. Copia el contenido JSON que aparece abajo de "Datos JSON generados:"
// 3. Pégalo en la variable $clientsData abajo
// 4. Ejecuta este script en Railway

$clientsData = [
    // PEGA AQUÍ LOS DATOS DEL EXPORT LOCAL
    // El formato debe ser:
    // 'exported_at' => '2024-01-15 14:30:45',
    // 'total_clients' => 123,
    // 'columns' => ['id', 'name', 'email', 'phone', 'business_name', 'address', 'latitude', 'longitude', 'zone', 'created_at'],
    // 'clients' => [
    //     ['id' => 1, 'name' => 'Cliente 1', 'email' => 'cliente1@email.com', ...],
    //     ['id' => 2, 'name' => 'Cliente 2', 'email' => 'cliente2@email.com', ...],
    //     ...
    // ]
];

if (empty($clientsData) || !isset($clientsData['clients'])) {
    echo "<div style='padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📋 Instrucciones</h2>";
    echo "<ol>";
    echo "<li><strong>En LOCAL:</strong> Ejecuta <code>http://localhost/sistema-techonway/export_clients_local.php</code></li>";
    echo "<li><strong>Copia los datos JSON</strong> que aparecen en la sección 'Datos JSON generados'</li>";
    echo "<li><strong>Edita este archivo</strong> y pega los datos en la variable <code>\$clientsData</code></li>";
    echo "<li><strong>Ejecuta de nuevo</strong> este script</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; margin: 20px 0;'>";
    echo "<h3>🔧 Formato esperado:</h3>";
    echo "<pre style='background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
    echo htmlspecialchars('$clientsData = [
    "exported_at" => "2024-01-15 14:30:45",
    "total_clients" => 123,
    "columns" => ["id", "name", "email", "phone", "business_name", "address", "latitude", "longitude", "zone", "created_at"],
    "clients" => [
        ["id" => 1, "name" => "Cliente 1", "email" => "cliente1@email.com", ...],
        ["id" => 2, "name" => "Cliente 2", "email" => "cliente2@email.com", ...],
        // ... más clientes
    ]
];');
    echo "</pre>";
    echo "</div>";
    
    exit;
}

try {
    echo "<h2>💾 Generando archivo JSON...</h2>";
    
    $jsonData = json_encode($clientsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (!$jsonData) {
        throw new Exception("Error al generar JSON: " . json_last_error_msg());
    }
    
    $filename = 'clients_export_' . date('Y-m-d_H-i-s') . '.json';
    $result = file_put_contents($filename, $jsonData);
    
    if ($result === false) {
        throw new Exception("Error al escribir archivo JSON");
    }
    
    echo "<p style='color: green; font-size: 1.2em;'>✅ Archivo JSON creado: <strong>$filename</strong></p>";
    echo "<p>📁 Tamaño: " . number_format($result / 1024, 2) . " KB</p>";
    echo "<p>📊 Clientes: " . $clientsData['total_clients'] . "</p>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎯 Próximo paso</h3>";
    echo "<p style='color: #155724;'>Ahora ejecuta el importador:</p>";
    echo "<p><a href='/import_clients_from_json.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🚀 Importar Clientes</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
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
pre {
    font-size: 12px;
    line-height: 1.4;
}
</style>
