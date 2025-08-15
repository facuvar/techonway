<?php
/**
 * Configuración de Email para TechonWay
 * 
 * Esta configuración usa SendGrid para enviar emails de notificación
 * sobre citas programadas a los clientes
 */

// Cargar configuración desde mail.php que ya funciona
$mailConfig = require __DIR__ . '/mail.php';

// Configuración de SendGrid desde mail.php
$smtpHost = $mailConfig['host'];
$smtpPort = $mailConfig['port'];
$smtpUsername = $mailConfig['username'];
$smtpPassword = $mailConfig['password'];
$fromEmail = $mailConfig['from_email'];
$fromName = $mailConfig['from_name'];

return [
    // Configuración SMTP (SendGrid)
    'smtp_host' => $smtpHost,
    'smtp_port' => $smtpPort,
    'smtp_username' => $smtpUsername,
    'smtp_password' => $smtpPassword,
    'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls', // 'tls' o 'ssl'
    
    // Información del remitente
    'from_email' => $fromEmail,
    'from_name' => $fromName,
    'reply_to' => $_ENV['REPLY_TO_EMAIL'] ?? $fromEmail,
    
    // Configuración adicional
    'debug_mode' => $_ENV['EMAIL_DEBUG'] ?? false,
    'timeout' => 30,
    
    // Templates
    'templates_path' => __DIR__ . '/../templates/emails/',
    
    // Configuración local para desarrollo
    'local_config' => [
        'enabled' => true, // Cambiar a false para desactivar emails en desarrollo
        'use_sendgrid' => true // Usar SendGrid incluso en desarrollo
    ]
];
