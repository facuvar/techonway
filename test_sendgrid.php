<?php
/**
 * Script de prueba para SendGrid
 * Verifica que la configuración de email funcione correctamente
 */

require_once 'includes/EmailNotifier.php';

echo "\n";
echo "🧪 PRUEBA DE CONFIGURACIÓN SENDGRID\n";
echo "═══════════════════════════════════\n\n";

// Verificar si el archivo de configuración existe
$configFile = 'config/email.php';
if (!file_exists($configFile)) {
    echo "❌ Error: No se encontró el archivo de configuración $configFile\n";
    echo "💡 Ejecuta: php setup_sendgrid.php\n\n";
    exit(1);
}

// Cargar configuración
$config = require $configFile;

echo "📧 Configuración cargada:\n";
echo "• SMTP Host: " . $config['smtp_host'] . "\n";
echo "• SMTP Port: " . $config['smtp_port'] . "\n";
echo "• From Email: " . $config['from_email'] . "\n";
echo "• From Name: " . $config['from_name'] . "\n\n";

// Verificar API Key
if (empty($config['smtp_password'])) {
    echo "❌ Error: No se encontró la API Key de SendGrid\n";
    echo "💡 Verifica las variables de entorno o config/local.php\n\n";
    exit(1);
}

echo "✅ API Key configurada (oculta por seguridad)\n\n";

// Pedir email de prueba
echo "📧 PRUEBA DE ENVÍO\n";
echo "═══════════════════\n";

if (php_sapi_name() === 'cli') {
    echo "Email de destino para la prueba: ";
    $testEmail = trim(fgets(STDIN));
} else {
    // Si se ejecuta desde el navegador
    $testEmail = $_GET['email'] ?? '';
    if (empty($testEmail)) {
        echo "<form method='get'>";
        echo "Email de destino: <input type='email' name='email' required> ";
        echo "<input type='submit' value='Probar'>";
        echo "</form>";
        exit;
    }
}

if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Email inválido\n";
    exit(1);
}

echo "Enviando email de prueba a: $testEmail\n\n";

try {
    // Crear instancia del notificador de email
    $emailNotifier = new EmailNotifier(true); // Debug mode activado
    
    // Crear datos de prueba
    $testClient = [
        'name' => 'Cliente de Prueba SendGrid',
        'email' => $testEmail,
        'address' => 'Calle de Prueba 123, Ciudad de Prueba'
    ];
    
    $testTicket = [
        'id' => 'SG-TEST-' . date('YmdHis'),
        'description' => 'Prueba de configuración de SendGrid para el sistema TechonWay',
        'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
        'scheduled_time' => '10:00:00',
        'security_code' => 'SG' . rand(1000, 9999)
    ];
    
    $testTechnician = [
        'name' => 'Técnico de Prueba SendGrid'
    ];
    
    echo "🚀 Enviando email...\n";
    
    $startTime = microtime(true);
    $result = $emailNotifier->sendAppointmentNotification($testClient, $testTicket, $testTechnician);
    $endTime = microtime(true);
    
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "\n";
    
    if ($result) {
        echo "✅ EMAIL ENVIADO CORRECTAMENTE!\n";
        echo "⏱️  Tiempo de envío: {$duration}ms\n";
        echo "📧 Revisa tu bandeja de entrada en: $testEmail\n";
        echo "📁 También revisa la carpeta de spam/correo no deseado\n\n";
        
        echo "📊 DETALLES DEL EMAIL:\n";
        echo "• Ticket: {$testTicket['id']}\n";
        echo "• Código de seguridad: {$testTicket['security_code']}\n";
        echo "• Fecha programada: {$testTicket['scheduled_date']}\n";
        echo "• Hora programada: {$testTicket['scheduled_time']}\n\n";
        
        echo "🎉 SendGrid está configurado correctamente!\n";
        
    } else {
        echo "❌ ERROR ENVIANDO EMAIL\n";
        echo "💡 Posibles causas:\n";
        echo "   • API Key inválida\n";
        echo "   • Email del remitente no verificado en SendGrid\n";
        echo "   • Problemas de conectividad\n";
        echo "   • Configuración SMTP incorrecta\n\n";
        echo "🔍 Revisa los logs de error para más detalles\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN CAPTURADA:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "💡 Verifica tu configuración de SendGrid\n";
}

echo "\n";
echo "🔗 RECURSOS ÚTILES:\n";
echo "═════════════════════\n";
echo "• SendGrid Dashboard: https://app.sendgrid.com/\n";
echo "• Activity Feed: https://app.sendgrid.com/email_activity\n";
echo "• API Keys: https://app.sendgrid.com/settings/api_keys\n";
echo "• Sender Authentication: https://app.sendgrid.com/settings/sender_auth\n\n";
