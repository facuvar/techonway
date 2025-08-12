<?php
/**
 * Script de debug para verificar configuración de WhatsApp
 */
require_once 'includes/init.php';

echo "<h1>Debug WhatsApp Configuration</h1>";

echo "<h2>Variables de entorno</h2>";
echo "<pre>";
echo "WHATSAPP_TOKEN: " . (isset($_ENV['WHATSAPP_TOKEN']) ? "✓ Configurado" : "❌ No configurado") . "\n";
echo "TWILIO_AUTH_TOKEN: " . (isset($_ENV['TWILIO_AUTH_TOKEN']) ? "✓ Configurado" : "❌ No configurado") . "\n";
echo "</pre>";

echo "<h2>Configuración WhatsApp</h2>";
try {
    $config = require __DIR__ . '/config/whatsapp.php';
    echo "<pre>";
    echo "API URL: " . $config['api_url'] . "\n";
    echo "Phone Number ID: " . $config['phone_number_id'] . "\n";
    echo "Token: " . (empty($config['token']) ? "❌ VACÍO" : "✓ Configurado") . "\n";
    echo "Business Account ID: " . $config['business_account_id'] . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error cargando configuración: " . $e->getMessage() . "</p>";
}

echo "<h2>Test de WhatsAppNotifier</h2>";
try {
    require_once 'includes/WhatsAppNotifier.php';
    $whatsapp = new WhatsAppNotifier(true); // Debug mode
    echo "<p style='color:green'>✓ WhatsAppNotifier se inicializó correctamente</p>";
    
    echo "<h3>Técnicos en la base de datos</h3>";
    $db = Database::getInstance();
    $technicians = $db->select("SELECT id, name, phone, role FROM users WHERE role = 'technician'");
    
    if (empty($technicians)) {
        echo "<p style='color:orange'>No hay técnicos en la base de datos</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Estado</th></tr>";
        foreach ($technicians as $tech) {
            $phoneStatus = !empty($tech['phone']) ? "✓ " . $tech['phone'] : "❌ Sin teléfono";
            echo "<tr>";
            echo "<td>{$tech['id']}</td>";
            echo "<td>{$tech['name']}</td>";
            echo "<td>{$phoneStatus}</td>";
            echo "<td>" . ($tech['role'] === 'technician' ? "✓ Técnico" : "❌ No es técnico") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error inicializando WhatsAppNotifier: " . $e->getMessage() . "</p>";
}

echo "<h2>Logs de WhatsApp recientes</h2>";
$logDir = __DIR__ . '/logs';
if (is_dir($logDir)) {
    $logFiles = glob($logDir . '/whatsapp*.log');
    if (!empty($logFiles)) {
        // Mostrar el archivo más reciente
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $recentLog = $logFiles[0];
        echo "<h3>Archivo: " . basename($recentLog) . "</h3>";
        echo "<pre style='background:#f5f5f5; padding:10px; max-height:300px; overflow-y:auto;'>";
        echo htmlspecialchars(file_get_contents($recentLog));
        echo "</pre>";
    } else {
        echo "<p>No hay logs de WhatsApp disponibles</p>";
    }
} else {
    echo "<p>Directorio de logs no existe</p>";
}
?>