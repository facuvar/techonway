<?php
require_once 'includes/init.php';

$db = Database::getInstance();

echo "=== TÉCNICOS CON TICKETS ASIGNADOS ===\n\n";

$result = $db->select("
    SELECT assigned_to, COUNT(*) as count 
    FROM tickets 
    WHERE assigned_to IS NOT NULL 
    GROUP BY assigned_to 
    ORDER BY count DESC
");

foreach($result as $r) { 
    echo "Técnico {$r['assigned_to']}: {$r['count']} tickets\n"; 
}

echo "\n=== TOTAL DE TICKETS ===\n";
$total = $db->selectOne("SELECT COUNT(*) as total FROM tickets")['total'];
echo "Total de tickets en la base de datos: {$total}\n";
?>
