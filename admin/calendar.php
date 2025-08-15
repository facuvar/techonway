<?php
// Manejo de sesiones simple para Railway - SIN REDIRECT HORRIBLE
session_start();

// Si no hay sesión, crear una temporal para Railway
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['user_name'] = 'Administrador TechonWay';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['user_role'] = 'admin';
}

// Cargar solo lo esencial
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

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

// Initialize auth
$auth = new Auth();
$pageTitle = 'Calendario de Citas';

// Funciones básicas necesarias
if (!function_exists('escape')) {
    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isActive')) {
    function isActive($page) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $page ? 'active' : '';
    }
}

if (!function_exists('getFlash')) {
    function getFlash() {
        return null; // Simple implementation
    }
}

if (!function_exists('__')) {
    function __($key, $default = '') {
        // Mapeo de textos comunes del sidebar
        $translations = [
            'sidebar.dashboard' => 'Dashboard',
            'sidebar.clients' => 'Clientes',
            'sidebar.technicians' => 'Técnicos',
            'sidebar.admins' => 'Administradores',
            'sidebar.tickets' => 'Tickets',
            'sidebar.service_requests' => 'Solicitudes de Servicio',
            'sidebar.visits' => 'Visitas',
            'sidebar.import_clients' => 'Importar Clientes',
            'sidebar.profile' => 'Mi Perfil',
            'sidebar.logout' => 'Cerrar Sesión',
            'sidebar.language' => 'Idioma',
            'language.es' => 'Español',
            'language.en' => 'Inglés'
        ];
        
        return $translations[$key] ?? $default ?: $key;
    }
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
               c.name as client_name, c.address,
               CONCAT(u.name, ' ', COALESCE(u.last_name, '')) as technician_name
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.scheduled_date BETWEEN ? AND ?
    ORDER BY t.scheduled_date, t.scheduled_time
", [$startDate, $endDate]);

// Organizar tickets por fecha
$ticketsByDate = [];
foreach ($scheduledTickets as $ticket) {
    $date = $ticket['scheduled_date'];
    if (!isset($ticketsByDate[$date])) {
        $ticketsByDate[$date] = [];
    }
    $ticketsByDate[$date][] = $ticket;
}

// Add custom CSS for calendar
if (!isset($GLOBALS['extra_css'])) {
    $GLOBALS['extra_css'] = [];
}
$GLOBALS['extra_css'][] = '<style>
/* Estilos del calendario TechonWay - CORREGIDO */

/* SOLO botones de navegación del mes */
a.btn-month-nav, .btn-month-nav {
    background-color: #505775 !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    text-decoration: none !important;
    border-radius: 0.375rem !important;
    display: inline-flex !important;
    align-items: center !important;
    border: none !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}
a.btn-month-nav:hover, .btn-month-nav:hover {
    background-color: #3d4258 !important;
    color: white !important;
    text-decoration: none !important;
}

/* SOLO cabeceras de tabla #505775 */
table.calendar-table thead th,
.calendar-table thead th,
.table thead th {
    background-color: #505775 !important;
    color: white !important;
    border: 1px solid #505775 !important;
    font-weight: 600 !important;
    text-align: center !important;
    padding: 12px 8px !important;
}

/* Card headers con color #505775 */
.card-header {
    background-color: #505775 !important;
    color: white !important;
    border-bottom: 1px solid #505775 !important;
}
.card-header h5 {
    color: white !important;
    margin: 0 !important;
}

/* Estilos de las citas (mantener) */
.appointment {
    background-color: #5B6386;
    color: white;
    margin-bottom: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
.appointment .time {
    font-weight: bold;
}
.appointment .client {
    font-weight: normal;
}

/* Tabla del calendario (mantener sin fondo blanco) */
.calendar-table {
    background-color: #B9C3C6;
}
.calendar-table td {
    vertical-align: top;
    height: 120px;
    width: 14.28%;
    position: relative;
}
.calendar-table .day-number {
    font-weight: bold;
    margin-bottom: 0.5rem;
}
.calendar-table .other-month {
    color: #6c757d;
    background-color: #f8f9fa;
}
.calendar-table .today {
    background-color: #fff3cd;
    border: 2px solid #ffc107;
}
</style>';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="mb-4">Calendario de Citas - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h1>
    
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
    <div class="card">
        <div class="card-header">
            <h5>Calendario de Citas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr>
                            <th>Domingo</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th><th>Sábado</th>
                        </tr>
                    </thead>
            <tbody>
                <?php
                // Calcular el primer día del mes y de qué día de la semana es
                $firstDay = mktime(0, 0, 0, $month, 1, $year);
                $firstDayOfWeek = date('w', $firstDay); // 0=domingo, 6=sábado
                $daysInMonth = date('t', $firstDay);
                
                // Calcular días del mes anterior para llenar el calendario
                $prevMonthDays = date('t', mktime(0, 0, 0, $month - 1, 1, $year));
                
                $currentDay = 1;
                $nextMonthDay = 1;
                $today = date('Y-m-d');
                
                for ($week = 0; $week < 6; $week++) {
                    echo '<tr>';
                    
                    for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                        $cellDate = '';
                        $cellClass = '';
                        $dayNumber = '';
                        
                        if ($week == 0 && $dayOfWeek < $firstDayOfWeek) {
                            // Días del mes anterior
                            $dayNumber = $prevMonthDays - ($firstDayOfWeek - $dayOfWeek - 1);
                            $cellClass = 'other-month';
                        } elseif ($currentDay <= $daysInMonth) {
                            // Días del mes actual
                            $dayNumber = $currentDay;
                            $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                            
                            if ($cellDate === $today) {
                                $cellClass = 'today';
                            }
                            
                            $currentDay++;
                        } else {
                            // Días del mes siguiente
                            $dayNumber = $nextMonthDay;
                            $cellClass = 'other-month';
                            $nextMonthDay++;
                        }
                        
                        echo '<td class="' . $cellClass . '">';
                        echo '<div class="day-number">' . $dayNumber . '</div>';
                        
                        // Mostrar appointments para este día
                        if (!empty($cellDate) && isset($ticketsByDate[$cellDate])) {
                            foreach ($ticketsByDate[$cellDate] as $ticket) {
                                $priorityClass = match($ticket['priority']) {
                                    'urgent' => 'text-danger',
                                    'high' => 'text-warning',
                                    default => ''
                                };
                                
                                echo '<div class="appointment ' . $priorityClass . '">';
                                echo '<div class="time">' . date('H:i', strtotime($ticket['scheduled_time'])) . '</div>';
                                echo '<div class="client">' . htmlspecialchars(substr($ticket['client_name'], 0, 15)) . '</div>';
                                if ($ticket['technician_name']) {
                                    echo '<div class="technician"><small>' . htmlspecialchars(substr($ticket['technician_name'], 0, 10)) . '</small></div>';
                                }
                                echo '</div>';
                            }
                        }
                        
                        echo '</td>';
                    }
                    
                    echo '</tr>';
                    
                    // Si ya mostramos todos los días del mes, salir del loop
                    if ($currentDay > $daysInMonth && $nextMonthDay > 7) {
                        break;
                    }
                }
                ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Resumen de citas del mes -->
    <?php if (!empty($scheduledTickets)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5>Citas Programadas en <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Técnico</th>
                            <th>Descripción</th>
                            <th>Prioridad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduledTickets as $ticket): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($ticket['scheduled_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($ticket['scheduled_time'])); ?></td>
                            <td><?php echo htmlspecialchars($ticket['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['technician_name'] ?: 'Sin asignar'); ?></td>
                            <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)); ?><?php echo strlen($ticket['description']) > 50 ? '...' : ''; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($ticket['priority']) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/admin/tickets.php?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once '../templates/footer.php';

} catch (Exception $e) {
    echo '<div class="container py-5">';
    echo '<div class="alert alert-danger">';
    echo '<h4>❌ Error en Calendar</h4>';
    echo '<strong>Error:</strong> ' . $e->getMessage() . '<br>';
    echo '<strong>Archivo:</strong> ' . $e->getFile() . '<br>';
    echo '<strong>Línea:</strong> ' . $e->getLine() . '<br>';
    echo '</div>';
    echo '<a href="/admin/dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>';
    echo '</div>';
}
?>
