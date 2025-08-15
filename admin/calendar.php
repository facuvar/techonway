<?php
/**
 * Calendar HÍBRIDO - Funciona tanto desde dashboard como directo
 */
session_start();

// Si no hay sesión, pero se puede autenticar desde el dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Intentar obtener sesión desde cookies o re-autenticar
    
    // Verificar si hay alguna forma de recuperar la sesión
    if (isset($_COOKIE['PHPSESSID'])) {
        // Intentar recuperar sesión existente
        session_regenerate_id();
    }
    
    // Si aún no hay sesión, redirigir a login (pero no al dashboard)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        // En lugar de redirigir, mostrar link para loguearse
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Calendar - Sesión Requerida</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3>📅 Calendar - Sesión Requerida</h3>
                                <p>Tu sesión de admin se perdió. Necesitas loguearte otra vez.</p>
                                
                                <div class="alert alert-info">
                                    <strong>Problema conocido:</strong> Las sesiones no se mantienen correctamente en Railway entre páginas del admin.
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="/admin/force_login_and_calendar.php" class="btn btn-primary">
                                        🔑 Ir a Calendar con Login
                                    </a>
                                    <a href="/index.php" class="btn btn-secondary">
                                        🏠 Volver al Login Principal
                                    </a>
                                    <a href="/admin/dashboard.php" class="btn btn-outline-secondary">
                                        📊 Intentar Dashboard
                                    </a>
                                </div>
                                
                                <hr>
                                <small class="text-muted">
                                    Session ID: <?php echo session_id(); ?><br>
                                    Timestamp: <?php echo date('Y-m-d H:i:s'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Si llegamos aquí, hay sesión válida - proceder con el calendar
require_once '../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Get month and year
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    $month = max(1, min(12, intval($month)));
    $year = max(2020, min(2030, intval($year)));
    
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
    
    $ticketsByDate = [];
    foreach ($scheduledTickets as $ticket) {
        $date = $ticket['scheduled_date'];
        if (!isset($ticketsByDate[$date])) {
            $ticketsByDate[$date] = [];
        }
        $ticketsByDate[$date][] = $ticket;
    }
    
    
    // Page title para el header
    $pageTitle = 'Calendario de Citas';
    
    // Include header (ya incluye sidebar automáticamente)
    include '../templates/header.php';
    ?>
    
    <style>
    .calendar-table { background-color: #B9C3C6; }
    .btn-month-nav { background-color: #2D3142; color: white; }
    .appointment { background-color: #5B6386; color: white; margin-bottom: 5px; padding: 5px; border-radius: 3px; }
    .appointment .time { font-weight: bold; }
    </style>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <h1>📅 Calendario de Citas - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h1>
                    
                    <!-- Navigation -->
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
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
                            <span class="mx-3 fw-bold"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-month-nav">Siguiente →</a>
                        </div>
                        
                        <div>
                            <a href="/admin/dashboard.php" class="btn btn-secondary">← Dashboard</a>
                            <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">Mes Actual</a>
                        </div>
                    </div>
                    
                    <!-- Calendar -->
                    <div class="table-responsive">
                        <table class="table table-bordered calendar-table">
                            <thead>
                                <tr>
                                    <th>Domingo</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th><th>Sábado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $firstDay = mktime(0, 0, 0, $month, 1, $year);
                                $startDay = date('w', $firstDay);
                                $daysInMonth = date('t', $firstDay);
                                
                                $day = 1;
                                for ($week = 0; $week < 6; $week++) {
                                    if ($day > $daysInMonth) break;
                                    echo '<tr>';
                                    
                                    for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                                        echo '<td style="height: 120px; vertical-align: top;">';
                                        
                                        if (($week == 0 && $dayOfWeek >= $startDay) || ($week > 0 && $day <= $daysInMonth)) {
                                            echo '<strong>' . $day . '</strong><br>';
                                            
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
                        <div class="row">
                            <div class="col-md-6">
                                <h3>📊 Resumen del Mes</h3>
                                <p><strong>Total de citas:</strong> <?php echo count($scheduledTickets); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    Session: <?php echo $_SESSION['user_name']; ?> | 
                                    ID: <?php echo session_id(); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../templates/footer.php'; ?>
    
    <?php
    
} catch (Exception $e) {
    // Page title para el header
    $pageTitle = 'Error en Calendario';
    include '../templates/header.php';
    ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="alert alert-danger">
                <h4>❌ Error en Calendar</h4>
                <strong>Error:</strong> <?php echo $e->getMessage(); ?><br>
                <strong>Archivo:</strong> <?php echo $e->getFile(); ?><br>
                <strong>Línea:</strong> <?php echo $e->getLine(); ?><br>
            </div>
        </div>
    </div>
    
    <?php include '../templates/footer.php';
}
?>
