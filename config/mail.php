<?php
// Cargar API key de SendGrid desde variable de entorno o configuración local
$envKey = getenv('SENDGRID_API_KEY') ?: '';

// Detectar si estamos en local o en servidor
$isLocal = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// Cargar configuración local si existe (solo para desarrollo)
$localConfig = null;
if ($isLocal && file_exists(__DIR__ . '/local.php')) {
    $localConfig = require __DIR__ . '/local.php';
}

// Obtener API key
$apiKey = $envKey;
if ($localConfig && isset($localConfig['sendgrid']['api_key'])) {
    $apiKey = $localConfig['sendgrid']['api_key'];
}

// Fallback para archivo local (mantener compatibilidad)
$fileKey = '';
$keyFile = __DIR__ . '/.sendgrid_key';
if (empty($apiKey) && file_exists($keyFile)) {
    $apiKey = trim((string)file_get_contents($keyFile));
}

return [
    // Transporte: 'smtp' o 'sendgrid_api'
    'transport' => 'sendgrid_api',
    'host' => 'smtp.sendgrid.net',
    'port' => 587,
    'username' => 'apikey',
    // Prioriza variable de entorno; si no existe, usa configuración local
    'password' => $apiKey,
    'sendgrid_api_key' => $apiKey,
    'encryption' => 'tls',
    // IMPORTANTE: usa un remitente verificado en SendGrid (Single Sender o dominio autenticado)
    'from_email' => 'no-reply@techonway.com',
    'from_name' => 'TechOnWay',
    'notify_to' => 'admin@example.com',
    // Debug y logging opcional (desactiva en producción)
    'debug' => true, // true para ver SMTPDebug
    'log_dir' => __DIR__ . '/../logs',
    // Opciones SSL (solo para SMTP)
    'verify_peer' => true,
    'verify_peer_name' => true,
    'allow_self_signed' => false,
    // Si configurás un CA bundle local, poné la ruta aquí (ej: C:\\xampp\\php\\extras\\ssl\\cacert.pem)
    'cafile' => ''
];


