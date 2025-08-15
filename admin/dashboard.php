<?php
/**
 * Admin Dashboard
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get counts for dashboard stats
$clientsCount = $db->selectOne("SELECT COUNT(*) as count FROM clients")['count'];
$techniciansCount = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'technician'")['count'];
$ticketsCount = $db->selectOne("SELECT COUNT(*) as count FROM tickets")['count'];

// Get tickets by status
$ticketsByStatus = $db->select("
    SELECT status, COUNT(*) as count 
    FROM tickets 
    GROUP BY status
");

// Format status counts for easier access
$statusCounts = [
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'not_completed' => 0
];

foreach ($ticketsByStatus as $status) {
    $statusCounts[$status['status']] = $status['count'];
}

// Get recent tickets
$recentTickets = $db->select("
    SELECT t.id, t.description, t.status, t.created_at, 
           c.name as client_name, u.name as technician_name
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Page title
$pageTitle = __('dashboard.title', 'Dashboard de Administrador');

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
    
    <!-- Stats Row -->
    <div class="row">
        <!-- Clients Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-primary">
                <div class="stat-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $clientsCount; ?></h3>
                    <p><?php echo __('dashboard.stats.clients', 'Clientes'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Technicians Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-secondary">
                <div class="stat-icon">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $techniciansCount; ?></h3>
                    <p><?php echo __('dashboard.stats.technicians', 'Técnicos'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tickets Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-info">
                <div class="stat-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $ticketsCount; ?></h3>
                    <p><?php echo __('dashboard.stats.tickets_total', 'Tickets Totales'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Completed Visits Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $statusCounts['completed']; ?></h3>
                    <p><?php echo __('dashboard.stats.visits_completed', 'Visitas Completadas'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tickets Status Chart and Recent Tickets -->
    <div class="row mt-4">
        <!-- Tickets Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('dashboard.status.title', 'Estado de Tickets'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo __('dashboard.status.header.state', 'Estado'); ?></th>
                                    <th><?php echo __('dashboard.status.header.count', 'Cantidad'); ?></th>
                                    <th><?php echo __('dashboard.status.header.percent', 'Porcentaje'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning"><?php echo __('dashboard.status.state.pending', 'Pendientes'); ?></span>
                                    </td>
                                    <td><?php echo $statusCounts['pending']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['pending'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-info"><?php echo __('dashboard.status.state.in_progress', 'En Progreso'); ?></span>
                                    </td>
                                    <td><?php echo $statusCounts['in_progress']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['in_progress'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-success"><?php echo __('dashboard.status.state.completed', 'Completados'); ?></span>
                                    </td>
                                    <td><?php echo $statusCounts['completed']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['completed'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger"><?php echo __('dashboard.status.state.not_completed', 'No Completados'); ?></span>
                                    </td>
                                    <td><?php echo $statusCounts['not_completed']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['not_completed'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title"><?php echo __('dashboard.recent.title', 'Tickets Recientes'); ?></h5>
                    <a href="tickets.php" class="btn btn-sm btn-primary"><?php echo __('dashboard.recent.view_all', 'Ver Todos'); ?></a>
                </div>
                <div class="card-body">
                    <?php if (count($recentTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('dashboard.recent.header.id', 'ID'); ?></th>
                                        <th><?php echo __('dashboard.recent.header.client', 'Cliente'); ?></th>
                                        <th><?php echo __('dashboard.recent.header.technician', 'Técnico'); ?></th>
                                        <th><?php echo __('dashboard.recent.header.status', 'Estado'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo $ticket['id']; ?></td>
                                            <td><?php echo escape($ticket['client_name']); ?></td>
                                            <td><?php echo escape($ticket['technician_name']); ?></td>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No hay tickets recientes</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-chat-dots text-white"></i> <?php echo __('dashboard.notifications.title', 'Notificaciones'); ?>
                    </h5>
                    <p class="card-text"><?php echo __('dashboard.notifications.desc', 'Gestione y pruebe las notificaciones por WhatsApp para técnicos.'); ?></p>
                    <div class="d-grid gap-2">
                        <a href="../test_whatsapp_direct.php" class="btn btn-success">
                            <i class="bi bi-tools"></i> <?php echo __('dashboard.notifications.button', 'Diagnóstico de WhatsApp'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo __('dashboard.whatsapp_tools.title', 'Herramientas de WhatsApp'); ?></h5>
                    <p class="card-text"><?php echo __('dashboard.whatsapp_tools.desc', 'Herramientas para diagnosticar y probar el sistema de notificaciones por WhatsApp.'); ?></p>
                    <a href="<?php echo BASE_URL; ?>admin/whatsapp_debug.php" class="btn btn-primary">
                        <i class="bi bi-whatsapp"></i> <?php echo __('dashboard.whatsapp_tools.button', 'Diagnóstico Avanzado de WhatsApp'); ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('dashboard.quick_actions.title', 'Acciones Rápidas'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="clients.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> <?php echo __('dashboard.quick_actions.new_client', 'Nuevo Cliente'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="technicians.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> <?php echo __('dashboard.quick_actions.new_technician', 'Nuevo Técnico'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="tickets.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> <?php echo __('dashboard.quick_actions.new_ticket', 'Nuevo Ticket'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="visits.php" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-clipboard-check"></i> <?php echo __('dashboard.quick_actions.view_visits', 'Ver Visitas'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
