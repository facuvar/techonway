<?php
/**
 * Calendar que SÍ funciona - usando el código probado + diseño manual
 */
session_start();

// Si no hay sesión, usar el login que ya probamos que funciona
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirigir al login que funciona
    header('Location: /admin/force_login_and_calendar.php');
    exit();
}

// Si llegamos aquí, hay sesión válida
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
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Calendario Admin - TechonWay</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
        <style>
        /* Diseño del panel admin simulado */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: #2D3142;
            color: white;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        .sidebar .nav-link {
            color: #bbb;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
        }
        .sidebar .nav-link:hover {
            background: #3d4252;
            color: white;
        }
        .sidebar .nav-link.active {
            background: #5B6386;
            color: white;
        }
        .navbar-custom {
            background: #2D3142;
            border-bottom: 1px solid #4a4a4a;
        }
        /* Estilos del calendar */
        .calendar-table { background-color: #B9C3C6; }
        .btn-month-nav { background-color: #2D3142; color: white; border: none; }
        .btn-month-nav:hover { background-color: #3d4252; color: white; }
        .appointment { 
            background-color: #5B6386; 
            color: white; 
            margin-bottom: 5px; 
            padding: 5px; 
            border-radius: 3px; 
            font-size: 0.85rem;
        }
        .appointment .time { font-weight: bold; }
        </style>
    </head>
    <body>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-3">
                <h4>TechonWay</h4>
                <small class="text-muted">Panel Admin</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="/admin/clients.php">
                    <i class="bi bi-building"></i> Clientes
                </a>
                <a class="nav-link" href="/admin/technicians.php">
                    <i class="bi bi-person-gear"></i> Técnicos
                </a>
                <a class="nav-link" href="/admin/tickets.php">
                    <i class="bi bi-ticket-perforated"></i> Tickets
                </a>
                <a class="nav-link active" href="/admin/calendar.php">
                    <i class="bi bi-calendar3"></i> Calendario
                </a>
                <a class="nav-link" href="/admin/visits.php">
                    <i class="bi bi-house-door"></i> Visitas
                </a>
                <a class="nav-link" href="/admin/logs.php">
                    <i class="bi bi-journal-text"></i> Logs
                </a>
                <hr class="text-muted">
                <a class="nav-link" href="/index.php">
                    <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
                <div class="container-fluid">
                    <span class="navbar-brand">📅 Calendario de Citas</span>
                    <span class="navbar-text">
                        👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?>
                    </span>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-12">
                        <h1 class="mb-4">📅 Calendario de Citas - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h1>
                        
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
                                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-month-nav me-2">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                                <span class="fw-bold fs-5"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-month-nav ms-2">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                            
                            <div>
                                <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">
                                    <i class="bi bi-house"></i> Mes Actual
                                </a>
                            </div>
                        </div>
                        
                        <!-- Calendar -->
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table">
                                <thead class="table-dark">
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
                                            echo '<td style="height: 120px; vertical-align: top; padding: 8px;">';
                                            
                                            if (($week == 0 && $dayOfWeek >= $startDay) || ($week > 0 && $day <= $daysInMonth)) {
                                                echo '<div class="fw-bold mb-1">' . $day . '</div>';
                                                
                                                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                if (isset($ticketsByDate[$currentDate])) {
                                                    foreach ($ticketsByDate[$currentDate] as $ticket) {
                                                        echo '<div class="appointment">';
                                                        echo '<div class="time">' . date('H:i', strtotime($ticket['scheduled_time'])) . '</div>';
                                                        echo '<div>' . htmlspecialchars(substr($ticket['client_name'], 0, 18)) . '</div>';
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
                        
                        <!-- Resumen -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">📊 Resumen del Mes</h5>
                                        <p class="card-text">
                                            <strong>Total de citas:</strong> <?php echo count($scheduledTickets); ?><br>
                                            <strong>Mes:</strong> <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">🔧 Acciones</h5>
                                        <a href="/admin/tickets.php?action=create" class="btn btn-success btn-sm">
                                            <i class="bi bi-plus"></i> Crear Ticket
                                        </a>
                                        <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-arrow-left"></i> Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error - TechonWay</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-5">
            <div class="alert alert-danger">
                <h4>❌ Error en Calendar</h4>
                <strong>Error:</strong> <?php echo $e->getMessage(); ?><br>
                <strong>Archivo:</strong> <?php echo $e->getFile(); ?><br>
                <strong>Línea:</strong> <?php echo $e->getLine(); ?><br>
            </div>
            <a href="/admin/dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
        </div>
    </body>
    </html>
    <?php
}
?>
