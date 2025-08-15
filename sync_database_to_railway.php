<?php
/**
 * Script para sincronizar base de datos local con Railway
 */

echo "<h1>🔄 Sincronización de Base de Datos Local → Railway</h1>";

// Detectar entorno
$isRailway = isset($_ENV['RAILWAY_ENVIRONMENT']);
$isLocal = !$isRailway;

echo "<h2>📍 Entorno detectado: " . ($isRailway ? "RAILWAY" : "LOCAL") . "</h2>";

if ($isLocal) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📤 MODO LOCAL: Exportar datos hacia Railway</h3>";
    echo "<p>Este script exportará los datos de tu base de datos local para importarlos en Railway.</p>";
    echo "</div>";
    
    try {
        // Configuración local
        $localConfig = require 'config/database.php';
        $pdo = new PDO("mysql:host={$localConfig['host']};dbname={$localConfig['dbname']};charset=utf8mb4", 
                      $localConfig['username'], $localConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p>✅ Conectado a DB local: {$localConfig['dbname']}</p>";
        
        // Tablas principales a sincronizar
        $tables = ['users', 'clients', 'tickets', 'visits', 'settings'];
        $exportData = [];
        
        foreach ($tables as $table) {
            echo "<h3>📋 Exportando tabla: $table</h3>";
            
            // Verificar si la tabla existe
            $checkTable = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if (!$checkTable) {
                echo "<p style='color: orange;'>⚠️ Tabla $table no existe, saltando...</p>";
                continue;
            }
            
            // Obtener estructura
            $structure = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            $exportData[$table]['structure'] = $structure;
            
            // Obtener datos
            $data = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            $exportData[$table]['data'] = $data;
            $exportData[$table]['count'] = count($data);
            
            echo "<p>✅ Exportados {$exportData[$table]['count']} registros de $table</p>";
            
            // Mostrar algunos datos de muestra
            if ($table === 'users') {
                echo "<p><strong>Usuarios encontrados:</strong></p>";
                foreach ($data as $user) {
                    echo "<li>{$user['name']} ({$user['email']}) - {$user['role']}</li>";
                }
            } elseif ($table === 'clients') {
                echo "<p><strong>Clientes encontrados:</strong> " . count($data) . "</p>";
            } elseif ($table === 'tickets') {
                echo "<p><strong>Tickets encontrados:</strong> " . count($data) . "</p>";
                $withSchedule = array_filter($data, fn($t) => !empty($t['scheduled_date']));
                echo "<p><strong>Con citas programadas:</strong> " . count($withSchedule) . "</p>";
            }
        }
        
        // Generar archivo SQL para Railway
        $sqlFile = "railway_sync_" . date('Y-m-d_H-i-s') . ".sql";
        $sql = "-- Sincronización de base de datos local a Railway\n";
        $sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            if (!isset($exportData[$table])) continue;
            
            $sql .= "-- Tabla: $table\n";
            $sql .= "TRUNCATE TABLE `$table`;\n";
            
            if (!empty($exportData[$table]['data'])) {
                $sql .= "INSERT INTO `$table` (";
                $columns = array_keys($exportData[$table]['data'][0]);
                $sql .= "`" . implode("`, `", $columns) . "`";
                $sql .= ") VALUES \n";
                
                $values = [];
                foreach ($exportData[$table]['data'] as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = "(" . implode(", ", $rowValues) . ")";
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Guardar archivo SQL
        file_put_contents($sqlFile, $sql);
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>📁 Archivo SQL generado: $sqlFile</h3>";
        echo "<p><strong>Instrucciones:</strong></p>";
        echo "<ol>";
        echo "<li>Descarga el archivo: <a href='$sqlFile' download>$sqlFile</a></li>";
        echo "<li>Ejecuta: <code>railway run php sync_database_to_railway.php</code></li>";
        echo "<li>O sube el archivo y ejecuta desde Railway</li>";
        echo "</ol>";
        echo "</div>";
        
        // También crear un script PHP para Railway
        $railwayScript = "railway_import.php";
        $scriptContent = "<?php\n";
        $scriptContent .= "// Script de importación para Railway\n";
        $scriptContent .= "echo 'Importando datos a Railway...';\n";
        $scriptContent .= "require_once 'config/database.php';\n";
        $scriptContent .= "\$config = require 'config/database.php';\n";
        $scriptContent .= "\$pdo = new PDO(\"mysql:host={\$config['host']};dbname={\$config['dbname']};charset=utf8mb4\", \$config['username'], \$config['password']);\n";
        $scriptContent .= "\$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
        $scriptContent .= "\$sql = file_get_contents('$sqlFile');\n";
        $scriptContent .= "\$pdo->exec(\$sql);\n";
        $scriptContent .= "echo 'Importación completada!';\n";
        
        file_put_contents($railwayScript, $scriptContent);
        
        echo "<p>✅ También generado: $railwayScript para ejecutar en Railway</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📥 MODO RAILWAY: Importar datos</h3>";
    echo "<p>Buscando archivo de sincronización...</p>";
    echo "</div>";
    
    // Buscar archivo SQL más reciente
    $files = glob("railway_sync_*.sql");
    if (empty($files)) {
        echo "<p style='color: red;'>❌ No se encontró archivo de sincronización. Ejecuta este script primero en local.</p>";
    } else {
        $latestFile = max($files);
        echo "<p>📁 Archivo encontrado: $latestFile</p>";
        
        try {
            // Configurar Railway DB
            $config = require 'config/database.php';
            $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                          $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<p>✅ Conectado a Railway DB: {$config['dbname']}</p>";
            
            // Ejecutar SQL
            $sql = file_get_contents($latestFile);
            $pdo->exec($sql);
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>🎉 ¡Importación completada exitosamente!</h3>";
            echo "<p>La base de datos de Railway ahora tiene los mismos datos que tu localhost.</p>";
            echo "</div>";
            
            // Verificar datos importados
            $tables = ['users', 'clients', 'tickets', 'visits'];
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p>✅ $table: $count registros</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error en importación: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h2>🔗 Enlaces útiles:</h2>";
echo "<p><a href='debug_railway.php'>🔧 Debug Railway</a></p>";
echo "<p><a href='admin/calendar_temp.php'>📅 Calendario Temporal</a></p>";
echo "<p><a href='admin/calendar.php'>📅 Calendario Principal</a></p>";
?>
