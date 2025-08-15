<?php
/**
 * Vista de calendario para administradores
 * Muestra todas las citas programadas en un calendario interactivo
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get month and year from query parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Validate month and year
$month = max(1, min(12, intval($month)));
$year = max(2020, min(2030, intval($year)));

// Get tickets with scheduled appointments for the selected month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate)); // Last day of the month

$scheduledTickets = $db->select("
    SELECT t.*, 
           c.name as client_name,
           c.business_name,
           c.address,
           c.email as client_email,
           u.name as technician_name,
           u.zone as technician_zone
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    WHERE t.scheduled_date IS NOT NULL 
    AND t.scheduled_date BETWEEN ? AND ?
    ORDER BY t.scheduled_date, t.scheduled_time
", [$startDate, $endDate]);

// Organize tickets by date
$ticketsByDate = [];
foreach ($scheduledTickets as $ticket) {
    $date = $ticket['scheduled_date'];
    if (!isset($ticketsByDate[$date])) {
        $ticketsByDate[$date] = [];
    }
    $ticketsByDate[$date][] = $ticket;
}

// Calculate navigation dates
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear = $month == 12 ? $year + 1 : $year;

// Month names in Spanish
$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Get calendar data
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay); // 0 = Sunday, 6 = Saturday
$today = date('Y-m-d');

// Page title
$pageTitle = "Calendario de Citas - {$monthNames[$month]} {$year}";

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-calendar-event"></i> Calendario de Citas Programadas
        </h1>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Ticket
            </a>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="btn btn-outline-primary">
                <i class="bi bi-list"></i> Lista de Tickets
            </a>
        </div>
    </div>
    
    <!-- Calendar Navigation -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                       class="btn btn-month-nav">
                        <i class="bi bi-chevron-left"></i> <?php echo $monthNames[$prevMonth]; ?>
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <h4 class="mb-0"><?php echo $monthNames[$month] . ' ' . $year; ?></h4>
                </div>
                <div class="col-md-4 text-end">
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                       class="btn btn-month-nav">
                        <?php echo $monthNames[$nextMonth]; ?> <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Calendar Grid -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr class="table-primary">
                            <th class="text-center">Domingo</th>
                            <th class="text-center">Lunes</th>
                            <th class="text-center">Martes</th>
                            <th class="text-center">Miércoles</th>
                            <th class="text-center">Jueves</th>
                            <th class="text-center">Viernes</th>
                            <th class="text-center">Sábado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $currentDate = 1;
                        $weekCounter = 0;
                        
                        // Generate calendar weeks
                        while ($currentDate <= $daysInMonth) {
                            echo "<tr>";
                            
                            // Generate 7 days for the week
                            for ($dayOfWeekCounter = 0; $dayOfWeekCounter < 7; $dayOfWeekCounter++) {
                                echo "<td class='calendar-day'>";
                                
                                // Check if we should show a date
                                if (($weekCounter == 0 && $dayOfWeekCounter >= $dayOfWeek) || 
                                    ($weekCounter > 0 && $currentDate <= $daysInMonth)) {
                                    
                                    $dateString = sprintf('%04d-%02d-%02d', $year, $month, $currentDate);
                                    $isToday = ($dateString === $today);
                                    $hasAppointments = isset($ticketsByDate[$dateString]);
                                    
                                    echo "<div class='day-number " . ($isToday ? 'today' : '') . "'>";
                                    echo $currentDate;
                                    echo "</div>";
                                    
                                    // Show appointments for this day
                                    if ($hasAppointments) {
                                        echo "<div class='appointments'>";
                                        foreach ($ticketsByDate[$dateString] as $ticket) {
                                            $time = date('H:i', strtotime($ticket['scheduled_time']));
                                            $statusClass = match($ticket['status']) {
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'not_completed' => 'danger',
                                                default => 'secondary'
                                            };
                                            
                                            echo "<div class='appointment badge bg-{$statusClass} mb-1 text-start' 
                                                      data-bs-toggle='modal' 
                                                      data-bs-target='#appointmentModal' 
                                                      data-ticket-id='{$ticket['id']}'
                                                      style='cursor: pointer; width: 100%; font-size: 0.7em;'>";
                                            echo "<div><strong>{$time}</strong></div>";
                                            echo "<div>{$ticket['client_name']}</div>";
                                            echo "<div>Téc: {$ticket['technician_name']}</div>";
                                            if (!empty($ticket['security_code'])) {
                                                echo "<div>🔒 {$ticket['security_code']}</div>";
                                            }
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                    }
                                    
                                    $currentDate++;
                                }
                                
                                echo "</td>";
                            }
                            
                            echo "</tr>";
                            $weekCounter++;
                            
                            // Prevent infinite loop
                            if ($currentDate > $daysInMonth) break;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Statistics Summary -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5>Pendientes</h5>
                    <h3><?php echo count(array_filter($scheduledTickets, fn($t) => $t['status'] === 'pending')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5>En Progreso</h5>
                    <h3><?php echo count(array_filter($scheduledTickets, fn($t) => $t['status'] === 'in_progress')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5>Completadas</h5>
                    <h3><?php echo count(array_filter($scheduledTickets, fn($t) => $t['status'] === 'completed')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5>Total Citas</h5>
                    <h3><?php echo count($scheduledTickets); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Appointment Details -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="appointmentModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="editTicketBtn" class="btn btn-primary">Editar Ticket</a>
                <a href="#" id="viewTicketBtn" class="btn btn-info">Ver Detalles</a>
            </div>
        </div>
    </div>
</div>

<style>
.btn-month-nav {
    background-color: #2D3142 !important;
    border-color: #2D3142 !important;
    color: white !important;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-month-nav:hover {
    background-color: #1a1d2a !important;
    border-color: #1a1d2a !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(45, 49, 66, 0.3);
}

.btn-month-nav:focus {
    background-color: #2D3142 !important;
    border-color: #2D3142 !important;
    color: white !important;
    box-shadow: 0 0 0 0.2rem rgba(45, 49, 66, 0.25);
}

.calendar-table {
    table-layout: fixed;
    background-color: #B9C3C6;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    padding: 5px;
    position: relative;
    width: 14.28%;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.day-number.today {
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 5px auto;
}

.appointments {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.appointment {
    font-size: 0.7em;
    line-height: 1.2;
    padding: 3px;
    border-radius: 3px;
    transition: transform 0.2s;
    background-color: #5B6386 !important;
    color: white !important;
    border: none !important;
    font-weight: 300;
}

/* Sobrescribir las clases de Bootstrap para los badges */
.appointment.bg-warning,
.appointment.bg-info,
.appointment.bg-success,
.appointment.bg-danger,
.appointment.bg-secondary {
    background-color: #5B6386 !important;
    color: white !important;
}

