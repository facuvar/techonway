<?php
/**
 * Tickets management page for administrators
 */
require_once '../includes/init.php';
require_once '../includes/WhatsAppNotifier.php';
require_once '../includes/SmsNotifier.php';
require_once '../includes/Mailer.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$ticketId = $_GET['id'] ?? null;
$clientId = $_GET['client_id'] ?? null;
$technicianId = $_GET['technician_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update ticket
    if (isset($_POST['save_ticket'])) {
        // Procesar datos de programación
        $scheduledDate = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
        $scheduledTime = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : null;
        $securityCode = !empty($_POST['security_code']) ? $_POST['security_code'] : null;
        
        // Si se especifica fecha/hora pero no hay código, generar uno
        if (($scheduledDate || $scheduledTime) && empty($securityCode)) {
            $securityCode = SecurityCodeGenerator::generateUnique($db);
        }
        
        // Si hay código pero no es válido, generar uno nuevo
        if (!empty($securityCode) && !SecurityCodeGenerator::isValid($securityCode)) {
            $securityCode = SecurityCodeGenerator::generateUnique($db);
        }
        
        $ticketData = [
            'client_id' => $_POST['client_id'] ?? null,
            'technician_id' => $_POST['technician_id'] ?? null,
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'pending',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'security_code' => $securityCode
        ];
        
        // Validate required fields
        if (empty($ticketData['client_id']) || empty($ticketData['technician_id']) || empty($ticketData['description'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Create or update
            if (isset($_POST['ticket_id']) && !empty($_POST['ticket_id'])) {
                // Obtener datos del ticket antes de actualizar
                $oldTicket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
                
                // Update existing ticket
                $db->update('tickets', $ticketData, 'id = ?', [$_POST['ticket_id']]);
                flash('Ticket actualizado correctamente.', 'success');
                
                // Si se cambió el técnico asignado, enviar notificación
                if ($oldTicket['technician_id'] != $ticketData['technician_id']) {
                    $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticketData['technician_id']]);
                    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticketData['client_id']]);
                    $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
                    
                    // Enviar notificación por WhatsApp (fallback SMS si falla)
                    $logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
                    $waOk = false;
                    try {
                        $whatsapp = new WhatsAppNotifier();
                        $waOk = $whatsapp->sendTicketNotification($technician, $ticket, $client);
                    } catch (Exception $e) {
                        error_log('Error WhatsApp: ' . $e->getMessage());
                    }
                    file_put_contents($logFile, "Resultado de notificación para ticket #{$ticket['id']}: " . ($waOk ? "ÉXITO" : "ERROR") . "\n");
                    file_put_contents($logFile, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
                    file_put_contents($logFile, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);
                    if (!$waOk) {
                        try {
                            $sms = new SmsNotifier();
                            $smsOk = $sms->sendTicketNotification($technician, $ticket, $client);
                            file_put_contents($logFile, "Fallback SMS: " . ($smsOk ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
                        } catch (Exception $ex) {
                            error_log('Error SMS: ' . $ex->getMessage());
                            file_put_contents($logFile, "Fallback SMS: EXCEPCION - " . $ex->getMessage() . "\n", FILE_APPEND);
                        }
                    }
                }
                
                // Verificar si se programó o cambió la cita y enviar email al cliente
                $schedulingChanged = ($oldTicket['scheduled_date'] != $ticketData['scheduled_date'] || 
                                    $oldTicket['scheduled_time'] != $ticketData['scheduled_time'] ||
                                    $oldTicket['security_code'] != $ticketData['security_code']);
                
                if (!empty($ticketData['scheduled_date']) && !empty($ticketData['scheduled_time']) && $schedulingChanged) {
                    $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticketData['technician_id']]);
                    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticketData['client_id']]);
                    $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
                    
                    // Determinar si es primera programación o reprogramación
                    $wasScheduled = !empty($oldTicket['scheduled_date']) && !empty($oldTicket['scheduled_time']);
                    $isRescheduling = $wasScheduled && ($oldTicket['scheduled_date'] != $ticketData['scheduled_date'] || 
                                                       $oldTicket['scheduled_time'] != $ticketData['scheduled_time']);
                    
                    try {
                        $mailer = new Mailer();
                        
                        if ($isRescheduling) {
                            // Email de reprogramación
                            $subject = 'Cita Técnica Reprogramada - TechonWay';
                            $html = '<h3 style="color: #f39c12;">🔄 Cita Técnica Reprogramada</h3>' .
                                    '<p>Estimado/a <strong>' . htmlspecialchars($client['name']) . '</strong>,</p>' .
                                    '<p><strong>Su cita técnica ha sido reprogramada.</strong></p>' .
                                    
                                    '<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;">' .
                                    '<h4 style="color: #856404; margin-top: 0;">📅 Datos Anteriores:</h4>' .
                                    '<ul style="color: #856404;">' .
                                    '<li><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($oldTicket['scheduled_date'])) . '</li>' .
                                    '<li><strong>Hora:</strong> ' . date('H:i', strtotime($oldTicket['scheduled_time'])) . '</li>' .
                                    '</ul>' .
                                    '</div>' .
                                    
                                    '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;">' .
                                    '<h4 style="color: #155724; margin-top: 0;">✅ Nuevos Datos de la Cita:</h4>' .
                                    '<ul style="color: #155724;">' .
                                    '<li><strong>Nueva Fecha:</strong> ' . date('d/m/Y', strtotime($ticket['scheduled_date'])) . '</li>' .
                                    '<li><strong>Nueva Hora:</strong> ' . date('H:i', strtotime($ticket['scheduled_time'])) . '</li>' .
                                    '<li><strong>Técnico:</strong> ' . htmlspecialchars($technician['name']) . '</li>' .
                                    '<li><strong>Nuevo Código de Seguridad:</strong> ' . htmlspecialchars($ticket['security_code']) . '</li>' .
                                    '</ul>' .
                                    '</div>' .
                                    
                                    '<p><strong>Descripción del trabajo:</strong><br>' . nl2br(htmlspecialchars($ticket['description'])) . '</p>' .
                                    '<p><strong>⚠️ Importante:</strong> Por favor, esté disponible en la dirección indicada en el <strong>nuevo horario programado</strong>.</p>' .
                                    '<p>Disculpe las molestias ocasionadas por el cambio de horario.</p>' .
                                    '<p>Saludos cordiales,<br><strong>Equipo TechonWay</strong></p>';
                            $logMessage = "Email reprogramación";
                            $flashMessage = 'Ticket actualizado y email de reprogramación enviado al cliente.';
                        } else {
                            // Email de primera programación
                            $subject = 'Cita Técnica Programada - TechonWay';
                            $html = '<h3>Cita Técnica Programada</h3>' .
                                    '<p>Estimado/a <strong>' . htmlspecialchars($client['name']) . '</strong>,</p>' .
                                    '<p>Su cita técnica ha sido programada con los siguientes detalles:</p>' .
                                    '<ul>' .
                                    '<li><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($ticket['scheduled_date'])) . '</li>' .
                                    '<li><strong>Hora:</strong> ' . date('H:i', strtotime($ticket['scheduled_time'])) . '</li>' .
                                    '<li><strong>Técnico:</strong> ' . htmlspecialchars($technician['name']) . '</li>' .
                                    '<li><strong>Código de Seguridad:</strong> ' . htmlspecialchars($ticket['security_code']) . '</li>' .
                                    '</ul>' .
                                    '<p><strong>Descripción:</strong><br>' . nl2br(htmlspecialchars($ticket['description'])) . '</p>' .
                                    '<p>Por favor, esté disponible en la dirección indicada en el horario programado.</p>' .
                                    '<p>Saludos cordiales,<br><strong>Equipo TechonWay</strong></p>';
                            $logMessage = "Email cita programada";
                            $flashMessage = 'Ticket actualizado y email de cita enviado al cliente.';
                        }
                        
                        $emailOk = $mailer->send($client['email'], $client['name'], $subject, $html);
                        $logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
                        file_put_contents($logFile, "$logMessage: " . ($emailOk ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
                        
                        if ($emailOk) {
                            flash($flashMessage, 'success');
                        } else {
                            flash('Ticket actualizado. Email no pudo ser enviado (verifique la configuración).', 'warning');
                        }
                    } catch (Exception $e) {
                        error_log('Error enviando email: ' . $e->getMessage());
                        flash('Ticket actualizado. Error enviando email al cliente.', 'warning');
                    }
                }
            } else {
                // Create new ticket
                $ticketId = $db->insert('tickets', $ticketData);
                flash('Ticket creado correctamente.', 'success');
                
                // Obtener datos para la notificación
                $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticketData['technician_id']]);
                $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticketData['client_id']]);
                $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
                
                // Enviar notificación por WhatsApp (fallback SMS si falla)
                $logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
                $waOk = false;
                try {
                    $whatsapp = new WhatsAppNotifier();
                    $waOk = $whatsapp->sendTicketNotification($technician, $ticket, $client);
                } catch (Exception $e) {
                    error_log('Error WhatsApp: ' . $e->getMessage());
                }
                file_put_contents($logFile, "Resultado de notificación para ticket #{$ticket['id']}: " . ($waOk ? "ÉXITO" : "ERROR") . "\n");
                file_put_contents($logFile, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
                file_put_contents($logFile, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);
                if (!$waOk) {
                    try {
                        $sms = new SmsNotifier();
                        $smsOk = $sms->sendTicketNotification($technician, $ticket, $client);
                        file_put_contents($logFile, "Fallback SMS: " . ($smsOk ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
                    } catch (Exception $ex) {
                        error_log('Error SMS: ' . $ex->getMessage());
                        file_put_contents($logFile, "Fallback SMS: EXCEPCION - " . $ex->getMessage() . "\n", FILE_APPEND);
                    }
                }
                
                // Enviar email al cliente si se programó una cita
                if (!empty($ticketData['scheduled_date']) && !empty($ticketData['scheduled_time'])) {
                    try {
                        $mailer = new Mailer();
                        $subject = 'Cita Técnica Programada - TechonWay';
                        $html = '<h3>Cita Técnica Programada</h3>' .
                                '<p>Estimado/a <strong>' . htmlspecialchars($client['name']) . '</strong>,</p>' .
                                '<p>Su cita técnica ha sido programada con los siguientes detalles:</p>' .
                                '<ul>' .
                                '<li><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($ticket['scheduled_date'])) . '</li>' .
                                '<li><strong>Hora:</strong> ' . date('H:i', strtotime($ticket['scheduled_time'])) . '</li>' .
                                '<li><strong>Técnico:</strong> ' . htmlspecialchars($technician['name']) . '</li>' .
                                '<li><strong>Código de Seguridad:</strong> ' . htmlspecialchars($ticket['security_code']) . '</li>' .
                                '</ul>' .
                                '<p><strong>Descripción:</strong><br>' . nl2br(htmlspecialchars($ticket['description'])) . '</p>' .
                                '<p>Por favor, esté disponible en la dirección indicada en el horario programado.</p>' .
                                '<p>Saludos cordiales,<br><strong>Equipo TechonWay</strong></p>';
                        
                        $emailOk = $mailer->send($client['email'], $client['name'], $subject, $html);
                        file_put_contents($logFile, "Email al cliente: " . ($emailOk ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
                        
                        if ($emailOk) {
                            flash('Ticket creado y email de cita enviado al cliente.', 'success');
                        } else {
                            flash('Ticket creado. Email no pudo ser enviado (verifique la configuración).', 'warning');
                        }
                    } catch (Exception $e) {
                        error_log('Error enviando email: ' . $e->getMessage());
                        file_put_contents($logFile, "Email ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                        flash('Ticket creado. Error enviando email al cliente.', 'warning');
                    }
                }
            }
            
            // Redirect to list view
            redirect('admin/tickets.php');
        }
    }
    
    // Delete ticket
    if (isset($_POST['delete_ticket'])) {
        $ticketId = $_POST['ticket_id'] ?? null;
        
        if ($ticketId) {
            // Check if ticket has visits
            $visitsCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM visits WHERE ticket_id = ?", 
                [$ticketId]
            )['count'];
            
            if ($visitsCount > 0) {
                flash('No se puede eliminar el ticket porque tiene visitas registradas.', 'danger');
            } else {
                $db->delete('tickets', 'id = ?', [$ticketId]);
                flash('Ticket eliminado correctamente.', 'success');
            }
        }
        
        // Redirect to list view
        redirect('admin/tickets.php');
    }
}

// Get ticket data for edit or view
$ticket = null;
if (($action === 'edit' || $action === 'view') && $ticketId) {
    $ticket = $db->selectOne("
        SELECT t.*, 
               c.name as client_name, 
               c.business_name, 
               c.address, 
               c.latitude, 
               c.longitude, 
               u.name as technician_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        WHERE t.id = ?
    ", [$ticketId]);
    
    if (!$ticket) {
        flash('Ticket no encontrado.', 'danger');
        redirect('admin/tickets.php');
    }
    
    // Debug - Imprimir valores
    if ($action === 'view') {
        error_log("Ticket ID: " . $ticketId);
        error_log("Latitude: " . ($ticket['latitude'] ?? 'NULL'));
        error_log("Longitude: " . ($ticket['longitude'] ?? 'NULL'));
    }
}

// Get all tickets for list view
$tickets = [];
if ($action === 'list') {
    $tickets = $db->select("
        SELECT t.*, c.name as client_name, u.name as technician_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        ORDER BY t.created_at DESC
    ");
}

// Get all clients and technicians for forms
$clients = $db->select("SELECT id, name, business_name FROM clients ORDER BY name");
$technicians = $db->select("SELECT id, name, zone FROM users WHERE role = 'technician' ORDER BY name");

// Page title
$pageTitle = match($action) {
    'create' => __('tickets.title.create', 'Crear Ticket'),
    'edit' => __('tickets.title.edit', 'Editar Ticket'),
    'view' => __('tickets.title.view', 'Ver Ticket'),
    default => __('tickets.title.index', 'Gestión de Tickets')
};

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?php echo __('tickets.actions.new', 'Nuevo Ticket'); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?php echo __('common.back_to_list', 'Volver a la Lista'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Tickets List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($tickets) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo __('common.id', 'ID'); ?></th>
                                    <th><?php echo __('common.client', 'Cliente'); ?></th>
                                    <th><?php echo __('common.technician', 'Técnico'); ?></th>
                                    <th><?php echo __('common.description', 'Descripción'); ?></th>
                                    <th><?php echo __('common.status', 'Estado'); ?></th>
                                    <th><?php echo __('common.date', 'Fecha'); ?></th>
                                    <th><?php echo __('common.actions', 'Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo $ticket['id']; ?></td>
                                        <td><?php echo escape($ticket['client_name']); ?></td>
                                        <td><?php echo escape($ticket['technician_name']); ?></td>
                                        <td><?php echo escape(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch ($ticket['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    $statusText = __('tickets.status.pending', 'Pendiente');
                                                    break;
                                                case 'in_progress':
                                                    $statusClass = 'bg-info';
                                                    $statusText = __('tickets.status.in_progress', 'En Progreso');
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'bg-success';
                                                    $statusText = __('tickets.status.completed', 'Completado');
                                                    break;
                                                case 'not_completed':
                                                    $statusClass = 'bg-danger';
                                                    $statusText = __('tickets.status.not_completed', 'No Completado');
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $ticket['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $ticket['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar el ticket #<?php echo $ticket['id']; ?>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="<?php echo BASE_URL; ?>admin/tickets.php">
                                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                                <button type="submit" name="delete_ticket" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay tickets registrados</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Ticket Form -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/tickets.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label"><?php echo __('tickets.form.client', 'Cliente'); ?> *</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value=""><?php echo __('tickets.form.select_client', 'Seleccionar cliente'); ?></option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            <?php echo (($action === 'edit' && $ticket['client_id'] == $client['id']) || 
                                                       ($action === 'create' && $clientId == $client['id'])) ? 'selected' : ''; ?>>
                                        <?php echo escape($client['name']) . ' - ' . escape($client['business_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="technician_id" class="form-label"><?php echo __('tickets.form.technician', 'Técnico'); ?> *</label>
                            <select class="form-select" id="technician_id" name="technician_id" required>
                                <option value=""><?php echo __('tickets.form.select_technician', 'Seleccionar técnico'); ?></option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>" 
                                            <?php echo (($action === 'edit' && $ticket['technician_id'] == $tech['id']) || 
                                                       ($action === 'create' && $technicianId == $tech['id'])) ? 'selected' : ''; ?>>
                                        <?php echo escape($tech['name']) . ' (Zona: ' . escape($tech['zone']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo __('tickets.form.description', 'Descripción del Problema'); ?> *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $action === 'edit' ? escape($ticket['description']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Sección de programación de cita -->
                    <div class="card bg-light mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-calendar-event"></i> Programación de Cita
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="scheduled_date" class="form-label">Fecha de la cita</label>
                                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                                           value="<?php echo $action === 'edit' ? escape($ticket['scheduled_date']) : ''; ?>"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <small class="form-text text-muted">Opcional: Deja vacío para programar después</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="scheduled_time" class="form-label">Hora de la cita</label>
                                    <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" 
                                           value="<?php echo $action === 'edit' ? escape($ticket['scheduled_time']) : ''; ?>">
                                    <small class="form-text text-muted">Formato 24 horas (ej: 14:30)</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="security_code" class="form-label">Código de seguridad</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="security_code" name="security_code" 
                                               value="<?php echo $action === 'edit' ? escape($ticket['security_code']) : ''; ?>"
                                               pattern="[0-9]{4}" maxlength="4" placeholder="0000">
                                        <button type="button" class="btn btn-outline-secondary" id="generate_code">
                                            <i class="bi bi-arrow-clockwise"></i> Generar
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Se genera automáticamente si se deja vacío</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Información:</strong> Si programas fecha y hora, se enviará automáticamente un email al cliente 
                                    con los detalles de la cita y el código de seguridad.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($action === 'edit'): ?>
                        <div class="mb-3">
                            <label for="status" class="form-label"><?php echo __('tickets.form.status', 'Estado'); ?></label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('tickets.status.pending', 'Pendiente'); ?></option>
                                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>><?php echo __('tickets.status.in_progress', 'En Progreso'); ?></option>
                                <option value="completed" <?php echo $ticket['status'] === 'completed' ? 'selected' : ''; ?>><?php echo __('tickets.status.completed', 'Completado'); ?></option>
                                <option value="not_completed" <?php echo $ticket['status'] === 'not_completed' ? 'selected' : ''; ?>><?php echo __('tickets.status.not_completed', 'No Completado'); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="btn" style="background-color:#2D3142; border-color:#2D3142; color:#fff;">
                            <?php echo __('common.cancel', 'Cancelar'); ?>
                        </a>
                        <button type="submit" name="save_ticket" class="btn btn-primary"><?php echo __('common.save', 'Guardar'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php elseif ($action === 'view' && $ticket): ?>
        <!-- View Ticket Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('tickets.view.info_title', 'Información del Ticket'); ?> #<?php echo $ticket['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6><?php echo __('common.status', 'Estado'); ?>:</h6>
                            <?php 
                            $statusClass = '';
                            $statusText = '';
                            
                            switch ($ticket['status']) {
                                case 'pending':
                                    $statusClass = 'bg-warning';
                                    $statusText = __('tickets.status.pending', 'Pendiente');
                                    break;
                                case 'in_progress':
                                    $statusClass = 'bg-info';
                                    $statusText = __('tickets.status.in_progress', 'En Progreso');
                                    break;
                                case 'completed':
                                    $statusClass = 'bg-success';
                                    $statusText = __('tickets.status.completed', 'Completado');
                                    break;
                                case 'not_completed':
                                    $statusClass = 'bg-danger';
                                    $statusText = __('tickets.status.not_completed', 'No Completado');
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?> fs-6">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('tickets.view.client', 'Cliente'); ?>:</h6>
                            <p>
                                <strong><?php echo escape($ticket['client_name']); ?></strong><br>
                                <?php echo escape($ticket['business_name']); ?><br>
                                <?php echo escape($ticket['address']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('tickets.view.technician_assigned', 'Técnico Asignado'); ?>:</h6>
                            <p><?php echo escape($ticket['technician_name']); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('tickets.view.problem_description', 'Descripción del Problema'); ?>:</h6>
                            <p><?php echo nl2br(escape($ticket['description'])); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('tickets.view.dates', 'Fechas'); ?>:</h6>
                            <p>
                                <strong><?php echo __('tickets.view.created_at', 'Creado'); ?>:</strong> <?php echo formatDateTime($ticket['created_at']); ?><br>
                                <strong><?php echo __('tickets.view.updated_at', 'Última Actualización'); ?>:</strong> <?php echo formatDateTime($ticket['updated_at']); ?>
                            </p>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> <?php echo __('tickets.view.edit_button', 'Editar Ticket'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <?php 
                // Convertir coordenadas a formato correcto
                $lat = str_replace(',', '.', $ticket['latitude'] ?? '');
                $lng = str_replace(',', '.', $ticket['longitude'] ?? '');
                $hasCoordinates = (!empty($lat) && !empty($lng) && is_numeric($lat) && is_numeric($lng));
                ?>
                
                <!-- Client Location Map -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('tickets.view.client_location', 'Ubicación del Cliente'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><?php echo __('tickets.view.address', 'Dirección'); ?>:</h6>
                            <p><?php echo escape($ticket['address']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6><?php echo __('tickets.view.coordinates', 'Coordenadas'); ?>:</h6>
                            <?php if ($hasCoordinates): ?>
                                <p class="coordinates">
                                    <?php echo $lat; ?>, <?php echo $lng; ?>
                                </p>
                            <?php else: ?>
                                <p class="text-warning"><?php echo __('tickets.view.no_coordinates', 'No hay coordenadas válidas disponibles'); ?></p>
                            <?php endif; ?>
                        </div>
                
                        <?php if ($hasCoordinates): ?>
                            <!-- Mapa de Leaflet -->
                            <div id="ticket-map" class="map-container" style="height: 300px; width: 100%; border-radius: 5px;"></div>
                            
                            <div class="mt-3">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $lat; ?>,<?php echo $lng; ?>" 
                                   class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-map"></i> <?php echo __('tickets.view.view_in_maps', 'Ver en Google Maps'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> No se pueden mostrar las coordenadas en el mapa. 
                                Por favor, verifique que el cliente tenga coordenadas válidas en su perfil.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php 
                // Obtener la visita más reciente para este ticket
                $visit = $db->selectOne("
                    SELECT * FROM visits WHERE ticket_id = ? ORDER BY id DESC LIMIT 1
                ", [$ticket['id']]);
                
                // Obtener todas las visitas para este ticket
                $allVisits = $db->select("
                    SELECT * FROM visits WHERE ticket_id = ? ORDER BY start_time DESC
                ", [$ticket['id']]);
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('tickets.view.visit_info', 'Información de Visita'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($visit): ?>
                            <div class="mb-3">
                                <h6><?php echo __('common.status', 'Estado'); ?>:</h6>
                                <?php if (!$visit['start_time']): ?>
                                    <span class="badge bg-secondary fs-6">No iniciada</span>
                                <?php elseif (!$visit['end_time']): ?>
                                    <span class="badge bg-info fs-6">En progreso</span>
                                <?php elseif ($visit['completion_status'] === 'success'): ?>
                                    <span class="badge bg-success fs-6">Finalizada con éxito</span>
                                <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                    <span class="badge bg-danger fs-6">Finalizada sin éxito</span>
                                <?php else: ?>
                                    <span class="badge bg-warning fs-6">Estado desconocido</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <h6><?php echo __('visits.view.start_time', 'Inicio'); ?>:</h6>
                                <p><?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : __('visits.status.not_started', 'No iniciada'); ?></p>
                            </div>
                            
                            <?php if (!empty($visit['start_notes'])): ?>
                            <div class="mb-3">
                                <h6><?php echo __('visits.view.initial_comments', 'Comentarios Iniciales'); ?>:</h6>
                                <div class="p-3 border rounded bg-info text-white">
                                    <?php echo nl2br(escape($visit['start_notes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($visit['comments'])): ?>
                            <div class="mb-3">
                                <h6><?php echo __('visits.view.final_comments', 'Comentarios de Finalización'); ?>:</h6>
                                <div class="p-3 border rounded bg-info text-white">
                                    <?php echo nl2br(escape($visit['comments'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($visit['end_time']): ?>
                                <div class="mb-3">
                                    <h6><?php echo __('visits.view.end_time', 'Fin'); ?>:</h6>
                                    <p><?php echo formatDateTime($visit['end_time']); ?></p>
                                </div>
                                
                                <?php if ($visit['completion_status'] !== 'success' && !empty($visit['failure_reason'])): ?>
                                <div class="mb-3">
                                    <h6><?php echo __('visits.view.not_completed_reason', 'Motivo de No Finalización'); ?>:</h6>
                                    <div class="p-3 border rounded bg-danger bg-opacity-10">
                                        <?php echo nl2br(escape($visit['failure_reason'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Historial de visitas si hay más de una -->
                            <?php if (count($allVisits) > 1): ?>
                                <div class="mt-4">
                                    <h6>Historial de Visitas:</h6>
                                    <div class="accordion" id="visitsAccordion">
                                        <?php foreach ($allVisits as $index => $historyVisit): ?>
                                            <?php if ($index > 0 || $historyVisit['id'] !== $visit['id']): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#visit<?php echo $historyVisit['id']; ?>" aria-expanded="false">
                                                            Visita del <?php echo formatDate($historyVisit['start_time'] ?? $historyVisit['created_at']); ?>
                                                            <?php if ($historyVisit['completion_status'] === 'success'): ?>
                                                                <span class="badge bg-success ms-2">Éxito</span>
                                                            <?php elseif ($historyVisit['completion_status'] === 'failure'): ?>
                                                                <span class="badge bg-danger ms-2">Fallida</span>
                                                            <?php elseif ($historyVisit['start_time'] && !$historyVisit['end_time']): ?>
                                                                <span class="badge bg-info ms-2">En progreso</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary ms-2">No iniciada</span>
                                                            <?php endif; ?>
                                                        </button>
                                                    </h2>
                                                    <div id="visit<?php echo $historyVisit['id']; ?>" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <?php if ($historyVisit['start_time']): ?>
                                                                <p><strong>Inicio:</strong> <?php echo formatDateTime($historyVisit['start_time']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($historyVisit['end_time']): ?>
                                                                <p><strong>Finalización:</strong> <?php echo formatDateTime($historyVisit['end_time']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($historyVisit['start_notes'])): ?>
                                                            <div class="mb-3">
                                                                <h6>Comentarios Iniciales:</h6>
                                                                <div class="p-3 border rounded bg-info text-white">
                                                                    <?php echo nl2br(escape($historyVisit['start_notes'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($historyVisit['comments'])): ?>
                                                            <div class="mb-3">
                                                                <h6>Comentarios de Finalización:</h6>
                                                                <div class="p-3 border rounded bg-info text-white">
                                                                    <?php echo nl2br(escape($historyVisit['comments'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($historyVisit['completion_status'] === 'failure' && !empty($historyVisit['failure_reason'])): ?>
                                                                <div class="mb-3">
                                                                    <h6>Motivo de No Finalización:</h6>
                                                                    <div class="p-3 border rounded bg-danger bg-opacity-10">
                                                                        <?php echo nl2br(escape($historyVisit['failure_reason'])); ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center"><?php echo __('tickets.view.no_visits', 'No hay visitas registradas para este ticket'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>

<?php if ($action === 'view' && $hasCoordinates): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar un momento para asegurarse de que el DOM esté completamente cargado
        setTimeout(function() {
            try {
                console.log("Inicializando mapa con coordenadas:", <?php echo $lat; ?>, <?php echo $lng; ?>);
                
                // Crear mapa
                const map = L.map('ticket-map').setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 15);
                
                // Añadir capa de tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Añadir marcador
                L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
                    .addTo(map)
                    .bindPopup("<?php echo escape($ticket['client_name']); ?><br><?php echo escape($ticket['address']); ?>");
                
                // Invalidar tamaño para asegurar que se renderice correctamente
                map.invalidateSize();
            } catch (error) {
                console.error("Error al inicializar el mapa:", error);
            }
        }, 500);
    });
</script>
<?php endif; ?>

<script>
// JavaScript para el formulario de tickets con programación de citas
document.addEventListener('DOMContentLoaded', function() {
    // Botón para generar código de seguridad
    const generateCodeBtn = document.getElementById('generate_code');
    const securityCodeInput = document.getElementById('security_code');
    
    if (generateCodeBtn && securityCodeInput) {
        generateCodeBtn.addEventListener('click', function() {
            // Generar código aleatorio de 4 dígitos
            const code = Math.floor(1000 + Math.random() * 9000).toString();
            securityCodeInput.value = code;
            
            // Animación visual
            securityCodeInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                securityCodeInput.style.backgroundColor = '';
            }, 1000);
        });
    }
    
    // Validaciones del formulario
    const scheduledDate = document.getElementById('scheduled_date');
    const scheduledTime = document.getElementById('scheduled_time');
    
    if (scheduledDate && scheduledTime) {
        // Si se selecciona fecha, hacer hora obligatoria
        scheduledDate.addEventListener('change', function() {
            if (this.value) {
                scheduledTime.setAttribute('required', 'required');
                scheduledTime.parentElement.querySelector('label').innerHTML = 
                    'Hora de la cita <span style="color: red;">*</span>';
            } else {
                scheduledTime.removeAttribute('required');
                scheduledTime.parentElement.querySelector('label').innerHTML = 'Hora de la cita';
            }
        });
        
        // Si se selecciona hora, hacer fecha obligatoria
        scheduledTime.addEventListener('change', function() {
            if (this.value) {
                scheduledDate.setAttribute('required', 'required');
                scheduledDate.parentElement.querySelector('label').innerHTML = 
                    'Fecha de la cita <span style="color: red;">*</span>';
            } else {
                scheduledDate.removeAttribute('required');
                scheduledDate.parentElement.querySelector('label').innerHTML = 'Fecha de la cita';
            }
        });
    }
    
    // Generar código automáticamente si no se especifica
    const form = document.querySelector('form');
    if (form && securityCodeInput) {
        form.addEventListener('submit', function() {
            if (!securityCodeInput.value || securityCodeInput.value.length !== 4) {
                const code = Math.floor(1000 + Math.random() * 9000).toString();
                securityCodeInput.value = code;
            }
        });
    }
});
</script>

<style>
/* Mejorar visibilidad de iconos de fecha y hora en modo oscuro */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.2);
    opacity: 1;
    cursor: pointer;
    padding: 2px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover,
input[type="time"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
    background-color: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

/* Para navegadores Firefox */
input[type="date"],
input[type="time"] {
    color-scheme: light;
}

/* Estilos específicos para los campos de fecha y hora */
#scheduled_date,
#scheduled_time {
    position: relative;
}

#scheduled_date::after,
#scheduled_time::after {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 0px;
    height: 20px;
    background-size: contain;
    background-repeat: no-repeat;
    pointer-events: none;
}

/* Icono de calendario para el campo de fecha */
#scheduled_date::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1v1a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E") !important;
    z-index: 10;
}

/* Icono de reloj para el campo de hora */
#scheduled_time::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 7 7z'/%3E%3C/svg%3E") !important;
    z-index: 10;
}

/* Mejorar visibilidad de campos de programación */
.card.bg-light {
    background-color: rgba(248, 249, 250, 0.1) !important;
}

.card.bg-light .card-header {
    background-color: rgba(0, 0, 0, 0.2) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card.bg-light .alert-info {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-color: rgba(13, 110, 253, 0.3) !important;
    color: #b6d7ff !important;
}

/* Estilos específicos para los campos de fecha y hora */
.form-control[type="date"],
.form-control[type="time"] {
    background-color: #2D2D2D;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    padding: 10px 15px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control[type="date"]:focus,
.form-control[type="time"]:focus {
    background-color: #2D2D2D;
    color: #fff;
    border-color: #2D3142;
    box-shadow: 0 0 0 0.2rem rgba(45, 49, 66, 0.25);
    outline: none;
}

.form-control[type="date"]:hover,
.form-control[type="time"]:hover {
    background-color: #2D2D2D;
    border-color: rgba(45, 49, 66, 0.5);
}

/* Mejorar visibilidad de las etiquetas */
.form-label {
    color: #fff;
    font-weight: 500;
    margin-bottom: 8px;
}

/* Mejorar visibilidad del texto de ayuda */
.form-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-top: 5px;
}
</style>
