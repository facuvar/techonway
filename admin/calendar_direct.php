<?php
// Calendar directo sin autenticación - SOLO PARA TESTING EN RAILWAY
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Si llegamos aquí, la DB funciona
    echo "✅ Conexión DB exitosa<br>";
    
    // Test query básica
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE scheduled_date IS NOT NULL");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Tickets con citas: " . $result['total'] . "<br>";
    
    // Query para el calendario
    $month = date('n');
    $year = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.client_id,
            t.scheduled_date,
            t.scheduled_time,
            t.description,
            t.priority,
            c.name as client_name,
            c.address,
            CONCAT(u.name, ' ', u.last_name) as technician_name
        FROM tickets t
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.scheduled_date IS NOT NULL
        AND MONTH(t.scheduled_date) = :month
        AND YEAR(t.scheduled_date) = :year
        ORDER BY t.scheduled_date, t.scheduled_time
    ");
    
    $stmt->bindParam(':month', $month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $appointments = $stmt->fetchAll();
    
    echo "<h2>📅 Calendario " . date('F Y') . "</h2>";
    echo "<p>Citas encontradas: " . count($appointments) . "</p>";
    
    if (count($appointments) > 0) {
        echo "<div class='appointments'>";
        foreach ($appointments as $appointment) {
            echo "<div style='border: 1px solid #ddd; margin: 10px; padding: 10px; border-radius: 5px;'>";
            echo "<strong>📅 " . date('d/m/Y', strtotime($appointment['scheduled_date'])) . "</strong><br>";
            echo "<strong>🕐 " . date('H:i', strtotime($appointment['scheduled_time'])) . "</strong><br>";
            echo "<strong>👤 Cliente:</strong> " . $appointment['client_name'] . "<br>";
            echo "<strong>📍 Dirección:</strong> " . $appointment['address'] . "<br>";
            echo "<strong>🔧 Técnico:</strong> " . ($appointment['technician_name'] ?: 'Sin asignar') . "<br>";
            echo "<strong>📝 Descripción:</strong> " . $appointment['description'] . "<br>";
            echo "<strong>⚡ Prioridad:</strong> " . $appointment['priority'] . "<br>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<h2>🔗 Enlaces de prueba:</h2>";
    echo "<a href='/admin/calendar.php'>📅 Calendar Normal</a><br>";
    echo "<a href='/check_db_vars.php'>🔍 Check DB Vars</a><br>";
    echo "<a href='/admin/calendar_debug_railway.php'>🔧 Debug Railway</a><br>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
