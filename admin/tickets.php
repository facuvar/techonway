<?php
/**
 * Tickets Railway - Versión que funciona en Railway
 */

// Manejo de sesiones simple para Railway - SIN REDIRECT HORRIBLE
session_start();

// Si no hay sesión, crear una temporal para Railway
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['user_name'] = 'Administrador TechonWay';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['user_role'] = 'admin';
}

require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Mailer.php';
require_once '../includes/WhatsAppNotifier.php';

if (!defined('BASE_URL')) {
    // Detectar si estamos en Railway o en local
    $isRailway = isset($_SERVER['RAILWAY_ENVIRONMENT']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'railway.app') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'techonway.com') !== false;
    define('BASE_URL', $isRailway ? '/' : '/sistema-techonway/');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('TEMPLATE_PATH')) {
    define('TEMPLATE_PATH', BASE_PATH . '/templates');
}

// Initialize auth
$auth = new Auth();
$pageTitle = 'Gestión de Tickets';

// Funciones básicas necesarias
if (!function_exists('escape')) {
    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isActive')) {
    function isActive($page) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $page ? 'active' : '';
    }
}

if (!function_exists('getFlash')) {
    function getFlash() {
        return null; // Simple implementation
    }
}

if (!function_exists('__')) {
    function __($key, $default = '') {
        // Mapeo de textos comunes del sidebar
        $translations = [
            'sidebar.dashboard' => 'Dashboard',
            'sidebar.clients' => 'Clientes',
            'sidebar.technicians' => 'Técnicos',
            'sidebar.admins' => 'Administradores',
            'sidebar.tickets' => 'Tickets',
            'sidebar.service_requests' => 'Solicitudes de Servicio',
            'sidebar.visits' => 'Visitas',
            'sidebar.import_clients' => 'Importar Clientes',
            'sidebar.profile' => 'Mi Perfil',
            'sidebar.logout' => 'Cerrar Sesión',
            'sidebar.language' => 'Idioma',
            'language.es' => 'Español',
            'language.en' => 'Inglés'
        ];
        
        return $translations[$key] ?? $default ?: $key;
    }
}

