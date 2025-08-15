<?php
/**
 * Calendar COPIANDO EXACTAMENTE la lógica de localhost
 */

// EXACTAMENTE como funciona en local - paso por paso
session_start();

// Si no hay sesión, redirigir al login (EXACTAMENTE como local)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirigir al login
    header('Location: /index.php');
    exit();
}

// Debug después del check de sesión
$debug_session_id = session_id();
$debug_user_id = $_SESSION['user_id'] ?? 'NO DEFINIDO';
$debug_role = $_SESSION['role'] ?? 'NO DEFINIDO';

// Cargar includes DESPUÉS de verificar sesión
require_once '../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Get month and year from query parameters
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Validate month and year
    $month = max(1, min(12, intval($month)));
    $year = max(2020, min(2030, intval($year)));
    
    // Query SIMPLIFICADA para evitar problemas de JOIN
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $scheduledTickets = $db->select("
        SELECT t.id, t.scheduled_date, t.scheduled_time, t.description, t.priority,
               c.name as client_name, c.address
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
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
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Calendario - TechonWay</title>
        <!-- DEBUG SESSION: ID=<?php echo $debug_session_id; ?>, User=<?php echo $debug_user_id; ?>, Role=<?php echo $debug_role; ?> -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
        .calendar-table { background-color: #B9C3C6; }
        .btn-month-nav { background-color: #2D3142; color: white; }
        .appointment { background-color: #5B6386; color: white; margin-bottom: 5px; padding: 5px; border-radius: 3px; }
        .appointment .time { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container-fluid py-4">
            <h1>📅 Calendario de Citas - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h1>
            
            <!-- Navigation -->
            <div class="mb-3">
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
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-month-nav">← Anterior</a>
                <span class="mx-3"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-month-nav">Siguiente →</a>
            </div>
            
            <!-- Calendar -->
            <div class="table-responsive">
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr>
                            <th>Domingo</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                            <th>Sábado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $firstDay = mktime(0, 0, 0, $month, 1, $year);
                        $startDay = date('w', $firstDay); // 0 = Sunday
                        $daysInMonth = date('t', $firstDay);
                        
                        $day = 1;
                        for ($week = 0; $week < 6; $week++) {
                            if ($day > $daysInMonth) break;
                            echo '<tr>';
                            
                            for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                                echo '<td style="height: 120px; vertical-align: top;">';
                                
                                if (($week == 0 && $dayOfWeek >= $startDay) || ($week > 0 && $day <= $daysInMonth)) {
                                    echo '<strong>' . $day . '</strong><br>';
                                    
                                    // Show appointments for this day
                                    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    if (isset($ticketsByDate[$currentDate])) {
                                        foreach ($ticketsByDate[$currentDate] as $ticket) {
                                            echo '<div class="appointment">';
                                            echo '<div class="time">' . date('H:i', strtotime($ticket['scheduled_time'])) . '</div>';
                                            echo '<div>' . htmlspecialchars(substr($ticket['client_name'], 0, 20)) . '</div>';
                                            echo '</div>';
                                        }
                                    }
                                    
                                    $day++;
                                }
                                
                                echo '</td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <h3>📊 Resumen del Mes</h3>
                <p><strong>Total de citas:</strong> <?php echo count($scheduledTickets); ?></p>
                <a href="/admin/dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    
    <?php
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
