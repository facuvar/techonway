<?php
/**
 * My Tickets page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$clientId = $_GET['client_id'] ?? '';

// Build query conditions
$conditions = ['t.technician_id = ?'];
$params = [$technicianId];

if ($status !== 'all') {
    $conditions[] = "t.status = ?";
    $params[] = $status;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($clientId)) {
    $conditions[] = "c.id = ?";
    $params[] = $clientId;
}

// Build the WHERE clause
$whereClause = implode(' AND ', $conditions);

// Get tickets
$tickets = $db->select("
    SELECT t.*, 
           c.id as client_id, c.name as client_name, c.business_name, c.address,
           c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE $whereClause
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            WHEN t.status = 'completed' THEN 3
            WHEN t.status = 'not_completed' THEN 4
        END,
        t.created_at DESC
", $params);

// Get clients for filter dropdown
$clients = $db->select("
    SELECT DISTINCT c.id, c.name, c.business_name
    FROM clients c
    JOIN tickets t ON c.id = t.client_id
    WHERE t.technician_id = ?
    ORDER BY c.name
", [$technicianId]);

// Page title
$pageTitle = __('tech.my_tickets.title', 'Mis Tickets');

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> <?php echo __('common.back_to_dashboard', 'Volver al Dashboard'); ?>
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title"><?php echo __('common.filters', 'Filtros'); ?></h5>
        </div>
        <div class="card-body">
            <form method="get" action="my-tickets.php" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label"><?php echo __('tickets.filters.status', 'Estado'); ?></label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>><?php echo __('common.all', 'Todos'); ?></option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('tickets.status.pending', 'Pendiente'); ?></option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>><?php echo __('tickets.status.in_progress', 'En Progreso'); ?></option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo __('tickets.status.completed', 'Completado'); ?></option>
                        <option value="not_completed" <?php echo $status === 'not_completed' ? 'selected' : ''; ?>><?php echo __('tickets.status.not_completed', 'No Completado'); ?></option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label"><?php echo __('common.date_from', 'Fecha Desde'); ?></label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label"><?php echo __('common.date_to', 'Fecha Hasta'); ?></label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="client_id" class="form-label"><?php echo __('tickets.filters.client', 'Cliente'); ?></label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value=""><?php echo __('common.client_all', 'Todos los clientes'); ?></option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($client['name']) . ' - ' . escape($client['business_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> <?php echo __('common.filter', 'Filtrar'); ?>
                    </button>
                    <a href="my-tickets.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle"></i> <?php echo __('common.clear', 'Limpiar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tickets List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?php echo __('tickets.list.title', 'Lista de Tickets'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (count($tickets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo __('common.id', 'ID'); ?></th>
                                <th><?php echo __('common.client', 'Cliente'); ?></th>
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
                                    <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="ticket-detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> <?php echo __('common.view', 'Ver'); ?>
                                            </a>
                                            
                                            <?php if ($ticket['status'] === 'pending'): ?>
                                                    <a href="scan_qr.php?action=start&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-qr-code-scan"></i> <?php echo __('tickets.actions.start', 'Iniciar'); ?>
                                                </a>
                                            <?php elseif ($ticket['status'] === 'in_progress'): ?>
                                                <?php
                                                // Check if there's an active visit for this ticket
                                                $activeVisit = $db->selectOne("
                                                    SELECT id FROM visits 
                                                    WHERE ticket_id = ? AND end_time IS NULL
                                                    LIMIT 1
                                                ", [$ticket['id']]);
                                                ?>
                                                
                                                <?php if ($activeVisit): ?>
                                                    <a href="active_visit.php?id=<?php echo $activeVisit['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> <?php echo __('tickets.actions.visit', 'Visita'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="scan_qr.php?action=start&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-qr-code-scan"></i> <?php echo __('tickets.actions.continue', 'Continuar'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $ticket['latitude']; ?>,<?php echo $ticket['longitude']; ?>" 
                                                class="btn btn-sm btn-outline-secondary" target="_blank">
                                                <i class="bi bi-map"></i> <?php echo __('common.map', 'Mapa'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center"><?php echo __('tickets.list.empty_filtered', 'No hay tickets que coincidan con los filtros'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics -->
    <?php
    // Get statistics
    $totalTickets = count($tickets);
    $pendingTickets = 0;
    $inProgressTickets = 0;
    $completedTickets = 0;
    $notCompletedTickets = 0;
    
    foreach ($tickets as $ticket) {
        switch ($ticket['status']) {
            case 'pending':
                $pendingTickets++;
                break;
            case 'in_progress':
                $inProgressTickets++;
                break;
            case 'completed':
                $completedTickets++;
                break;
            case 'not_completed':
                $notCompletedTickets++;
                break;
        }
    }
    
    $completionRate = $totalTickets > 0 ? round((($completedTickets + $notCompletedTickets) / $totalTickets) * 100) : 0;
    $successRate = ($completedTickets + $notCompletedTickets) > 0 ? round(($completedTickets / ($completedTickets + $notCompletedTickets)) * 100) : 0;
    ?>
    
    <?php if ($totalTickets > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('common.statistics', 'Estadísticas'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-primary p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('common.total', 'Total'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $totalTickets; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-warning p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('tickets.status.pending', 'Pendientes'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $pendingTickets; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-info p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('tickets.status.in_progress', 'En Progreso'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $inProgressTickets; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-success p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('tickets.status.completed', 'Completados'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $completedTickets; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-danger p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('tickets.status.not_completed', 'No Completados'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $notCompletedTickets; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card bg-dark-secondary p-3 rounded text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo __('tickets.stats.success_rate', 'Tasa de Éxito'); ?>:&nbsp;</h6>
                                        <h6 class="mb-0 fw-bold"><?php echo $successRate; ?> %</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>