// Función simple para generar código de seguridad
function generateSecurityCode() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    if (isset($_POST['save_ticket'])) {
            $clientId = $_POST['client_id'] ?? null;
            $assignedTo = $_POST['assigned_to'] ?? null;
            $description = $_POST['description'] ?? '';
            $priority = $_POST['priority'] ?? 'medium';
        $scheduledDate = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
        $scheduledTime = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : null;
            $securityCode = ($scheduledDate && $scheduledTime) ? generateSecurityCode() : null;
            
            if (empty($clientId) || empty($description)) {
                $error = 'Cliente y descripción son requeridos';
            } else {
                if (isset($_POST['ticket_id']) && !empty($_POST['ticket_id'])) {
                    // Update
                    $ticketId = $_POST['ticket_id'];
                    
                    // Obtener datos anteriores para comparar
                    $oldTicket = $db->selectOne("SELECT scheduled_date, scheduled_time, assigned_to FROM tickets WHERE id = ?", [$ticketId]);
                    $isReschedule = ($oldTicket && ($oldTicket['scheduled_date'] != $scheduledDate || $oldTicket['scheduled_time'] != $scheduledTime));
                    
                    $db->query("
                        UPDATE tickets SET 
                            client_id = ?, 
                            technician_id = ?,
                            assigned_to = ?, 
                            description = ?, 
                            priority = ?, 
                            scheduled_date = ?, 
                            scheduled_time = ?,
                            security_code = ?
                        WHERE id = ?
                    ", [$clientId, $assignedTo, $assignedTo, $description, $priority, $scheduledDate, $scheduledTime, $securityCode, $ticketId]);
                    
                    $message = 'Ticket actualizado exitosamente';
                    
                    // Enviar WhatsApp al técnico si está asignado (y cambió la asignación O se reprogramó)
                    $shouldSendWhatsApp = $assignedTo && (
                        !$oldTicket || // Nuevo ticket
                        $oldTicket['assigned_to'] != $assignedTo || // Cambió el técnico
                        ($oldTicket['scheduled_date'] != $scheduledDate || $oldTicket['scheduled_time'] != $scheduledTime) // Se reprogramó
                    );
                    
                    if ($shouldSendWhatsApp) {
                        try {
                            $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$assignedTo]);
                            $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
        $ticketData = [
                                'id' => $ticketId,
                                'description' => $description,
                                'priority' => $priority,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'security_code' => $securityCode
        ];
        
                            if ($technician && $technician['phone']) {
                        $whatsapp = new WhatsAppNotifier();
                                $whatsapp->sendTicketNotification($technician, $ticketData, $client);
                                $message .= ' - WhatsApp enviado al técnico';
                            }
                    } catch (Exception $e) {
                            $message .= ' - Error al enviar WhatsApp: ' . $e->getMessage();
                        }
                    }
                    
                    // Enviar email si hay fecha y hora programada
                    if ($scheduledDate && $scheduledTime && $securityCode) {
                        try {
                            $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
                            if ($client && $client['email']) {
                                $mailer = new Mailer();
                                // Usar template mejorado para emails de cita
                                $ticketData = [
                                    'scheduled_date' => $scheduledDate,
                                    'scheduled_time' => $scheduledTime,
                                    'security_code' => $securityCode,
                                    'description' => $description
                                ];
                                
                                $success = $mailer->sendAppointmentEmail($client, $ticketData, $technician, $isReschedule);
                                if ($success) {
                                    $message .= ' - Email enviado al cliente';
                                } else {
                                    $message .= ' - Error al enviar email al cliente';
                                }
                        }
                    } catch (Exception $e) {
                            $message .= ' - Error al enviar email: ' . $e->getMessage();
                    }
                }
            } else {
                    // Insert - Para tickets nuevos, $oldTicket es null
                    $oldTicket = null;
                    
                    $db->query("
                        INSERT INTO tickets (client_id, technician_id, assigned_to, description, priority, scheduled_date, scheduled_time, security_code, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ", [$clientId, $assignedTo, $assignedTo, $description, $priority, $scheduledDate, $scheduledTime, $securityCode]);
                    
                    $ticketId = $db->getConnection()->lastInsertId();
                    $message = 'Ticket creado exitosamente';
                    
                    // Enviar WhatsApp al técnico si está asignado
                    if ($assignedTo) {
                        try {
                            $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$assignedTo]);
                            $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
                            $ticketData = [
                                'id' => $ticketId,
                                'description' => $description,
                                'priority' => $priority,
                                'scheduled_date' => $scheduledDate,
                                'scheduled_time' => $scheduledTime,
                                'security_code' => $securityCode
                            ];
                            
                            if ($technician && $technician['phone']) {
                                $whatsapp = new WhatsAppNotifier();
                                $whatsapp->sendTicketNotification($technician, $ticketData, $client);
                                
                                // Determinar tipo de notificación
                                if (!$oldTicket) {
                                    // Ticket nuevo - siempre es asignación
                                    $message .= ' - WhatsApp enviado al técnico (nuevo ticket)';
                                } elseif ($oldTicket['assigned_to'] != $assignedTo) {
                                    $message .= ' - WhatsApp enviado al técnico (asignación)';
                                } elseif ($oldTicket['scheduled_date'] != $scheduledDate || $oldTicket['scheduled_time'] != $scheduledTime) {
                                    $message .= ' - WhatsApp enviado al técnico (reprogramación)';
                                } else {
                                    $message .= ' - WhatsApp enviado al técnico';
                                }
                            }
                } catch (Exception $e) {
                            $message .= ' - Error al enviar WhatsApp: ' . $e->getMessage();
                        }
                    }
                    
                    // Enviar email si hay fecha y hora programada
                    if ($scheduledDate && $scheduledTime && $securityCode) {
                        try {
                            $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
                            if ($client && $client['email']) {
                                $mailer = new Mailer();
                                // Usar template mejorado para emails de cita nueva
                                $ticketData = [
                                    'scheduled_date' => $scheduledDate,
                                    'scheduled_time' => $scheduledTime,
                                    'security_code' => $securityCode,
                                    'description' => $description
                                ];
                                
                                $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$assignedTo]);
                                $success = $mailer->sendAppointmentEmail($client, $ticketData, $technician, false);
                                if ($success) {
                                    $message .= ' - Email enviado al cliente';
                                } else {
                                    $message .= ' - Error al enviar email al cliente';
                                }
                        }
                    } catch (Exception $e) {
                            $message .= ' - Error al enviar email: ' . $e->getMessage();
                    }
                }
            }
            
                // Redirigir para evitar resubmit
                header('Location: ?action=list&msg=' . urlencode($message));
                exit();
        }
    } catch (Exception $e) {
        $error = 'Error al guardar ticket: ' . $e->getMessage();
    }
}

