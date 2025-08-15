<?php
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h1>🔍 Estructura de tabla tickets en Railway</h1>";
    
    // Verificar estructura de la tabla tickets
    $stmt = $pdo->prepare("DESCRIBE tickets");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<h2>📋 Columnas en tabla tickets:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si scheduled_date existe
    $hasScheduledDate = false;
    $hasScheduledTime = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'scheduled_date') {
            $hasScheduledDate = true;
        }
        if ($column['Field'] === 'scheduled_time') {
            $hasScheduledTime = true;
        }
    }
    
    echo "<h2>📊 Estado de columnas de citas:</h2>";
    echo ($hasScheduledDate ? "✅" : "❌") . " scheduled_date<br>";
    echo ($hasScheduledTime ? "✅" : "❌") . " scheduled_time<br>";
    
    if (!$hasScheduledDate || !$hasScheduledTime) {
        echo "<h2>🔧 Comando para agregar columnas faltantes:</h2>";
        echo "<pre>";
        if (!$hasScheduledDate) {
            echo "ALTER TABLE tickets ADD COLUMN scheduled_date DATE NULL;<br>";
        }
        if (!$hasScheduledTime) {
            echo "ALTER TABLE tickets ADD COLUMN scheduled_time TIME NULL;<br>";
        }
        echo "</pre>";
    }
    
    // Contar tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<h2>📊 Total de tickets: " . $result['total'] . "</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
