<?php

// Detectar si estamos en local o en servidor
$isLocal = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// Cargar configuración local si existe (solo para desarrollo)
$localConfig = null;
if ($isLocal && file_exists(__DIR__ . '/local.php')) {
    $localConfig = require __DIR__ . '/local.php';
}

// Obtener token
$authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
if ($localConfig && isset($localConfig['twilio']['auth_token'])) {
    $authToken = $localConfig['twilio']['auth_token'];
}

return [
    'provider' => 'twilio',
    'account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'auth_token' => $authToken,
    'from_number' => '+1XXXXXXXXXX',
    // URL base pública del sistema (para incluir enlace al ticket en el SMS)
    'base_url' => 'http://localhost/sistema-techonway',
    'country_code' => '54',
];




