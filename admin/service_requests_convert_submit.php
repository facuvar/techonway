<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/WhatsAppNotifier.php';
require_once __DIR__ . '/../includes/SmsNotifier.php';

$auth->requireAdmin();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/service_requests.php');
}

$requestId = (int)($_POST['request_id'] ?? 0);
$clientId = (int)($_POST['client_id'] ?? 0);
$technicianId = (int)($_POST['technician_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($requestId <= 0 || $clientId <= 0 || $technicianId <= 0 || $description === '') {
    flash('Complete todos los campos.', 'danger');
    redirect('admin/service_requests.php?action=convert&id=' . $requestId);
}

// Verificar solicitud
$request = $db->selectOne('SELECT * FROM service_requests WHERE id = ?', [$requestId]);
if (!$request) {
    flash('Solicitud no encontrada.', 'danger');
    redirect('admin/service_requests.php');
}

// Crear ticket
$ticketId = $db->insert('tickets', [
    'client_id' => $clientId,
    'technician_id' => $technicianId,
    'description' => $description,
    'status' => 'pending'
]);

// Vincular y cerrar solicitud
$db->update('service_requests', [
    'ticket_id' => $ticketId,
    'status' => 'closed'
], 'id = ?', [$requestId]);

// Notificar al técnico por WhatsApp (mismo flujo que en tickets.php)
$technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
$client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
$ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$ticketId]);

// Notificación principal por WhatsApp y fallback SMS si falla
$waOk = false;
try {
    $whatsapp = new WhatsAppNotifier();
    $waOk = $whatsapp->sendTicketNotification($technician, $ticket, $client);
} catch (Exception $e) {
    error_log('Error al enviar notificación WhatsApp: ' . $e->getMessage());
}

// Registrar log común
$logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
file_put_contents($logFile, "Resultado de notificación para ticket #{$ticket['id']}: " . ($waOk ? "ÉXITO" : "ERROR") . "\n");
file_put_contents($logFile, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
file_put_contents($logFile, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);

// Si WhatsApp falló, enviar SMS
if (!$waOk) {
    try {
        $sms = new SmsNotifier();
        $smsOk = $sms->sendTicketNotification($technician, $ticket, $client);
        file_put_contents($logFile, "Fallback SMS: " . ($smsOk ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
    } catch (Exception $ex) {
        error_log('Error al enviar SMS: ' . $ex->getMessage());
        file_put_contents($logFile, "Fallback SMS: EXCEPCION - " . $ex->getMessage() . "\n", FILE_APPEND);
    }
}

flash('Ticket #' . $ticketId . ' creado desde la solicitud #' . $requestId . '.', 'success');
redirect('admin/tickets.php?action=view&id=' . $ticketId);


