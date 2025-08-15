<?php
/**
 * Debug para encontrar QUÉ está causando la redirección
 */

session_start();

echo "<h1>🔍 Debug Redirección Calendar</h1>";

echo "<h2>1. Estado de sesión:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NO DEFINIDO') . "<br>";
echo "Todas las variables de sesión:<br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>2. Verificación de autenticación:</h2>";
if (!isset($_SESSION['user_id'])) {
    echo "❌ NO hay user_id en sesión<br>";
} else {
    echo "✅ user_id existe: " . $_SESSION['user_id'] . "<br>";
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "❌ NO es admin. Role actual: " . ($_SESSION['role'] ?? 'NO DEFINIDO') . "<br>";
} else {
    echo "✅ Es admin<br>";
}

echo "<h2>3. Headers enviados:</h2>";
if (headers_sent($file, $line)) {
    echo "❌ Headers ya enviados en archivo: $file, línea: $line<br>";
} else {
    echo "✅ Headers no enviados aún<br>";
}

echo "<h2>4. Test de Database:</h2>";
try {
    require_once '../includes/Database.php';
    $db = Database::getInstance();
    echo "✅ Database conectada<br>";
    
    // Test query simple
    $result = $db->selectOne("SELECT COUNT(*) as total FROM tickets");
    echo "✅ Query test: " . $result['total'] . " tickets<br>";
    
} catch (Exception $e) {
    echo "❌ Error Database: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Variables de entorno:</h2>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "<br>";

echo "<h2>6. Test manual de calendario:</h2>";
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "✅ Autorización OK - Procedera mostrar calendario...<br>";
    
    try {
        $month = date('m');
        $year = date('Y');
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $scheduledTickets = $db->select("
            SELECT t.id, t.scheduled_date, t.scheduled_time, t.description,
                   c.name as client_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            WHERE t.scheduled_date IS NOT NULL 
            AND t.scheduled_date BETWEEN ? AND ?
            ORDER BY t.scheduled_date, t.scheduled_time
        ", [$startDate, $endDate]);
        
        echo "✅ Query calendario exitosa: " . count($scheduledTickets) . " citas encontradas<br>";
        
        if (count($scheduledTickets) > 0) {
            echo "<h3>Citas encontradas:</h3>";
            foreach ($scheduledTickets as $ticket) {
                echo "- " . $ticket['scheduled_date'] . " " . $ticket['scheduled_time'] . " - " . $ticket['client_name'] . "<br>";
            }
        }
        
        echo "<h3>🎉 ¡TODO FUNCIONA! No debería haber redirección.</h3>";
        
    } catch (Exception $e) {
        echo "❌ Error en query calendario: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Autorización FALLA - Se redirigirá<br>";
}

echo "<h2>7. Enlaces de prueba:</h2>";
echo "<a href='/admin/dashboard.php'>Dashboard</a><br>";
echo "<a href='/admin/calendar.php'>Calendar Original</a><br>";
echo "<a href='/admin/calendar_copy_local.php'>Calendar Copy</a><br>";

?>
