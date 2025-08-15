<?php
/**
 * Formulario web para crear el archivo JSON de clientes en Railway
 * Permite pegar los datos JSON desde el export local
 */

echo "<h1>📥 Crear archivo JSON de clientes</h1>";

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_data'])) {
    try {
        $jsonInput = trim($_POST['json_data']);
        
        if (empty($jsonInput)) {
            throw new Exception("No se proporcionaron datos JSON");
        }
        
        // Intentar decodificar el JSON
        $clientsData = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (!isset($clientsData['clients']) || !is_array($clientsData['clients'])) {
            throw new Exception("Formato de datos incorrecto. Debe contener un array 'clients'");
        }
        
        echo "<h2>💾 Generando archivo JSON...</h2>";
        
        $filename = 'clients_export_' . date('Y-m-d_H-i-s') . '.json';
        $result = file_put_contents($filename, $jsonInput);
        
        if ($result === false) {
            throw new Exception("Error al escribir archivo JSON");
        }
        
        echo "<p style='color: green; font-size: 1.2em;'>✅ Archivo JSON creado: <strong>$filename</strong></p>";
        echo "<p>📁 Tamaño: " . number_format($result / 1024, 2) . " KB</p>";
        echo "<p>📊 Clientes: " . count($clientsData['clients']) . "</p>";
        
        echo "<div style='margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>🎯 Próximo paso</h3>";
        echo "<p style='color: #155724;'>Ahora ejecuta el importador:</p>";
        echo "<p><a href='/import_clients_from_json.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🚀 Importar Clientes</a></p>";
        echo "</div>";
        
        exit;
        
    } catch (Exception $e) {
        echo "<div style='margin: 20px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error</h3>";
        echo "<p style='color: #721c24;'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// Mostrar formulario
if (true) {
    echo "<div style='padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📋 Instrucciones</h2>";
    echo "<ol>";
    echo "<li><strong>En LOCAL:</strong> Ejecuta <code>http://localhost/sistema-techonway/export_clients_local.php</code></li>";
    echo "<li><strong>Copia todo el contenido JSON</strong> del textarea que aparece</li>";
    echo "<li><strong>Pégalo abajo</strong> y presiona 'Crear archivo JSON'</li>";
    echo "<li><strong>Ejecuta el importador</strong> cuando se cree el archivo</li>";
    echo "</ol>";
    echo "</div>";
    
    // Formulario para pegar JSON
    echo "<form method='POST'>";
    echo "<div style='margin: 20px 0;'>";
    echo "<label for='json_data' style='font-weight: bold; margin-bottom: 10px; display: block;'>📝 Pega aquí los datos JSON del export local:</label>";
    echo "<textarea name='json_data' id='json_data' style='width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;' placeholder='Pega aquí el contenido JSON completo del export_clients_local.php...' required></textarea>";
    echo "</div>";
    echo "<div style='margin: 20px 0;'>";
    echo "<button type='submit' style='padding: 12px 20px; background: #007bff; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>🚀 Crear archivo JSON</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div style='padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #0c5460; margin-top: 0;'>💡 Consejo</h3>";
    echo "<p style='color: #0c5460; margin-bottom: 0;'>Asegúrate de copiar TODO el contenido JSON, desde la primera llave { hasta la última }. El JSON debe ser válido y contener el array 'clients'.</p>";
    echo "</div>";
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

 * Formulario web para crear el archivo JSON de clientes en Railway
 * Permite pegar los datos JSON desde el export local
 */

echo "<h1>📥 Crear archivo JSON de clientes</h1>";

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_data'])) {
    try {
        $jsonInput = trim($_POST['json_data']);
        
        if (empty($jsonInput)) {
            throw new Exception("No se proporcionaron datos JSON");
        }
        
        // Intentar decodificar el JSON
        $clientsData = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        if (!isset($clientsData['clients']) || !is_array($clientsData['clients'])) {
            throw new Exception("Formato de datos incorrecto. Debe contener un array 'clients'");
        }
        
        echo "<h2>💾 Generando archivo JSON...</h2>";
        
        $filename = 'clients_export_' . date('Y-m-d_H-i-s') . '.json';
        $result = file_put_contents($filename, $jsonInput);
        
        if ($result === false) {
            throw new Exception("Error al escribir archivo JSON");
        }
        
        echo "<p style='color: green; font-size: 1.2em;'>✅ Archivo JSON creado: <strong>$filename</strong></p>";
        echo "<p>📁 Tamaño: " . number_format($result / 1024, 2) . " KB</p>";
        echo "<p>📊 Clientes: " . count($clientsData['clients']) . "</p>";
        
        echo "<div style='margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>🎯 Próximo paso</h3>";
        echo "<p style='color: #155724;'>Ahora ejecuta el importador:</p>";
        echo "<p><a href='/import_clients_from_json.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🚀 Importar Clientes</a></p>";
        echo "</div>";
        
        exit;
        
    } catch (Exception $e) {
        echo "<div style='margin: 20px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error</h3>";
        echo "<p style='color: #721c24;'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// Mostrar formulario
if (true) {
    echo "<div style='padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📋 Instrucciones</h2>";
    echo "<ol>";
    echo "<li><strong>En LOCAL:</strong> Ejecuta <code>http://localhost/sistema-techonway/export_clients_local.php</code></li>";
    echo "<li><strong>Copia todo el contenido JSON</strong> del textarea que aparece</li>";
    echo "<li><strong>Pégalo abajo</strong> y presiona 'Crear archivo JSON'</li>";
    echo "<li><strong>Ejecuta el importador</strong> cuando se cree el archivo</li>";
    echo "</ol>";
    echo "</div>";
    
    // Formulario para pegar JSON
    echo "<form method='POST'>";
    echo "<div style='margin: 20px 0;'>";
    echo "<label for='json_data' style='font-weight: bold; margin-bottom: 10px; display: block;'>📝 Pega aquí los datos JSON del export local:</label>";
    echo "<textarea name='json_data' id='json_data' style='width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;' placeholder='Pega aquí el contenido JSON completo del export_clients_local.php...' required></textarea>";
    echo "</div>";
    echo "<div style='margin: 20px 0;'>";
    echo "<button type='submit' style='padding: 12px 20px; background: #007bff; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>🚀 Crear archivo JSON</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div style='padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #0c5460; margin-top: 0;'>💡 Consejo</h3>";
    echo "<p style='color: #0c5460; margin-bottom: 0;'>Asegúrate de copiar TODO el contenido JSON, desde la primera llave { hasta la última }. El JSON debe ser válido y contener el array 'clients'.</p>";
    echo "</div>";
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
