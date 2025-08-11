<?php
/**
 * EJEMPLO de configuración local para desarrollo
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo como "config/local.php"
 * 2. Completa las credenciales reales
 * 3. El archivo config/local.php NO se sube a Git
 */

return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'techonway',
        'username' => 'root',
        'password' => '',                    // Contraseña de MySQL local
        'charset' => 'utf8mb4'
    ],
    
    'database_server' => [
        'host' => 'localhost',
        'dbname' => 'techonway_demo',
        'username' => 'techonway_demousr',
        'password' => 'TU_PASSWORD_SERVIDOR',  // Contraseña del servidor
        'charset' => 'utf8'
    ],
    
    'whatsapp' => [
        'token' => 'TU_WHATSAPP_TOKEN_AQUI'  // Token de WhatsApp Business API
    ],
    
    'sendgrid' => [
        'api_key' => 'TU_SENDGRID_API_KEY_AQUI'  // API Key de SendGrid
    ],
    
    'twilio' => [
        'auth_token' => 'TU_TWILIO_TOKEN_AQUI'  // Auth Token de Twilio
    ]
];
?>
