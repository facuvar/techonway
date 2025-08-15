<?php
/**
 * Configuración local para desarrollo
 * 
 * Copia este archivo como 'local.php' y configura tus credenciales
 * El archivo 'local.php' está en .gitignore y no se sube al repositorio
 */

return [
    // Configuración de base de datos local
    'database' => [
        'host' => 'localhost',
        'name' => 'techonway',
        'username' => 'root',
        'password' => ''
    ],
    
    // Configuración de WhatsApp local
    'whatsapp' => [
        'token' => 'tu_token_de_whatsapp_aqui',
        // Otros parámetros si necesitas sobrescribir
    ],
    
    // Configuración de SMS local
    'sms' => [
        'account_sid' => 'tu_twilio_sid_aqui',
        'auth_token' => 'tu_twilio_token_aqui',
        'from_number' => '+1234567890'
    ],
    
    // Configuración de Email/SendGrid local
    'email' => [
        'smtp_host' => 'smtp.sendgrid.net',
        'smtp_port' => 587,
        'smtp_username' => 'apikey',
        'smtp_password' => 'tu_sendgrid_api_key_aqui',
        'from_email' => 'tu_email@dominio.com',
        'from_name' => 'TechonWay - Sistema Local'
    ]
];