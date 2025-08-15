<?php
/**
 * Vista de calendario TEMPORAL (sin auth por ahora)
 * Para debugging en Railway
 */
require_once '../includes/init.php';

// TEMPORAL: Forzar datos de admin para testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Admin Temp';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['last_regeneration'] = time();
    
    echo "<div style='background: orange; padding: 10px; margin: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>MODO DEBUG:</strong> Sesión admin temporal activada para testing";
    echo "</div>";
}

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

// Create calendar
$firstDay = date('w', strtotime($startDate));
$daysInMonth = date('t', strtotime($startDate));

$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Page title
$pageTitle = 'Calendario de Citas - ' . $monthNames[$month] . ' ' . $year;

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-calendar-event"></i> Calendario de Citas Programadas (TEMP)
        </h1>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Cita
            </a>
        </div>
    </div>
    
    <!-- Month Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        ?>
        
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-month-nav">
            <i class="bi bi-chevron-left"></i> <?php echo $monthNames[$prevMonth]; ?>
        </a>
        
        <h2 class="mb-0"><?php echo $monthNames[$month] . ' ' . $year; ?></h2>
        
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-month-nav">
            <?php echo $monthNames[$nextMonth]; ?> <i class="bi bi-chevron-right"></i>
        </a>
    </div>
    
    <!-- Calendar -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table" style="background-color: #B9C3C6;">
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
                        $dayCount = 1;
                        $weekCount = 0;
                        
                        // Calculate total weeks needed
                        $totalCells = $firstDay + $daysInMonth;
                        $weeksNeeded = ceil($totalCells / 7);
                        
                        for ($weekCounter = 0; $weekCounter < $weeksNeeded; $weekCounter++) {
                            echo "<tr>";
                            
                            // Generate 7 days for the week
                            for ($dayOfWeekCounter = 0; $dayOfWeekCounter < 7; $dayOfWeekCounter++) {
                                echo "<td class='calendar-day'>";
                                
                                // Check if we should show a date
                                if (($weekCounter == 0 && $dayOfWeekCounter >= $firstDay) || 
                                    ($weekCounter > 0 && $dayCount <= $daysInMonth)) {
                                    
                                    if ($dayCount <= $daysInMonth) {
                                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $dayCount);
                                        $isToday = $currentDate === date('Y-m-d');
                                        
                                        echo "<div class='day-number" . ($isToday ? ' today' : '') . "'>";
                                        echo $dayCount;
                                        echo "</div>";
                                        
                                        // Show appointments for this day
                                        if (isset($ticketsByDate[$currentDate])) {
                                            foreach ($ticketsByDate[$currentDate] as $ticket) {
                                                $time = date('H:i', strtotime($ticket['scheduled_time']));
                                                $statusClass = match($ticket['status']) {
                                                    'pending' => 'bg-warning',
                                                    'in_progress' => 'bg-info', 
                                                    'completed' => 'bg-success',
                                                    'not_completed' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                
                                                echo "<div class='appointment mb-1 text-start' style='background-color: #5B6386 !important; color: white !important; font-weight: 300;'>";
                                                echo "<div style='font-weight: 500;'>$time</div>";
                                                echo "<div>" . escape(substr($ticket['client_name'], 0, 15)) . "</div>";
                                                echo "<div>" . escape($ticket['technician_name']) . "</div>";
                                                echo "</div>";
                                            }
                                        }
                                        
                                        $dayCount++;
                                    }
                                }
                                
                                echo "</td>";
                            }
                            
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Estadísticas del Mes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo count($scheduledTickets); ?></h3>
                                <p class="mb-0">Total Citas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php 
                                $pending = array_filter($scheduledTickets, fn($t) => $t['status'] === 'pending');
                                ?>
                                <h3 class="text-warning"><?php echo count($pending); ?></h3>
                                <p class="mb-0">Pendientes</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php 
                                $completed = array_filter($scheduledTickets, fn($t) => $t['status'] === 'completed');
                                ?>
                                <h3 class="text-success"><?php echo count($completed); ?></h3>
                                <p class="mb-0">Completadas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php 
                                $inProgress = array_filter($scheduledTickets, fn($t) => $t['status'] === 'in_progress');
                                ?>
                                <h3 class="text-info"><?php echo count($inProgress); ?></h3>
                                <p class="mb-0">En Progreso</p>
                            </div>
                        </div>
                    </div>
                </div>
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
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

.appointment.bg-warning,
.appointment.bg-info,
.appointment.bg-success,
.appointment.bg-danger,
.appointment.bg-secondary {
    background-color: #5B6386 !important;
    color: white !important;
}

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

<?php include_once '../templates/footer.php'; ?>
