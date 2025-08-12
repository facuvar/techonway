<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/WhatsAppNotifier.php';
require_once __DIR__ . '/../includes/SmsNotifier.php';

$auth->requireAdmin();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/service_requests.php');
}

try {
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

    // Verificar que existan el cliente y técnico
    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
    if (!$client) {
        flash('Cliente no encontrado.', 'danger');
        redirect('admin/service_requests.php?action=convert&id=' . $requestId);
    }

    $technician = $db->selectOne("SELECT * FROM users WHERE id = ? AND role = 'technician'", [$technicianId]);
    if (!$technician) {
        flash('Técnico no encontrado.', 'danger');
        redirect('admin/service_requests.php?action=convert&id=' . $requestId);
    }

    // Crear ticket
    $ticketId = $db->insert('tickets', [
        'client_id' => $clientId,
        'technician_id' => $technicianId,
        'description' => $description,
        'status' => 'pending'
    ]);

    if (!$ticketId) {
        throw new Exception('Error al crear el ticket en la base de datos.');
    }

    // Vincular y cerrar solicitud
    $updateResult = $db->update('service_requests', [
        'ticket_id' => $ticketId,
        'status' => 'closed'
    ], 'id = ?', [$requestId]);

    if (!$updateResult) {
        throw new Exception('Error al actualizar la solicitud de servicio.');
    }

    // Obtener ticket creado
    $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$ticketId]);

    // Logging mejorado usando directorio logs
    $logMessage = [];
    $logMessage[] = "[" . date('Y-m-d H:i:s') . "] Conversión de solicitud #$requestId a ticket #$ticketId";
    $logMessage[] = "Técnico: {$technician['name']} ({$technician['phone']})";
    $logMessage[] = "Cliente: {$client['name']} ({$client['business_name']})";

    // Notificación principal por WhatsApp y fallback SMS si falla
    $waOk = false;
    try {
        $whatsapp = new WhatsAppNotifier();
        $waOk = $whatsapp->sendTicketNotification($technician, $ticket, $client);
        $logMessage[] = "WhatsApp notification: " . ($waOk ? "ÉXITO" : "ERROR");
    } catch (Exception $e) {
        $errorMsg = 'Error al enviar notificación WhatsApp: ' . $e->getMessage();
        error_log($errorMsg);
        $logMessage[] = "WhatsApp notification: EXCEPCIÓN - " . $e->getMessage();
    }

    // Si WhatsApp falló, enviar SMS
    if (!$waOk) {
        try {
            $sms = new SmsNotifier();
            $smsOk = $sms->sendTicketNotification($technician, $ticket, $client);
            $logMessage[] = "Fallback SMS: " . ($smsOk ? "ÉXITO" : "ERROR");
        } catch (Exception $ex) {
            $errorMsg = 'Error al enviar SMS: ' . $ex->getMessage();
            error_log($errorMsg);
            $logMessage[] = "Fallback SMS: EXCEPCIÓN - " . $ex->getMessage();
        }
    }

    // Intentar escribir al log si es posible
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/ticket_notifications_' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, implode("\n", $logMessage) . "\n\n", FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Log al error_log del sistema si no se puede escribir archivo
        error_log("Log de conversión: " . implode(" | ", $logMessage));
    }

    flash('Ticket #' . $ticketId . ' creado exitosamente desde la solicitud #' . $requestId . '.', 'success');
    redirect('admin/tickets.php?action=view&id=' . $ticketId);

} catch (Exception $e) {
    error_log("Error en conversión de solicitud a ticket: " . $e->getMessage());
    flash('Error al convertir la solicitud: ' . $e->getMessage(), 'danger');
    redirect('admin/service_requests.php?action=convert&id=' . ($requestId ?? 0));
}