.appointment:hover {
    transform: scale(1.05);
}

/* Hacer que solo la hora sea bold, el resto del texto más ligero */
.appointment div:first-child {
    font-weight: 500;
}

.appointment div:not(:first-child) {
    font-weight: 300;
}

@media (max-width: 768px) {
    .calendar-day {
        height: 80px;
        padding: 2px;
    }
    
    .appointment {
        font-size: 0.6em;
        padding: 2px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle appointment modal
    const appointmentModal = document.getElementById('appointmentModal');
    const modalBody = document.getElementById('appointmentModalBody');
    const editBtn = document.getElementById('editTicketBtn');
    const viewBtn = document.getElementById('viewTicketBtn');
    
    if (appointmentModal) {
        appointmentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const ticketId = button.getAttribute('data-ticket-id');
            
            // Reset modal content
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            
            // Update button links
            editBtn.href = `<?php echo BASE_URL; ?>admin/tickets.php?action=edit&id=${ticketId}`;
            viewBtn.href = `<?php echo BASE_URL; ?>admin/tickets.php?action=view&id=${ticketId}`;
            
            // Load ticket details via AJAX
            fetch(`<?php echo BASE_URL; ?>api/get_ticket.php?id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const ticket = data.ticket;
                        const statusBadges = {
                            'pending': 'warning',
                            'in_progress': 'info',
                            'completed': 'success',
                            'not_completed': 'danger'
                        };
                        
                        const statusTexts = {
                            'pending': 'Pendiente',
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'not_completed': 'No Completado'
                        };
                        
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-person"></i> Cliente</h6>
                                    <p><strong>${ticket.client_name}</strong><br>
                                       ${ticket.business_name}<br>
                                       ${ticket.client_address}</p>
                                    
                                    <h6><i class="bi bi-tools"></i> Técnico</h6>
                                    <p><strong>${ticket.technician_name}</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-calendar"></i> Fecha y Hora</h6>
                                    <p><strong>${formatDate(ticket.scheduled_date)} a las ${formatTime(ticket.scheduled_time)}</strong></p>
                                    
                                    <h6><i class="bi bi-lock"></i> Código de Seguridad</h6>
                                    <p><span class="badge bg-primary fs-6">${ticket.security_code}</span></p>
                                    
                                    <h6><i class="bi bi-flag"></i> Estado</h6>
                                    <p><span class="badge bg-${statusBadges[ticket.status]}">${statusTexts[ticket.status]}</span></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <h6><i class="bi bi-clipboard"></i> Descripción</h6>
                                    <p>${ticket.description}</p>
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger">Error cargando los detalles del ticket.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>';
                });
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('es-ES', options);
    }
    
    function formatTime(timeString) {
        return timeString + ' hs';
    }
});
</script>

<?php include_once '../templates/footer.php'; ?>
