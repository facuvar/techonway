<?php
/**
 * Script para probar y configurar el sistema de emails
 */
require_once 'includes/init.php';

// Verificar PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<h1>❌ Error: PHPMailer no está instalado</h1>";
    echo "<p>Para instalar PHPMailer, ejecuta en la terminal:</p>";
    echo "<pre>composer require phpmailer/phpmailer</pre>";
    exit;
}

echo "<h1>🔧 Configuración del Sistema de Emails</h1>";

// Simular envío de email
echo "<h2>📧 Prueba de Email de Cita Programada</h2>";

// Datos de prueba
$client = [
    'name' => 'Juan Pérez',
    'email' => 'cliente@ejemplo.com',
    'address' => 'Av. Corrientes 1234, Buenos Aires'
];

$ticket = [
    'id' => 123,
    'description' => 'Mantenimiento preventivo del ascensor',
    'scheduled_date' => '2024-12-20',
    'scheduled_time' => '14:30:00',
    'security_code' => '7854'
];

$technician = [
    'name' => 'Carlos Rodríguez'
];

try {
    // Crear instancia del notificador
    $emailNotifier = new EmailNotifier(true); // Debug mode activado
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>📝 Email que se enviaría:</h3>";
    
    echo "<p><strong>Para:</strong> {$client['email']}</p>";
    echo "<p><strong>Asunto:</strong> Cita de Mantenimiento Programada - TechonWay</p>";
    echo "<p><strong>Fecha:</strong> Viernes, 20 de diciembre de 2024</p>";
    echo "<p><strong>Hora:</strong> 14:30 hs</p>";
    echo "<p><strong>Técnico:</strong> {$technician['name']}</p>";
    echo "<p><strong>Código de Seguridad:</strong> <span style='background: #007bff; color: white; padding: 2px 8px; border-radius: 3px;'>{$ticket['security_code']}</span></p>";
    echo "</div>";
    
    echo "<h3>🔧 Configuraciones de Email Disponibles:</h3>";
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
    
    // Opción 1: Modo simulación
    echo "<div style='border: 2px solid #28a745; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='color: #28a745;'>✅ Opción 1: Modo Simulación (Recomendado)</h4>";
    echo "<p>Solo registra en logs, no envía emails reales</p>";
    echo "<p><strong>Ventaja:</strong> Funciona inmediatamente</p>";
    echo "<p><strong>Uso:</strong> Perfecto para desarrollo</p>";
    echo "<p><a href='setup_email_simulation.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Activar Modo Simulación</a></p>";
    echo "</div>";
    
    // Opción 2: Gmail SMTP
    echo "<div style='border: 2px solid #ffc107; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='color: #f57c00;'>⚙️ Opción 2: Configurar Gmail SMTP</h4>";
    echo "<p>Usar tu cuenta de Gmail para enviar emails</p>";
    echo "<p><strong>Requiere:</strong> Configurar app password de Gmail</p>";
    echo "<p><strong>Uso:</strong> Para pruebas reales</p>";
    echo "<p><a href='setup_gmail_smtp.php' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Configurar Gmail</a></p>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<h3>📊 Estado Actual:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
    echo "<p><strong>⚠️ Emails deshabilitados:</strong> El sistema funciona normalmente pero no envía emails reales.</p>";
    echo "<p><strong>✅ Tickets funcionan:</strong> Se pueden programar citas sin problemas.</p>";
    echo "<p><strong>📝 Logs disponibles:</strong> Los intentos de envío se registran en los logs.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24;'>";
    echo "<h3>❌ Error de Configuración</h3>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='admin/tickets.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 Volver a Tickets</a></p>";
?>
