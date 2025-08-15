<?php
// Manejo de sesiones simplificado para Railway
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/force_login_and_calendar.php');
    exit();
}

// Cargar solo lo esencial
require_once '../includes/Database.php';

// Definir constantes esenciales que normalmente están en init.php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('TEMPLATE_PATH')) {
    define('TEMPLATE_PATH', BASE_PATH . '/templates');
}

// Simular funciones esenciales
function __($key, $default = null) {
    return $default ?: $key;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isActive($page) {
    $current = basename($_SERVER['REQUEST_URI']);
    return (strpos($current, $page) !== false) ? 'active' : '';
}

function getFlash() {
    return null; // Sin flash messages por simplicidad
}

// Simular objeto auth
$auth = new stdClass();
$auth->isLoggedIn = function() { return isset($_SESSION['user_id']); };
$auth->isAdmin = function() { return $_SESSION['role'] === 'admin'; };

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
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema de Gestión de Tickets para Ascensores</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
        <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">
        
        <style>
        /* Estilos específicos del calendar */
        .calendar-table { background-color: #B9C3C6; }
        .btn-month-nav { 
            background-color: #2D3142; 
            color: white; 
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-month-nav:hover { 
            background-color: #3d4252; 
            color: white; 
        }
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
    <body class="dark-mode has-sidebar">
        <!-- Top Navbar (mobile) -->
        <nav class="navbar top-navbar d-flex align-items-center px-3 d-md-none">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list" style="font-size:1.25rem;"></i>
                </button>
                <a class="navbar-brand mb-0 h1 d-flex align-items-center" href="<?php echo BASE_URL; ?>dashboard.php">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" style="height:28px;width:auto;"/>
                </a>
            </div>
        </nav>
        
        <!-- Offcanvas Sidebar for mobile -->
        <div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Menú</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="sidebar-content">
                    <!-- Mobile sidebar content -->
                    <div class="text-center p-3">
                        <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TechonWay" style="height: 40px;">
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/admin/clients.php">
                            <i class="bi bi-people me-2"></i>Clientes
                        </a>
                        <a class="nav-link" href="/admin/tickets.php">
                            <i class="bi bi-ticket-perforated me-2"></i>Tickets
                        </a>
                        <a class="nav-link active" href="/admin/calendar.php">
                            <i class="bi bi-calendar3 me-2"></i>Calendario
                        </a>
                        <a class="nav-link" href="/admin/visits.php">
                            <i class="bi bi-geo-alt me-2"></i>Visitas
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="bi bi-person-gear me-2"></i>Usuarios
                        </a>
                        <a class="nav-link" href="/admin/settings.php">
                            <i class="bi bi-gear me-2"></i>Configuración
                        </a>
                        <hr>
                        <a class="nav-link" href="/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar d-none d-md-block">
                    <div class="sidebar-brand text-center p-3">
                        <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TechonWay" style="height: 50px;">
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/admin/clients.php">
                            <i class="bi bi-people me-2"></i>Clientes
                        </a>
                        <a class="nav-link" href="/admin/tickets.php">
                            <i class="bi bi-ticket-perforated me-2"></i>Tickets
                        </a>
                        <a class="nav-link active" href="/admin/calendar.php">
                            <i class="bi bi-calendar3 me-2"></i>Calendario
                        </a>
                        <a class="nav-link" href="/admin/visits.php">
                            <i class="bi bi-geo-alt me-2"></i>Visitas
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="bi bi-person-gear me-2"></i>Usuarios
                        </a>
                        <a class="nav-link" href="/admin/settings.php">
                            <i class="bi bi-gear me-2"></i>Configuración
                        </a>
                        <hr>
                        <a class="nav-link" href="/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
                <!-- Main content -->
                <div class="col-12 col-md-9 col-lg-10 ms-auto main-content">
                    
                    <!-- Calendar Content -->
                    <div class="container-fluid py-4">
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
                                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn-month-nav me-2">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                                <span class="fw-bold fs-5 mx-3"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn-month-nav ms-2">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                            
                            <div>
                                <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">
                                    <i class="bi bi-house"></i> Mes Actual
                                </a>
                                <a href="/admin/tickets.php?action=create" class="btn btn-success">
                                    <i class="bi bi-plus"></i> Crear Ticket
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
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">📊 Resumen del Mes</h5>
                                        <p class="card-text">
                                            <strong>Total de citas:</strong> <?php echo count($scheduledTickets); ?><br>
                                            <strong>Mes:</strong> <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?><br>
                                            <small class="text-muted">Usuario: <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">🔧 Acciones Rápidas</h5>
                                        <div class="d-grid gap-2">
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
        </div>
        
        <!-- Bootstrap JS -->
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
                <strong>Archivo:</strong> <?php echo $e->getFile(); ?><br>
                <strong>Línea:</strong> <?php echo $e->getLine(); ?><br>
            </div>
            <a href="/admin/dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
            <a href="/admin/force_login_and_calendar.php" class="btn btn-primary">🔑 Login y Calendar</a>
        </div>
    </body>
    </html>
    <?php
}
?>
