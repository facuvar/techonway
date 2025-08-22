<?php
/**
 * Script de diagnóstico para encontrar duplicados en el dashboard del técnico
 */
require_once 'includes/init.php';

// Requerir autenticación de técnico
$auth->requireTechnician();

// Obtener conexión a la base de datos
$db = Database::getInstance();

// Obtener ID del técnico
$technicianId = $_SESSION['user_id'];

echo "<h1>Diagnóstico de Duplicados - Dashboard Técnico</h1>";
echo "<p>Técnico ID: {$technicianId}</p>";

// 1. Verificar tickets del técnico
echo "<h2>1. Tickets asignados al técnico</h2>";
$tickets = $db->select("
    SELECT t.id, t.description, t.status, t.created_at, 
           c.name as client_name
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ?
    ORDER BY t.id
", [$technicianId]);

echo "<p>Total tickets: " . count($tickets) . "</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Cliente</th><th>Estado</th><th>Creado</th></tr>";
foreach ($tickets as $ticket) {
    echo "<tr>";
    echo "<td>{$ticket['id']}</td>";
    echo "<td>{$ticket['client_name']}</td>";
    echo "<td>{$ticket['status']}</td>";
    echo "<td>{$ticket['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Verificar visitas por ticket
echo "<h2>2. Visitas por ticket</h2>";
$visits = $db->select("
    SELECT v.id, v.ticket_id, v.start_time, v.end_time,
           t.description, c.name as client_name
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ?
    ORDER BY v.ticket_id, v.id
", [$technicianId]);

echo "<p>Total visitas: " . count($visits) . "</p>";
echo "<table border='1'>";
echo "<tr><th>ID Visita</th><th>ID Ticket</th><th>Cliente</th><th>Inicio</th><th>Fin</th></tr>";
foreach ($visits as $visit) {
    echo "<tr>";
    echo "<td>{$visit['id']}</td>";
    echo "<td>{$visit['ticket_id']}</td>";
    echo "<td>{$visit['client_name']}</td>";
    echo "<td>{$visit['start_time']}</td>";
    echo "<td>{$visit['end_time']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Verificar tickets con múltiples visitas
echo "<h2>3. Tickets con múltiples visitas</h2>";
$multipleVisits = $db->select("
    SELECT t.id as ticket_id, t.description, c.name as client_name,
           COUNT(v.id) as visit_count
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    LEFT JOIN visits v ON t.id = v.ticket_id
    WHERE t.technician_id = ?
    GROUP BY t.id
    HAVING visit_count > 1
    ORDER BY visit_count DESC
", [$technicianId]);

echo "<p>Tickets con múltiples visitas: " . count($multipleVisits) . "</p>";
if (count($multipleVisits) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID Ticket</th><th>Cliente</th><th>Descripción</th><th>Visitas</th></tr>";
    foreach ($multipleVisits as $ticket) {
        echo "<tr>";
        echo "<td>{$ticket['ticket_id']}</td>";
        echo "<td>{$ticket['client_name']}</td>";
        echo "<td>" . substr($ticket['description'], 0, 50) . "...</td>";
        echo "<td>{$ticket['visit_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay tickets con múltiples visitas.</p>";
}

// 4. Probar la consulta del dashboard
echo "<h2>4. Consulta del dashboard (citas programadas)</h2>";
$scheduledAppointments = $db->select("
    SELECT DISTINCT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
           c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ? 
    AND t.scheduled_date IS NOT NULL 
    AND t.scheduled_date >= CURDATE()
    ORDER BY t.scheduled_date, t.scheduled_time
", [$technicianId]);

echo "<p>Citas programadas: " . count($scheduledAppointments) . "</p>";
if (count($scheduledAppointments) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Cliente</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>";
    foreach ($scheduledAppointments as $apt) {
        echo "<tr>";
        echo "<td>{$apt['id']}</td>";
        echo "<td>{$apt['client_name']}</td>";
        echo "<td>{$apt['scheduled_date']}</td>";
        echo "<td>{$apt['scheduled_time']}</td>";
        echo "<td>{$apt['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Probar la consulta sin DISTINCT
echo "<h2>5. Misma consulta SIN DISTINCT (para ver duplicados)</h2>";
$appointmentsWithoutDistinct = $db->select("
    SELECT t.id, t.description, t.status, t.scheduled_date, t.scheduled_time, t.security_code,
           c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ? 
    AND t.scheduled_date IS NOT NULL 
    AND t.scheduled_date >= CURDATE()
    ORDER BY t.scheduled_date, t.scheduled_time
", [$technicianId]);

echo "<p>Resultados sin DISTINCT: " . count($appointmentsWithoutDistinct) . "</p>";
if (count($appointmentsWithoutDistinct) != count($scheduledAppointments)) {
    echo "<p style='color: red;'><strong>¡DUPLICADOS ENCONTRADOS!</strong></p>";
    echo "<p>Con DISTINCT: " . count($scheduledAppointments) . " registros</p>";
    echo "<p>Sin DISTINCT: " . count($appointmentsWithoutDistinct) . " registros</p>";
} else {
    echo "<p style='color: green;'>No hay duplicados en esta consulta.</p>";
}

// 6. Verificar tickets asignados
echo "<h2>6. Consulta de tickets asignados</h2>";
$assignedTickets = $db->select("
    SELECT DISTINCT t.id, t.description, t.status, t.created_at, t.scheduled_date, t.scheduled_time, t.security_code,
           c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            ELSE 3
        END,
        t.created_at DESC
", [$technicianId]);

echo "<p>Tickets asignados (con DISTINCT): " . count($assignedTickets) . "</p>";

$assignedTicketsWithoutDistinct = $db->select("
    SELECT t.id, t.description, t.status, t.created_at, t.scheduled_date, t.scheduled_time, t.security_code,
           c.name as client_name, c.business_name, c.address, c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            ELSE 3
        END,
        t.created_at DESC
", [$technicianId]);

echo "<p>Tickets asignados (sin DISTINCT): " . count($assignedTicketsWithoutDistinct) . "</p>";

if (count($assignedTicketsWithoutDistinct) != count($assignedTickets)) {
    echo "<p style='color: red;'><strong>¡DUPLICADOS ENCONTRADOS EN TICKETS ASIGNADOS!</strong></p>";
} else {
    echo "<p style='color: green;'>No hay duplicados en tickets asignados.</p>";
}

echo "<hr>";
echo "<h2>Conclusión</h2>";
echo "<p>Si ves duplicados en alguna sección, el problema está identificado. ";
echo "El DISTINCT debería resolver el problema en el dashboard.</p>";
?>
