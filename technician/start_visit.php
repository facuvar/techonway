<?php
/**
 * Start Visit without QR Code Page
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get ticket ID from query string
$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    flash('ID de ticket no proporcionado.', 'danger');
    redirect('dashboard.php');
}

// Get ticket details
$ticket = $db->selectOne("
    SELECT t.*, 
           c.name as client_name, c.business_name, c.address,
           c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.id = ? AND (t.technician_id = ? OR t.assigned_to = ?)
", [$ticketId, $technicianId, $technicianId]);

if (!$ticket) {
    flash('Ticket no encontrado o no asignado a usted.', 'danger');
    redirect('dashboard.php');
}

// Check if there's already an active visit
$activeVisit = $db->selectOne("
    SELECT * FROM visits 
    WHERE ticket_id = ? AND end_time IS NULL
", [$ticketId]);

if ($activeVisit) {
    flash('Ya existe una visita activa para este ticket.', 'warning');
    redirect('/technician/active_visit.php?id=' . $activeVisit['id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime = date('Y-m-d H:i:s');
    $notes = $_POST['start_notes'] ?? '';
    
    try {
        // Start the visit
        $visitId = $db->insert('visits', [
            'ticket_id' => $ticketId,
            'start_time' => $startTime,
            'start_notes' => $notes,
            'latitude' => null, // No GPS verification for manual start
            'longitude' => null
        ]);
        
        // Update ticket status to in_progress
        $db->update('tickets', [
            'status' => 'in_progress'
        ], 'id = ?', [$ticketId]);
        
        flash('Visita iniciada correctamente sin verificación QR.', 'success');
        redirect('/technician/active_visit.php?id=' . $visitId);
        
    } catch (Exception $e) {
        flash('Error al iniciar la visita: ' . $e->getMessage(), 'danger');
    }
}

// Page title
$pageTitle = 'Iniciar Visita Sin QR - Ticket #' . $ticketId;

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-play-circle"></i> Iniciar Visita Sin QR
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Atención:</strong> Está iniciando una visita sin verificación QR. 
                        Asegúrese de estar en la ubicación correcta del cliente.
                    </div>
                    
                    <!-- Ticket Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Información del Ticket</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Cliente:</strong> <?php echo escape($ticket['client_name']); ?></p>
                                    <p><strong>Empresa:</strong> <?php echo escape($ticket['business_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Dirección:</strong> <?php echo escape($ticket['address']); ?></p>
                                    <p><strong>Estado:</strong> 
                                        <span class="badge bg-warning">Pendiente</span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <strong>Descripción:</strong>
                                <p class="mt-2"><?php echo nl2br(escape($ticket['description'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Start Visit Form -->
                    <form method="POST">
                        <div class="mb-4">
                            <label for="start_notes" class="form-label">
                                <i class="bi bi-journal-text"></i> Notas de Inicio (Opcional)
                            </label>
                            <textarea class="form-control" id="start_notes" name="start_notes" rows="4" 
                                      placeholder="Escriba cualquier observación inicial sobre el trabajo a realizar..."></textarea>
                            <div class="form-text">
                                Registre cualquier información relevante antes de iniciar el trabajo.
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Información:</strong>
                            <ul class="mb-0 mt-2">
                                <li>La visita se iniciará inmediatamente sin verificación de ubicación</li>
                                <li>Asegúrese de estar en el lugar correcto antes de continuar</li>
                                <li>Podrá finalizar la visita más tarde desde "Ver Visita Activa"</li>
                                <li>Se marcará como "Sin verificación QR" en el sistema</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="ticket-detail.php?id=<?php echo $ticketId; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-play-circle"></i> Iniciar Visita Ahora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
