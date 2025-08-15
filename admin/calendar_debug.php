<?php
/**
 * Debug version del calendario
 */
echo "<h1>Calendar Debug</h1>";

try {
    echo "<p>1. Iniciando includes...</p>";
    require_once '../includes/init.php';
    echo "<p>✅ includes/init.php cargado</p>";

    echo "<p>2. Verificando autenticación...</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "</p>";
    echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "</p>";
    
    // Verificar Auth sin requireAdmin
    if (isset($auth)) {
        echo "<p>✅ Objeto Auth existe</p>";
        echo "<p>Is Admin: " . ($auth->isAdmin() ? 'SÍ' : 'NO') . "</p>";
        echo "<p>Is Authenticated: " . ($auth->isAuthenticated() ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p>❌ Objeto Auth no existe</p>";
    }

    echo "<p>3. Probando requireAdmin()...</p>";
    $auth->requireAdmin();
    echo "<p>✅ requireAdmin() pasó sin problemas</p>";

    echo "<p>4. Conectando a base de datos...</p>";
    $db = Database::getInstance();
    echo "<p>✅ Database conectada</p>";

    echo "<p>5. Probando consulta de tickets...</p>";
    $month = date('m');
    $year = date('Y');
    
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

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
        LIMIT 5
    ", [$startDate, $endDate]);
    
    echo "<p>✅ Consulta ejecutada, " . count($scheduledTickets) . " tickets encontrados</p>";
    
    echo "<p>6. Todo funcionando correctamente!</p>";
    echo "<p><a href='calendar.php'>Ir al calendario normal</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
