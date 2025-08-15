<?php
/**
 * Script para eliminar clientes duplicados en Railway
 * Mantiene el cliente con ID menor (más antiguo) y elimina las copias
 */

require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>🧹 Eliminando clientes duplicados</h1>";
    
    // Buscar duplicados por nombre y dirección
    echo "<h2>📋 Analizando duplicados...</h2>";
    
    $duplicates = $db->select("
        SELECT name, address, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM clients 
        GROUP BY name, address 
        HAVING COUNT(*) > 1
        ORDER BY name
    ");
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✅ No se encontraron clientes duplicados</p>";
        echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🔗 Ir a Clientes</a></p>";
        exit;
    }
    
    echo "<p style='color: orange;'>⚠️ Encontrados " . count($duplicates) . " grupos de clientes duplicados:</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #2D3142; color: white;'>";
    echo "<th style='padding: 10px;'>Cliente</th><th style='padding: 10px;'>Dirección</th><th style='padding: 10px;'>Copias</th><th style='padding: 10px;'>IDs</th>";
    echo "</tr>";
    
    $totalDuplicatesToDelete = 0;
    
    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($dup['name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($dup['address'] ?? 'Sin dirección') . "</td>";
        echo "<td style='padding: 8px;'>" . $dup['count'] . "</td>";
        echo "<td style='padding: 8px;'>" . $dup['ids'] . "</td>";
        echo "</tr>";
        
        $totalDuplicatesToDelete += ($dup['count'] - 1); // Mantener 1, eliminar el resto
    }
    
    echo "</table>";
    
    echo "<p><strong>📊 Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>Grupos duplicados: " . count($duplicates) . "</li>";
    echo "<li>Registros a eliminar: " . $totalDuplicatesToDelete . "</li>";
    echo "<li>Registros a mantener: " . count($duplicates) . " (los más antiguos)</li>";
    echo "</ul>";
    
    echo "<h2>🔧 Eliminando duplicados...</h2>";
    
    $deletedCount = 0;
    
    foreach ($duplicates as $dup) {
        $ids = explode(',', $dup['ids']);
        $keepId = $ids[0]; // Mantener el ID más pequeño (más antiguo)
        
        echo "<p>📝 Cliente: <strong>" . htmlspecialchars($dup['name']) . "</strong></p>";
        echo "<p>✅ Manteniendo ID: $keepId</p>";
        
        // Eliminar los IDs duplicados (todos excepto el primero)
        for ($i = 1; $i < count($ids); $i++) {
            $deleteId = $ids[$i];
            
            // Verificar si tiene tickets asociados
            $ticketsCount = $db->selectOne("SELECT COUNT(*) as count FROM tickets WHERE client_id = ?", [$deleteId]);
            
            if ($ticketsCount['count'] > 0) {
                echo "<p style='color: orange;'>⚠️ ID $deleteId tiene {$ticketsCount['count']} tickets. Moviendo a ID $keepId...</p>";
                
                // Mover los tickets al cliente que vamos a mantener
                $db->query("UPDATE tickets SET client_id = ? WHERE client_id = ?", [$keepId, $deleteId]);
                echo "<p style='color: green;'>✅ Tickets movidos correctamente</p>";
            }
            
            // Ahora eliminar el cliente duplicado
            $db->query("DELETE FROM clients WHERE id = ?", [$deleteId]);
            echo "<p style='color: red;'>🗑️ Eliminado ID: $deleteId</p>";
            
            $deletedCount++;
        }
        
        echo "<hr>";
    }
    
    echo "<h2>🎉 ¡Limpieza completada!</h2>";
    echo "<p style='color: green; font-size: 1.2em;'>✅ Eliminados $deletedCount clientes duplicados</p>";
    
    // Verificar resultado final
    echo "<h2>📊 Verificación final</h2>";
    
    $finalDuplicates = $db->select("
        SELECT name, address, COUNT(*) as count
        FROM clients 
        GROUP BY name, address 
        HAVING COUNT(*) > 1
    ");
    
    if (empty($finalDuplicates)) {
        echo "<p style='color: green; font-weight: bold;'>✅ ¡No quedan duplicados! Limpieza exitosa.</p>";
    } else {
        echo "<p style='color: red;'>❌ Aún quedan " . count($finalDuplicates) . " duplicados. Revisar manualmente.</p>";
    }
    
    // Mostrar total final de clientes
    $totalClients = $db->selectOne("SELECT COUNT(*) as count FROM clients");
    echo "<p><strong>👥 Total de clientes únicos:</strong> " . $totalClients['count'] . "</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<p><a href='/admin/clients.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔗 Ver Clientes Limpios</a></p>";
    echo "<p><a href='/admin/dashboard.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔗 Ir al Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 1200px; 
    margin: 20px auto; 
    padding: 20px; 
    background: #f8f9fa;
}
h1 { 
    color: #2D3142; 
    border-bottom: 3px solid #2D3142; 
    padding-bottom: 10px; 
}
h2 { 
    color: #5B6386; 
    border-left: 4px solid #5B6386; 
    padding-left: 10px; 
}
table { 
    width: 100%; 
    background: white; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th, td { 
    text-align: left; 
    border-bottom: 1px solid #ddd;
}
p { 
    margin: 10px 0; 
    line-height: 1.4;
}
hr {
    margin: 20px 0;
    border: 1px solid #ddd;
}
ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
a {
    display: inline-block;
    margin: 5px;
}
</style>
