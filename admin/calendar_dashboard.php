<?php
/**
 * Calendar accesible DESDE el dashboard - Con sesión temporal
 */

// Permitir acceso con token temporal desde dashboard
$validAccess = false;

// Verificar si viene desde dashboard con token
if (isset($_GET['token']) && $_GET['token'] === 'dashboard_access') {
    $validAccess = true;
    
    // Crear sesión temporal de admin
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrador TechonWay';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['role'] = 'admin';
    $_SESSION['temp_login'] = true;
} else {
    // Verificar sesión normal
    session_start();
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
        $validAccess = true;
    }
}

// Si no hay acceso válido, redirigir
if (!$validAccess) {
    header('Location: /admin/force_login_and_calendar.php');
    exit();
}

// Cargar solo lo esencial
require_once '../includes/Database.php';

// Definir constantes esenciales
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="/assets/css/style.css">
        
        <style>
        /* Forzar estilos del panel admin */
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
            border-right: 3px solid #5B6386;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #444;
        }
        .sidebar-user {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #444;
        }
        .sidebar .nav-link {
            color: #bbb;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background: rgba(91, 99, 134, 0.3);
            color: white;
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: #5B6386;
            color: white;
            border-left: 4px solid #8B9DC3;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 0;
        }
        .content-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 2px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* Estilos del calendar */
        .calendar-table { 
            background-color: #B9C3C6;
            border: 2px solid #2D3142;
        }
        .calendar-table th {
            background-color: #2D3142;
            color: white;
            text-align: center;
            font-weight: bold;
            padding: 12px;
        }
        .calendar-table td {
            height: 120px;
            vertical-align: top;
            padding: 8px;
            border: 1px solid #2D3142;
        }
        .btn-month-nav { 
            background: linear-gradient(135deg, #2D3142 0%, #3A3F58 100%);
            color: white; 
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .btn-month-nav:hover { 
            background: linear-gradient(135deg, #3A3F58 0%, #4A4F68 100%);
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .appointment { 
            background: linear-gradient(135deg, #5B6386 0%, #6B7396 100%);
            color: white; 
            margin-bottom: 5px; 
            padding: 6px 8px; 
            border-radius: 4px; 
            font-size: 0.8rem;
            border-left: 3px solid #8B9DC3;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .appointment .time { 
            font-weight: bold; 
            font-size: 0.85rem;
        }
        .btn-calendar-action {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-calendar-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #2D3142 0%, #3A3F58 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        </style>
    </head>
    <body class="bg-light">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="/assets/img/logo.png" alt="TecLocator Logo" class="img-fluid" style="max-height: 50px;">
                <h5 class="mt-2 mb-0">TechonWay</h5>
                <small class="text-muted">Panel Admin</small>
            </div>
            <div class="sidebar-user">
                <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
                <div class="mt-2 fw-semibold"><?php echo $_SESSION['user_name']; ?></div>
                <small class="text-light">Administrador</small>
                <?php if (isset($_SESSION['temp_login'])): ?>
                <div class="mt-1">
                    <span class="badge bg-info">Sesión Temporal</span>
                </div>
                <?php endif; ?>
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
                    <a class="nav-link" href="/admin/admins.php">
                        <i class="bi bi-shield-lock"></i> Administradores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/tickets.php">
                        <i class="bi bi-ticket-perforated"></i> Tickets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/calendar_dashboard.php?token=dashboard_access">
                        <i class="bi bi-calendar-event"></i> Calendario de Citas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/service_requests.php">
                        <i class="bi bi-journal-text"></i> Solicitudes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/visits.php">
                        <i class="bi bi-clipboard-check"></i> Visitas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/force_login_and_calendar.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-0">📅 Calendario de Citas</h1>
                        <small class="text-muted"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></small>
                    </div>
                    <div>
                        <a href="/admin/tickets.php?action=create" class="btn btn-success btn-calendar-action">
                            <i class="bi bi-plus"></i> Crear Ticket
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="container-fluid py-4">
                <!-- Navigation -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
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
                        <a href="?token=dashboard_access&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn-month-nav me-3">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                        <span class="fw-bold fs-4 mx-3"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                        <a href="?token=dashboard_access&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn-month-nav ms-3">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div>
                        <a href="?token=dashboard_access&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">
                            <i class="bi bi-house"></i> Mes Actual
                        </a>
                    </div>
                </div>
                
                <!-- Calendar -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar3"></i> Calendario de Citas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table mb-0">
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
                                            echo '<td>';
                                            
                                            if (($week == 0 && $dayOfWeek >= $startDay) || ($week > 0 && $day <= $daysInMonth)) {
                                                echo '<div class="fw-bold mb-2 fs-5">' . $day . '</div>';
                                                
                                                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                if (isset($ticketsByDate[$currentDate])) {
                                                    foreach ($ticketsByDate[$currentDate] as $ticket) {
                                                        echo '<div class="appointment">';
                                                        echo '<div class="time">' . date('H:i', strtotime($ticket['scheduled_time'])) . '</div>';
                                                        echo '<div>' . htmlspecialchars(substr($ticket['client_name'], 0, 16)) . '</div>';
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
                    </div>
                </div>
                
                <!-- Resumen -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">📊 Resumen del Mes</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Total de citas:</strong> <?php echo count($scheduledTickets); ?></p>
                                <p class="mb-2"><strong>Mes:</strong> <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></p>
                                <small class="text-muted">Usuario: <?php echo $_SESSION['user_name']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">🔧 Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/admin/tickets.php?action=create" class="btn btn-success">
                                        <i class="bi bi-plus"></i> Crear Ticket con Cita
                                    </a>
                                    <a href="/admin/dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Volver al Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error - TechonWay</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-5">
            <div class="alert alert-danger">
                <h4>❌ Error en Calendar</h4>
                <strong>Error:</strong> <?php echo $e->getMessage(); ?><br>
            </div>
            <a href="/admin/force_login_and_calendar.php" class="btn btn-primary">🔑 Login y Calendar</a>
        </div>
    </body>
    </html>
    <?php
}
?>
