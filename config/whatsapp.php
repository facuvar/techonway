<?php
/**
 * WhatsApp API configuration
 */

// Detectar si estamos en local o en servidor
$isLocal = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// Cargar configuración local si existe (solo para desarrollo)
$localConfig = null;
if ($isLocal && file_exists(__DIR__ . '/local.php')) {
    $localConfig = require __DIR__ . '/local.php';
}

// Obtener token
$token = $_ENV['WHATSAPP_TOKEN'] ?? '';
if ($localConfig && isset($localConfig['whatsapp']['token'])) {
    $token = $localConfig['whatsapp']['token'];
}

return [
    // URL base de la API de WhatsApp Business
    'api_url' => 'https://graph.facebook.com/v20.0/',
    
    // Token de acceso para la API de WhatsApp Business
    'token' => $token,
    
    // ID del número de teléfono de WhatsApp Business
    'phone_number_id' => '345032388695817',
    
    // WhatsApp Business Account ID
    'business_account_id' => '247414851788542',
    
    // Business Account ID
    'account_id' => '935803630729512',
    
    // Código de país para formatear números (Argentina = 54)
    'country_code' => '54',
    
    // URL base para enlaces en los mensajes (detecta automáticamente el entorno)
    'base_url' => (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
        ? 'http://localhost/sistema-techonway/' 
        : 'https://demo.techonway.com/',
    
    // Configuración de plantillas
    'default_template' => 'nuevo_ticket', // Nombre de la plantilla por defecto para notificaciones de tickets
    'default_language' => 'es_AR'         // Código de idioma por defecto para las plantillas
];
