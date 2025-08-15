<?php
/**
 * LOGIN FORZADO + CALENDAR - para bypasear problema de sesiones en Railway
 */
session_start();

echo "<h1>🔑 Login Forzado + Calendar</h1>";

// Si no hay datos de POST, mostrar formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <form method="POST" action="">
        <div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h2>Login Railway</h2>
            <p><strong>Usuarios admin disponibles:</strong></p>
            <ul>
                <li>admin@techonway.com / admin123</li>
                <li>facundo@techonway.com / (tu password)</li>
            </ul>
            
            <label>Email:</label><br>
            <input type="email" name="email" value="admin@techonway.com" style="width: 100%; padding: 8px; margin: 5px 0 15px 0;"><br>
            
            <label>Password:</label><br>
            <input type="password" name="password" value="admin123" style="width: 100%; padding: 8px; margin: 5px 0 15px 0;"><br>
            
            <button type="submit" style="width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px;">
                🔑 Entrar y ver Calendar
            </button>
        </div>
    </form>
    <?php
    exit();
}

// Procesar login
require_once '../includes/Database.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo "<div style='color: red;'>❌ Email y password son requeridos</div>";
    exit();
}

try {
    $db = Database::getInstance();
    
    // Buscar usuario
    $user = $db->selectOne("
        SELECT id, name, last_name, email, password, role 
        FROM users 
        WHERE email = ? AND role = 'admin'
    ", [$email]);
    
    if (!$user) {
        echo "<div style='color: red;'>❌ Usuario admin no encontrado</div>";
        echo "<p>Usuarios admin disponibles:</p>";
        $admins = $db->select("SELECT email FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            echo "- " . $admin['email'] . "<br>";
        }
        exit();
    }
    
    // Verificar password
    if (!password_verify($password, $user['password'])) {
        echo "<div style='color: red;'>❌ Password incorrecto</div>";
        exit();
    }
    
    // ¡LOGIN EXITOSO! Crear sesión manualmente
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    echo "<div style='color: green;'>✅ Login exitoso!</div>";
    echo "<p><strong>Usuario:</strong> " . $user['name'] . " (" . $user['email'] . ")</p>";
    echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    
    // Ahora mostrar el CALENDAR
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
        <title>Calendar Railway - TechonWay</title>
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
            <h1>📅 Calendar Railway - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h1>
            
            <div class="alert alert-success">
                ✅ <strong>Logueado como:</strong> <?php echo $user['name']; ?> (<?php echo $user['email']; ?>)
            </div>
            
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
                <h3>📊 Resumen</h3>
                <p><strong>Total de citas este mes:</strong> <?php echo count($scheduledTickets); ?></p>
                <a href="/admin/dashboard.php" class="btn btn-secondary">← Dashboard</a>
                <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">Mes Actual</a>
            </div>
        </div>
    </body>
    </html>
    
    <?php
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "</div>";
}
?>
