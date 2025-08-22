<?php
/**
 * Script para detectar y eliminar duplicados reales en la base de datos
 * 
 * Este script identifica y opcionalmente elimina registros duplicados
 * que están causando los problemas en el dashboard del técnico.
 */

require_once 'includes/init.php';

// Obtener conexión a la base de datos
$db = Database::getInstance();
$pdo = $db->getConnection();

echo "=== DETECCIÓN Y LIMPIEZA DE DUPLICADOS ===\n\n";

try {
    echo "1. Analizando estructura de la base de datos...\n";
    
    // Verificar si hay duplicados en la tabla tickets
    echo "\n2. Verificando duplicados en tabla TICKETS...\n";
    $ticketDuplicates = $pdo->query("
        SELECT id, client_id, assigned_to, description, COUNT(*) as count
        FROM tickets 
        GROUP BY id
        HAVING count > 1
        ORDER BY count DESC, id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Tickets con ID duplicado: " . count($ticketDuplicates) . "\n";
    
    if (count($ticketDuplicates) > 0) {
        echo "   ¡PROBLEMA CRÍTICO! Hay IDs duplicados en la tabla tickets:\n";
        foreach ($ticketDuplicates as $dup) {
            echo "     - Ticket ID {$dup['id']}: {$dup['count']} registros\n";
        }
    }
    
    // Verificar duplicados en tabla visits
    echo "\n3. Verificando duplicados en tabla VISITS...\n";
    $visitDuplicates = $pdo->query("
        SELECT id, ticket_id, start_time, COUNT(*) as count
        FROM visits 
        GROUP BY id
        HAVING count > 1
        ORDER BY count DESC, id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Visitas con ID duplicado: " . count($visitDuplicates) . "\n";
    
    if (count($visitDuplicates) > 0) {
        echo "   ¡PROBLEMA CRÍTICO! Hay IDs duplicados en la tabla visits:\n";
        foreach ($visitDuplicates as $dup) {
            echo "     - Visita ID {$dup['id']}: {$dup['count']} registros\n";
        }
    }
    
    // Verificar duplicados en tabla clients
    echo "\n4. Verificando duplicados en tabla CLIENTS...\n";
    $clientDuplicates = $pdo->query("
        SELECT id, name, COUNT(*) as count
        FROM clients 
        GROUP BY id
        HAVING count > 1
        ORDER BY count DESC, id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Clientes con ID duplicado: " . count($clientDuplicates) . "\n";
    
    if (count($clientDuplicates) > 0) {
        echo "   ¡PROBLEMA CRÍTICO! Hay IDs duplicados en la tabla clients:\n";
        foreach ($clientDuplicates as $dup) {
            echo "     - Cliente ID {$dup['id']}: {$dup['count']} registros\n";
        }
    }
    
    // Verificar integridad de claves primarias
    echo "\n5. Verificando integridad de claves primarias...\n";
    
    $tables = ['tickets', 'visits', 'clients', 'users'];
    $problemTables = [];
    
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("
                SELECT COUNT(*) as total_rows,
                       COUNT(DISTINCT id) as unique_ids
                FROM {$table}
            ")->fetch(PDO::FETCH_ASSOC);
            
            echo "   Tabla {$table}:\n";
            echo "     - Total filas: {$result['total_rows']}\n";
            echo "     - IDs únicos: {$result['unique_ids']}\n";
            
            if ($result['total_rows'] != $result['unique_ids']) {
                echo "     ❌ PROBLEMA: Hay {$result['total_rows']} filas pero solo {$result['unique_ids']} IDs únicos\n";
                $problemTables[] = $table;
            } else {
                echo "     ✅ OK: Integridad correcta\n";
            }
        } catch (Exception $e) {
            echo "     ❌ ERROR: No se pudo verificar tabla {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    // Si hay problemas, ofrecer soluciones
    if (count($problemTables) > 0) {
        echo "\n6. SOLUCIONES RECOMENDADAS:\n";
        echo "\n¡ATENCIÓN! Se detectaron duplicados en las siguientes tablas: " . implode(', ', $problemTables) . "\n";
        echo "\nEsto explica por qué aparecen registros triplicados en el dashboard.\n";
        echo "\nPARA SOLUCIONARLO:\n";
        echo "\n1. HACER BACKUP COMPLETO DE LA BASE DE DATOS:\n";
        echo "   mysqldump -u usuario -p techonway > backup_antes_limpieza.sql\n";
        echo "\n2. EJECUTAR LIMPIEZA (¡CUIDADO! ESTO ELIMINARÁ DUPLICADOS):\n";
        
        foreach ($problemTables as $table) {
            echo "\n   Para tabla {$table}:\n";
            echo "   -- Crear tabla temporal con registros únicos\n";
            echo "   CREATE TABLE {$table}_temp AS \n";
            echo "   SELECT * FROM {$table} \n";
            echo "   GROUP BY id;\n";
            echo "   \n";
            echo "   -- Reemplazar tabla original\n";
            echo "   DROP TABLE {$table};\n";
            echo "   RENAME TABLE {$table}_temp TO {$table};\n";
            echo "   \n";
            echo "   -- Restaurar claves primarias e índices\n";
            echo "   ALTER TABLE {$table} ADD PRIMARY KEY (id);\n";
            echo "   ALTER TABLE {$table} MODIFY id INT AUTO_INCREMENT;\n";
        }
        
        echo "\n3. VERIFICAR INTEGRIDAD POST-LIMPIEZA:\n";
        echo "   php fix_database_duplicates.php\n";
        
    } else {
        echo "\n6. DIAGNÓSTICO ALTERNATIVO:\n";
        echo "\nNo se encontraron duplicados en las claves primarias.\n";
        echo "El problema puede estar en:\n";
        echo "\n1. CONSULTAS SQL mal formadas que generan productos cartesianos\n";
        echo "2. JOINS múltiples que multiplican resultados\n";
        echo "3. CAMPOS technician_id vs assigned_to inconsistentes\n";
        echo "\nVamos a verificar consultas específicas...\n";
        
        // Probar consulta problemática del debug
        echo "\n7. Probando consulta específica del debug...\n";
        $testQuery = "
            SELECT t.id, t.description, t.status, t.created_at, 
                   c.name as client_name
            FROM tickets t
            JOIN clients c ON t.client_id = c.id
            WHERE t.assigned_to = 4
            ORDER BY t.id
        ";
        
        $testResults = $pdo->query($testQuery)->fetchAll(PDO::FETCH_ASSOC);
        echo "   Resultados de consulta corregida: " . count($testResults) . " registros\n";
        
        if (count($testResults) > 0) {
            echo "   Primeros 5 resultados:\n";
            for ($i = 0; $i < min(5, count($testResults)); $i++) {
                $r = $testResults[$i];
                echo "     - Ticket {$r['id']}: {$r['client_name']} ({$r['status']})\n";
            }
        }
    }
    
    echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}
?>
