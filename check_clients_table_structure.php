<?php
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "📋 Estructura de la tabla clients:\n\n";
    
    $result = $db->select("DESCRIBE clients");
    
    echo "Campo\tTipo\tNulo\tKey\tDefault\tExtra\n";
    echo "-----\t----\t----\t---\t-------\t-----\n";
    
    foreach ($result as $column) {
        echo $column['Field'] . "\t";
        echo $column['Type'] . "\t";
        echo $column['Null'] . "\t";
        echo ($column['Key'] ?? '') . "\t";
        echo ($column['Default'] ?? '') . "\t";
        echo ($column['Extra'] ?? '') . "\n";
    }
    
    echo "\n📊 Primeros 3 clientes para verificar datos:\n\n";
    $clients = $db->select("SELECT * FROM clients LIMIT 3");
    
    if (!empty($clients)) {
        $firstClient = $clients[0];
        echo "Columnas disponibles: " . implode(', ', array_keys($firstClient)) . "\n\n";
        
        foreach ($clients as $i => $client) {
            echo "Cliente " . ($i + 1) . ":\n";
            foreach ($client as $key => $value) {
                echo "  $key: " . ($value ?: 'NULL') . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No hay clientes en la tabla.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
