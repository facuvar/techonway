<?php
/**
 * Technician Dashboard
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get current user
$technicianId = $_SESSION['user_id'];

// Get counts for dashboard stats
$assignedTicketsCount = $db->selectOne(
    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ?",
    [$technicianId]
)['count'];

$pendingTicketsCount = $db->selectOne(
    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'pending'",
    [$technicianId]
)['count'];

$inProgressTicketsCount = $db->selectOne(
    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'in_progress'",
    [$technicianId]
)['count'];

$completedTicketsCount = $db->selectOne(
    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'completed'",
    [$technicianId]
)['count'];

// Get assigned tickets
$assignedTickets = $db->select("
    SELECT t.id, t.description, t.status, t.created_at, 
           c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            ELSE 3
        END,
        t.created_at DESC
    LIMIT 10
", [$technicianId]);

// Page title
$pageTitle = __('tech.dashboard.title', 'Dashboard de Técnico');

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
    
    <!-- Stats Row -->
    <div class="row">
        <!-- Total Assigned Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-primary">
                <div class="stat-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $assignedTicketsCount; ?></h3>
                    <p><?php echo __('tech.dashboard.stats.assigned', 'Tickets Asignados'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Pending Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pendingTicketsCount; ?></h3>
                    <p><?php echo __('tech.dashboard.stats.pending', 'Pendientes'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- In Progress Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-info">
                <div class="stat-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $inProgressTicketsCount; ?></h3>
                    <p><?php echo __('tech.dashboard.stats.in_progress', 'En Progreso'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Completed Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completedTicketsCount; ?></h3>
                    <p><?php echo __('tech.dashboard.stats.completed', 'Completados'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assigned Tickets -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title"><?php echo __('tech.dashboard.assigned.title', 'Mis Tickets Asignados'); ?></h5>
                    <a href="my-tickets.php" class="btn btn-sm btn-outline-primary"><?php echo __('common.view_all', 'Ver Todos'); ?></a>
                </div>
                <div class="card-body">
                    <?php if (count($assignedTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                         <th><?php echo __('common.id', 'ID'); ?></th>
                                         <th><?php echo __('common.client', 'Cliente'); ?></th>
                                         <th><?php echo __('common.description', 'Descripción'); ?></th>
                                         <th><?php echo __('common.status', 'Estado'); ?></th>
                                         <th><?php echo __('common.actions', 'Acciones'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedTickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo $ticket['id']; ?></td>
                                            <td>
                                                <strong><?php echo escape($ticket['client_name']); ?></strong><br>
                                                <small><?php echo escape($ticket['business_name']); ?></small>
                                            </td>
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
                                            <td>
                                                <a href="ticket-detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> <?php echo __('common.view', 'Ver'); ?>
                                                </a>
                                                
                                                <?php if ($ticket['status'] === 'pending'): ?>
                                                 <a href="scan_qr.php?action=start&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-success">
                                                     <i class="bi bi-play-fill"></i> <?php echo __('visits.actions.start_visit', 'Iniciar Visita'); ?>
                                                </a>
                                                <?php elseif ($ticket['status'] === 'in_progress'): ?>
                                                 <a href="scan_qr.php?action=end&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-info">
                                                     <i class="bi bi-check-circle"></i> <?php echo __('visits.actions.finish', 'Finalizar'); ?>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center"><?php echo __('tech.dashboard.no_assigned_tickets', 'No tienes tickets asignados'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Access -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('tech.dashboard.quick_access', 'Acceso Rápido'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        
                        
                        <!-- Active Visit Button -->
                        <div class="col-md-12 mb-4">
                            <div class="d-grid">
                                <a href="my_active_visit.php" class="btn btn-lg btn-success">
                                    <i class="bi bi-clock-history fs-4 me-2"></i> <?php echo __('tech.dashboard.quick.active_visit', 'Ver Mi Visita Activa'); ?>
                                </a>
                                <p class="text-center mt-2 text-muted"><?php echo __('tech.dashboard.quick.active_hint', 'Acceda a su visita activa en curso para ver detalles o finalizarla'); ?></p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="my-tickets.php" class="btn btn-primary">
                                    <i class="bi bi-ticket-perforated"></i> <?php echo __('tech.menu.my_tickets', 'Mis Tickets'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="completed-visits.php" class="btn btn-primary">
                                    <i class="bi bi-check2-all"></i> <?php echo __('tech.menu.completed_visits', 'Visitas Completadas'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="<?php echo BASE_URL; ?>profile.php" class="btn btn-primary">
                                    <i class="bi bi-person"></i> <?php echo __('sidebar.profile', 'Mi Perfil'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Visit Alert (if any) -->
    <?php
    // Check if there's an active visit
    $activeVisit = $db->selectOne("
        SELECT v.id, v.ticket_id, t.description, c.name as client_name
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        WHERE t.technician_id = ? AND v.end_time IS NULL
        ORDER BY v.start_time DESC
        LIMIT 1
    ", [$technicianId]);
    
    if ($activeVisit):
    ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                <div>
                    <strong><?php echo __('visits.alert.in_progress_title', 'Visita en progreso:'); ?></strong> <?php echo __('common.ticket', 'Ticket'); ?> #<?php echo $activeVisit['ticket_id']; ?> - <?php echo escape($activeVisit['client_name']); ?>
                    <div class="mt-2">
                        <a href="active_visit.php?id=<?php echo $activeVisit['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i> <?php echo __('visits.actions.view_details', 'Ver Detalles'); ?>
                        </a>
                        <a href="scan_qr.php?action=end&visit_id=<?php echo $activeVisit['id']; ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-qr-code-scan"></i> <?php echo __('visits.actions.finish_with_qr', 'Finalizar con QR'); ?>
                        </a>
                        <button id="finalizarDirectamente" class="btn btn-sm btn-warning">
                            <i class="bi bi-check-circle"></i> <?php echo __('visits.actions.finish_direct', 'Finalizar Directamente'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>

<script>
// Botón para finalizar directamente
document.addEventListener('DOMContentLoaded', function() {
    const finalizarBtn = document.getElementById('finalizarDirectamente');
    if (finalizarBtn) {
        finalizarBtn.addEventListener('click', function() {
            // Mostrar mensaje de carga
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass"></i> ' + <?php echo json_encode(__('visits.alert.getting_location', 'Obteniendo ubicación...')); ?>;
            this.disabled = true;
            
            // Establecer un timeout para evitar que se quede congelado
            const timeoutId = setTimeout(() => {
                finalizarBtn.innerHTML = originalText;
                finalizarBtn.disabled = false;
                alert(<?php echo json_encode(__('visits.alert.operation_timeout', 'La operación ha tardado demasiado tiempo. Por favor, intente nuevamente.')); ?>);
            }, 15000); // 15 segundos de timeout
            
            // Obtener la ubicación actual
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Cancelar el timeout
                        clearTimeout(timeoutId);
                        
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Redirigir a la página de finalización con las coordenadas
                        window.location.href = `scan_qr.php?action=end&visit_id=<?php echo $activeVisit['id']; ?>&lat=${lat}&lng=${lng}`;
                    },
                    function(error) {
                        // Cancelar el timeout
                        clearTimeout(timeoutId);
                        
                        // Mostrar error específico basado en el código de error
                        let errorMsg = 'Error al obtener la ubicación.';
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = <?php echo json_encode(__('geolocation.permission_denied', 'Acceso a la ubicación denegado. Por favor, permita el acceso a su ubicación en la configuración de su navegador.')); ?>;
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = <?php echo json_encode(__('geolocation.position_unavailable', 'La información de ubicación no está disponible en este momento.')); ?>;
                                break;
                            case error.TIMEOUT:
                                errorMsg = <?php echo json_encode(__('geolocation.timeout', 'La solicitud de ubicación ha expirado.')); ?>;
                                break;
                            case error.UNKNOWN_ERROR:
                                errorMsg = <?php echo json_encode(__('geolocation.unknown_error', 'Ha ocurrido un error desconocido al obtener la ubicación.')); ?>;
                                break;
                        }
                        
                        alert(errorMsg);
                        finalizarBtn.innerHTML = originalText;
                        finalizarBtn.disabled = false;
                    },
                    { 
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                // Cancelar el timeout
                clearTimeout(timeoutId);
                
                alert(<?php echo json_encode(__('geolocation.unsupported', 'Su navegador no soporta geolocalización.')); ?>);
                finalizarBtn.innerHTML = originalText;
                finalizarBtn.disabled = false;
            }
        });
    }
});
</script>
