<?php
/**
 * Tickets Railway - Versión que funciona en Railway
 */

// Manejo de sesiones similar al calendar
session_start();

// Permitir acceso con token desde dashboard o verificar sesión
$validAccess = false;
if (isset($_GET['token']) && $_GET['token'] === 'dashboard_access') {
    $validAccess = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrador TechonWay';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['role'] = 'admin';
} else if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $validAccess = true;
}

if (!$validAccess) {
    header('Location: /admin/force_login_and_calendar.php');
    exit();
}

require_once '../includes/Database.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
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
                    $db->query("
                        UPDATE tickets SET 
                            client_id = ?, 
                            assigned_to = ?, 
                            description = ?, 
                            priority = ?, 
                            scheduled_date = ?, 
                            scheduled_time = ?,
                            security_code = ?
                        WHERE id = ?
                    ", [$clientId, $assignedTo, $description, $priority, $scheduledDate, $scheduledTime, $securityCode, $ticketId]);
                    $message = 'Ticket actualizado exitosamente';
                } else {
                    // Insert
                    $db->query("
                        INSERT INTO tickets (client_id, assigned_to, description, priority, scheduled_date, scheduled_time, security_code, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ", [$clientId, $assignedTo, $description, $priority, $scheduledDate, $scheduledTime, $securityCode]);
                    $message = 'Ticket creado exitosamente';
                }
                
                // Redirigir para evitar resubmit
                header('Location: ?action=list&msg=' . urlencode($message));
                exit();
            }
        }
    } catch (Exception $e) {
        $error = 'Error al guardar ticket: ' . $e->getMessage();
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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - TechonWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
    /* Estilos del panel admin */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background: linear-gradient(180deg, #2D3142 0%, #3A3F58 100%);
        color: white;
        z-index: 1000;
        overflow-y: auto;
    }
    .main-content {
        margin-left: 250px;
        min-height: 100vh;
        background: #f8f9fa;
    }
    .sidebar .nav-link {
        color: #bbb;
        padding: 12px 20px;
        text-decoration: none;
    }
    .sidebar .nav-link:hover {
        background: rgba(91, 99, 134, 0.3);
        color: white;
    }
    .sidebar .nav-link.active {
        background: #5B6386;
        color: white;
    }
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3 text-center border-bottom">
            <img src="/assets/img/logo.png" alt="Logo" style="max-height: 50px;">
            <h5 class="mt-2 mb-0">TechonWay</h5>
        </div>
        <div class="p-3 text-center border-bottom">
            <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
            <div class="mt-2"><?php echo $_SESSION['user_name']; ?></div>
            <small class="text-light">Administrador</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/clients.php">
                    <i class="bi bi-building"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/technicians.php">
                    <i class="bi bi-person-gear"></i> Técnicos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="/admin/tickets.php">
                    <i class="bi bi-ticket-perforated"></i> Tickets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/calendar.php">
                    <i class="bi bi-calendar-event"></i> Calendario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/visits.php">
                    <i class="bi bi-clipboard-check"></i> Visitas
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>🎫 Gestión de Tickets</h1>
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
                <div class="card-header">
                    <h5 class="mb-0">Lista de Tickets</h5>
                </div>
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
                                        <a href="?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
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
                                    <label class="form-label">Cliente *</label>
                                    <select name="client_id" class="form-select" required>
                                        <option value="">Seleccionar cliente...</option>
                                        <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" 
                                                <?php echo ($ticket && $ticket['client_id'] == $client['id']) ? 'selected' : ''; ?>>
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
                                    <label class="form-label">Técnico Asignado</label>
                                    <select name="assigned_to" class="form-select">
                                        <option value="">Sin asignar</option>
                                        <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>"
                                                <?php echo ($ticket && $ticket['assigned_to'] == $tech['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['name'] . ' ' . ($tech['last_name'] ?: '')); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción *</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo $ticket ? htmlspecialchars($ticket['description']) : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Prioridad</label>
                                    <select name="priority" class="form-select">
                                        <option value="low" <?php echo ($ticket && $ticket['priority'] === 'low') ? 'selected' : ''; ?>>Baja</option>
                                        <option value="medium" <?php echo (!$ticket || $ticket['priority'] === 'medium') ? 'selected' : ''; ?>>Media</option>
                                        <option value="high" <?php echo ($ticket && $ticket['priority'] === 'high') ? 'selected' : ''; ?>>Alta</option>
                                        <option value="urgent" <?php echo ($ticket && $ticket['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de la Cita</label>
                                    <input type="date" name="scheduled_date" class="form-control" 
                                           value="<?php echo $ticket ? $ticket['scheduled_date'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Hora de la Cita</label>
                                    <input type="time" name="scheduled_time" class="form-control" 
                                           value="<?php echo $ticket ? $ticket['scheduled_time'] : ''; ?>">
                                </div>
                            </div>
                        </div>

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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
