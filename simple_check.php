<?php
$pdo = new PDO('mysql:host=localhost;dbname=techonway', 'root', '');

echo "Verificando tickets:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
echo "Total tickets: " . $stmt->fetchColumn() . "\n";

echo "\nTécnicos con tickets:\n";
$stmt = $pdo->query("SELECT assigned_to, COUNT(*) as count FROM tickets WHERE assigned_to IS NOT NULL GROUP BY assigned_to");
while ($row = $stmt->fetch()) {
    echo "Técnico {$row['assigned_to']}: {$row['count']} tickets\n";
}
?>