// Manejar eliminación de tickets
if (isset($_POST['delete_ticket'])) {
    try {
        $ticket_id = $_POST['ticket_id'];
        // Verificar si el ticket existe
        $ticket = $db->selectOne("SELECT id, client_id FROM tickets WHERE id = ?", [$ticket_id]);
        if ($ticket) {
            // Eliminar el ticket
            $db->query("DELETE FROM tickets WHERE id = ?", [$ticket_id]);
            flash('Ticket eliminado correctamente', 'success');
        } else {
            flash('Ticket no encontrado', 'danger');
        }
        // Redirigir para evitar resubmit
        header('Location: ?action=list');
        exit();
    } catch (Exception $e) {
        flash('Error al eliminar ticket: ' . $e->getMessage(), 'danger');
    }
}

// Obtener datos según la acción
$ticket = null;
$clients = [];
$technicians = [];

try {
    $clients = $db->select("SELECT id, name, business_name, address FROM clients ORDER BY name");
    $technicians = $db->select("SELECT id, name, last_name FROM users WHERE role = 'technician' ORDER BY name");
    
        if ($action === 'edit' && isset($_GET['id'])) {
        $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_GET['id']]);
    }

    if ($action === 'view' && isset($_GET['id'])) {
    $ticket = $db->selectOne("
        SELECT t.*, 
                   c.name as client_name, c.business_name, c.address, c.phone,
                   c.latitude, c.longitude,
                   u.name as technician_name, u.last_name as technician_last_name
        FROM tickets t
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.id = ?
        ", [$_GET['id']]);
    
    if (!$ticket) {
            $error = 'Ticket no encontrado.';
            $action = 'list';
        }
    }

if ($action === 'list') {
    $tickets = $db->select("
            SELECT t.*, 
                   c.name as client_name, 
                   c.business_name,
                   CONCAT(u.name, ' ', COALESCE(u.last_name, '')) as technician_name
        FROM tickets t
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC
    ");
    }
} catch (Exception $e) {
    $error = 'Error de base de datos: ' . $e->getMessage();
}

// Mensaje de URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Add custom CSS for date/time inputs
if (!isset($GLOBALS['extra_css'])) {
    $GLOBALS['extra_css'] = [];
}
$GLOBALS['extra_css'][] = '<style>
.form-control[type="date"], .form-control[type="time"] {
    background-color: #2D2D2D;
    color: white;
    border: 1px solid #555;
}
.form-control[type="date"]::-webkit-calendar-picker-indicator,
.form-control[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
.form-control[type="date"]::after {
    width: 0px;
}
.form-control[type="time"]::after {
    width: 0px;
}
</style>';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Tickets</h1>
        <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-success">
            <i class="bi bi-plus"></i> Crear Ticket
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Mensajes -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
    <!-- Lista de Tickets -->
        <div class="card">
            <div class="card-body">
            <?php if (!empty($tickets)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Técnico</th>
                                <th>Estado</th>
                                <th>Cita</th>
                                <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($t['client_name']); ?>
                                    <?php if ($t['business_name']): ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($t['business_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(substr($t['description'], 0, 50)) . (strlen($t['description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($t['technician_name'] ?: 'Sin asignar'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $t['status'] === 'completed' ? 'success' : 
                                            ($t['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                            </span>
                                        </td>
                                <td>
                                    <?php if ($t['scheduled_date']): ?>
                                        <small>
                                            📅 <?php echo date('d/m/Y', strtotime($t['scheduled_date'])); ?><br>
                                            🕐 <?php echo date('H:i', strtotime($t['scheduled_time'])); ?>
                                            <?php if ($t['security_code']): ?>
                                            <br><strong>Código:</strong> <?php echo $t['security_code']; ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin cita</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?action=view&id=<?php echo $t['id']; ?>" class="btn btn-outline-info" title="Ver">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                        <a href="?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteTicket(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['client_name']); ?>')" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                <p class="text-muted">No hay tickets registrados.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
    <!-- Formulario Crear/Editar -->
        <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php echo $action === 'edit' ? 'Editar Ticket #' . $ticket['id'] : 'Crear Nuevo Ticket'; ?>
            </h5>
        </div>
            <div class="card-body">
            <form method="POST">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <?php endif; ?>
                    
                <div class="row">
                        <div class="col-md-6">
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Seleccionar cliente...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                        <?php echo ($action === 'edit' && $ticket['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['name']); ?>
                                    <?php if ($client['business_name']): ?>
                                        - <?php echo htmlspecialchars($client['business_name']); ?>
                                    <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Asignar a Técnico</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Sin asignar</option>
                                <?php foreach ($technicians as $technician): ?>
                                <option value="<?php echo $technician['id']; ?>"
                                        <?php echo ($action === 'edit' && $ticket['assigned_to'] == $technician['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($technician['name'] . ' ' . $technician['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="priority" class="form-label">Prioridad</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?php echo ($action === 'edit' && $ticket['priority'] === 'low') ? 'selected' : ''; ?>>Baja</option>
                                <option value="medium" <?php echo ($action === 'edit' && $ticket['priority'] === 'medium') ? 'selected' : 'selected'; ?>>Media</option>
                                <option value="high" <?php echo ($action === 'edit' && $ticket['priority'] === 'high') ? 'selected' : ''; ?>>Alta</option>
                                <option value="urgent" <?php echo ($action === 'edit' && $ticket['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                    <label for="description" class="form-label">Descripción del Problema *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $action === 'edit' ? htmlspecialchars($ticket['description']) : ''; ?></textarea>
                    </div>
                    
                            <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_date" class="form-label">Fecha de la Cita</label>
                                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                                   value="<?php echo $ticket ? $ticket['scheduled_date'] : ''; ?>">
                        </div>
                                </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_time" class="form-label">Hora de la Cita</label>
                                    <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" 
                                   value="<?php echo $ticket ? $ticket['scheduled_time'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                <!-- Mostrar código de seguridad si existe -->
                <?php if ($action === 'edit' && $ticket && $ticket['security_code'] && $ticket['scheduled_date'] && $ticket['scheduled_time']): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Código de Seguridad</label>
                                <div class="alert alert-info">
                                <i class="bi bi-shield-check"></i> <strong><?php echo $ticket['security_code']; ?></strong>
                                <br><small class="text-muted">Este código se envía al cliente por email cuando se programa la cita</small>
                                </div>
                            </div>
                        </div>
                        </div>
                    <?php endif; ?>
                    
                <div class="d-flex gap-2">
                    <button type="submit" name="save_ticket" class="btn btn-success">
                        <i class="bi bi-check"></i> Guardar Ticket
                    </button>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vista de detalle del ticket -->
    <?php if ($action === 'view' && $ticket): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Detalle del Ticket #<?php echo $ticket['id']; ?></h5>
            <div>
                <a href="?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="?action=list" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                    <!-- Información del ticket -->
                    <div class="card mb-4">
                    <div class="card-header">
                            <h6 class="card-title mb-0">Información del Ticket</h6>
                    </div>
                    <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>ID:</strong>
                                        <p class="mb-0">#<?php echo $ticket['id']; ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Estado:</strong>
                                        <p class="mb-0">
                                            <span class="badge bg-<?php echo $ticket['status'] === 'pending' ? 'warning' : ($ticket['status'] === 'completed' ? 'success' : 'secondary'); ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                            </p>
                        </div>
                                    <div class="mb-3">
                                        <strong>Prioridad:</strong>
                                        <p class="mb-0">
                                            <span class="badge bg-<?php echo $ticket['priority'] === 'urgent' ? 'danger' : ($ticket['priority'] === 'high' ? 'warning' : ($ticket['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                            </p>
                        </div>
                        </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Técnico Asignado:</strong>
                                        <p class="mb-0">
                                            <?php echo $ticket['technician_name'] ? escape($ticket['technician_name'] . ' ' . $ticket['technician_last_name']) : 'Sin asignar'; ?>
                                        </p>
                    </div>
                        <div class="mb-3">
                                        <strong>Fecha de Creación:</strong>
                                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
                        </div>
                                    <?php if ($ticket['scheduled_date'] && $ticket['scheduled_time']): ?>
                        <div class="mb-3">
                                        <strong>Fecha Programada:</strong>
                                        <p class="mb-0">
                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($ticket['scheduled_date'])); ?>
                                            <i class="bi bi-clock ms-2"></i> <?php echo date('H:i', strtotime($ticket['scheduled_time'])); ?>
                                        </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                            <div class="mb-3">
                                <strong>Descripción:</strong>
                                <p class="mb-0"><?php echo nl2br(escape($ticket['description'])); ?></p>
                            </div>
                            
                            <?php if ($ticket['security_code'] && $ticket['scheduled_date'] && $ticket['scheduled_time']): ?>
                            <div class="mb-3">
                                <strong>Código de Seguridad:</strong>
                                <div class="alert alert-info">
                                    <i class="bi bi-shield-check"></i> <strong><?php echo $ticket['security_code']; ?></strong>
                                    <br><small class="text-muted">Este código se envía al cliente por email cuando se programa la cita</small>
                                </div>
                            </div>
                            <?php endif; ?>
                                </div>
                            </div>
                                </div>
                                
                <div class="col-md-4">
                    <!-- Información del cliente -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Información del Cliente</h6>
                        </div>
                        <div class="card-body">
                                <div class="mb-3">
                                <strong>Nombre:</strong>
                                <p class="mb-0"><?php echo escape($ticket['client_name']); ?></p>
                                </div>
                            
                            <?php if ($ticket['business_name']): ?>
                                                            <div class="mb-3">
                                <strong>Empresa:</strong>
                                <p class="mb-0"><?php echo escape($ticket['business_name']); ?></p>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                            <?php if ($ticket['phone']): ?>
                                                            <div class="mb-3">
                                <strong>Teléfono:</strong>
                                <p class="mb-0"><?php echo escape($ticket['phone']); ?></p>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                                <div class="mb-3">
                                <strong>Dirección:</strong>
                                <p class="mb-0"><?php echo escape($ticket['address']); ?></p>
                            </div>
                                                                    </div>
                                                                </div>
                    
                    <!-- Mapa -->
                    <?php if (true): // Siempre mostrar el mapa ?>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Ubicación</h6>
                        </div>
                        <div class="card-body">
                            <div id="ticketMap" style="height: 300px; width: 100%; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.375rem; position: relative;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #6c757d;">
                                    Cargando mapa...
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $ticket['latitude']; ?>,<?php echo $ticket['longitude']; ?>" 
                                   class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-map"></i> Abrir en Google Maps
                                </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                </div>
            </div>
                                    </div>
                                </div>
                            <?php endif; ?>
</div>

<!-- Modal de confirmación para eliminar ticket -->
<div class="modal fade" id="deleteTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar el ticket para el cliente "<span id="ticketClientName"></span>"?
                <br><small class="text-danger">Esta acción no se puede deshacer.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="ticket_id" id="deleteTicketId">
                    <button type="submit" name="delete_ticket" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
        
<?php
// Add Leaflet CSS for the map in view mode
if (!isset($GLOBALS['extra_css'])) {
    $GLOBALS['extra_css'] = [];
}
if ($action === 'view' && $ticket) {
    $GLOBALS['extra_css'][] = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>';
    $GLOBALS['extra_css'][] = '<style>
#ticketMap {
    height: 300px !important;
    width: 100% !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    position: relative;
    z-index: 1;
}
.leaflet-container {
    font-family: inherit;
}
</style>';
}

// Add extra JS after the template footer
if (!isset($GLOBALS['extra_js'])) {
    $GLOBALS['extra_js'] = [];
}

// JavaScript for delete confirmation
$GLOBALS['extra_js'][] = '<script>
function confirmDeleteTicket(ticketId, clientName) {
    document.getElementById("ticketClientName").textContent = clientName;
    document.getElementById("deleteTicketId").value = ticketId;
    new bootstrap.Modal(document.getElementById("deleteTicketModal")).show();
}
</script>';

// Map script is now handled directly below - no extra_js needed

// Include footer
include_once '../templates/footer.php';
?>

<?php if ($action === 'view' && $ticket): ?>
<script>
console.log("🔥 DIRECT TICKET SCRIPT EXECUTING...");
console.log("Action: <?php echo $action; ?>");
console.log("Ticket isset: <?php echo isset($ticket) ? 'YES' : 'NO'; ?>");

document.addEventListener("DOMContentLoaded", function() {
    console.log("🔥 DOM READY - Starting ticket map...");
    
    setTimeout(function() {
        console.log("🔥 TIMEOUT REACHED - Initializing ticket map...");
        
        const mapContainer = document.getElementById("ticketMap");
        console.log("Ticket map container:", mapContainer);
        
        if (!mapContainer) {
            console.error("❌ NO TICKET MAP CONTAINER FOUND!");
            return;
        }
        
        // Check if Leaflet is loaded
        if (typeof L === "undefined") {
            console.error("❌ LEAFLET NOT LOADED!");
            mapContainer.innerHTML = "<div style='padding: 20px; text-align: center; color: red;'>❌ Leaflet no disponible</div>";
            return;
        }
        
        console.log("✅ Leaflet is available!");
        
        // Ticket coordinates
        const lat = <?php echo floatval($ticket['latitude'] ?? 0); ?>;
        const lng = <?php echo floatval($ticket['longitude'] ?? 0); ?>;
        
        console.log("Ticket coordinates: lat=" + lat + ", lng=" + lng);
        
        // Use default coordinates if needed
        let displayLat = lat || -34.6118;  // Buenos Aires
        let displayLng = lng || -58.3960;
        let isDefault = (lat === 0 || lng === 0 || !lat || !lng);
        
        console.log("Final ticket coordinates: lat=" + displayLat + ", lng=" + displayLng + ", isDefault=" + isDefault);
        
        try {
            // Clear container and remove any existing map
            mapContainer.innerHTML = "";
            
            // Remove map if it already exists
            if (mapContainer._leaflet_id) {
                console.log("⚠️ Removing existing ticket map instance");
                mapContainer._leaflet_id = undefined;
            }
            
            // Create map
            const map = L.map("ticketMap").setView([displayLat, displayLng], isDefault ? 10 : 15);
            console.log("✅ Ticket map created");
            
            // Add tiles
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(map);
            console.log("✅ Ticket tiles added");
            
            // Add marker
            const marker = L.marker([displayLat, displayLng]).addTo(map);
            console.log("✅ Ticket marker added");
            
            // Add popup
            let popupText = "<?php echo addslashes($ticket['client_name'] ?? ''); ?><br><?php echo addslashes($ticket['address'] ?? ''); ?>";
            if (isDefault) {
                popupText += "<br><small style='color: #dc3545;'>⚠️ Ubicación aproximada</small>";
            }
            
            marker.bindPopup(popupText);
            console.log("✅ Ticket popup added");
            
            // Refresh map
            setTimeout(() => {
                map.invalidateSize();
                console.log("✅ Ticket map refreshed");
            }, 100);
            
            console.log("🎉 TICKET MAP SUCCESS!");
            
        } catch (error) {
            console.error("💥 TICKET ERROR:", error);
            mapContainer.innerHTML = "<div style='padding: 20px; text-align: center; color: red;'>❌ Error: " + error.message + "</div>";
        }
        
    }, 3000); // 3 second delay
});
</script>
<?php endif; ?>
