<?php
/**
 * Script de prueba para plantillas HSM de WhatsApp
 * 
 * Este script permite probar las plantillas HSM antes de ponerlas en producción.
 * Solo funciona cuando 'use_hsm_templates' está habilitado en la configuración.
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/WhatsAppNotifier.php';

// Función para mostrar logs
function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

echo "=== PRUEBA DE PLANTILLAS HSM WHATSAPP ===\n\n";

try {
    // Verificar si las plantillas HSM están habilitadas
    $config = require 'config/whatsapp.php';
    if (!$config['use_hsm_templates']) {
        logMessage("ATENCIÓN: Las plantillas HSM están deshabilitadas.");
        logMessage("Para probar, cambia 'use_hsm_templates' => true en config/whatsapp.php");
        exit;
    }
    
    logMessage("Plantillas HSM habilitadas. Iniciando pruebas...");
    
    // Conectar a la base de datos
    $db = new Database();
    
    // Obtener un técnico de prueba
    $technician = $db->selectOne("SELECT * FROM users WHERE role = 'technician' AND phone IS NOT NULL LIMIT 1");
    
    if (!$technician) {
        logMessage("ERROR: No se encontró ningún técnico con número de teléfono para las pruebas");
        exit;
    }
    
    logMessage("Técnico de prueba: {$technician['name']} ({$technician['phone']})");
    
    // Obtener un cliente de prueba
    $client = $db->selectOne("SELECT * FROM clients LIMIT 1");
    
    if (!$client) {
        logMessage("ERROR: No se encontró ningún cliente para las pruebas");
        exit;
    }
    
    logMessage("Cliente de prueba: {$client['name']}");
    
    // Crear datos de ticket de prueba
    $ticketData = [
        'id' => 99999,
        'description' => 'Prueba de plantilla HSM - Mantenimiento de equipo',
        'priority' => 'medium',
        'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
        'scheduled_time' => '14:30:00',
        'security_code' => 'TEST123'
    ];
    
    $whatsapp = new WhatsAppNotifier(true); // Modo debug habilitado
    
    echo "\n=== PRUEBA 1: Nuevo ticket sin cita ===\n";
    $ticketWithoutAppointment = $ticketData;
    $ticketWithoutAppointment['scheduled_date'] = null;
    $ticketWithoutAppointment['scheduled_time'] = null;
    $ticketWithoutAppointment['security_code'] = null;
    
    $result1 = $whatsapp->sendTicketNotification($technician, $ticketWithoutAppointment, $client, null, false);
    logMessage("Resultado: " . ($result1 ? "ÉXITO" : "ERROR"));
    
    sleep(2); // Esperar entre mensajes
    
    echo "\n=== PRUEBA 2: Nuevo ticket con cita ===\n";
    $result2 = $whatsapp->sendTicketNotification($technician, $ticketData, $client, null, false);
    logMessage("Resultado: " . ($result2 ? "ÉXITO" : "ERROR"));
    
    sleep(2); // Esperar entre mensajes
    
    echo "\n=== PRUEBA 3: Reprogramación de cita ===\n";
    $rescheduledTicket = $ticketData;
    $rescheduledTicket['scheduled_date'] = date('Y-m-d', strtotime('+2 days'));
    $rescheduledTicket['scheduled_time'] = '16:00:00';
    $rescheduledTicket['security_code'] = 'NUEVO456';
    
    $result3 = $whatsapp->sendTicketNotification($technician, $rescheduledTicket, $client, null, true);
    logMessage("Resultado: " . ($result3 ? "ÉXITO" : "ERROR"));
    
    sleep(2); // Esperar entre mensajes
    
    echo "\n=== PRUEBA 4: Mensaje de bienvenida ===\n";
    $result4 = $whatsapp->sendWelcomeMessage($technician);
    logMessage("Resultado: " . ($result4 ? "ÉXITO" : "ERROR"));
    
    echo "\n=== RESUMEN DE PRUEBAS ===\n";
    logMessage("Nuevo ticket sin cita: " . ($result1 ? "ÉXITO" : "ERROR"));
    logMessage("Nuevo ticket con cita: " . ($result2 ? "ÉXITO" : "ERROR"));
    logMessage("Reprogramación: " . ($result3 ? "ÉXITO" : "ERROR"));
    logMessage("Mensaje de bienvenida: " . ($result4 ? "ÉXITO" : "ERROR"));
    
    $successCount = ($result1 ? 1 : 0) + ($result2 ? 1 : 0) + ($result3 ? 1 : 0) + ($result4 ? 1 : 0);
    logMessage("Total exitosos: {$successCount}/4");
    
    if ($successCount == 4) {
        logMessage("¡Todas las pruebas fueron exitosas! Las plantillas HSM están funcionando correctamente.");
    } else {
        logMessage("Algunas pruebas fallaron. Revisa los logs de WhatsApp para más detalles.");
    }
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Traza: " . $e->getTraceAsString());
}

echo "\n=== FIN DE PRUEBAS ===\n";
?>
