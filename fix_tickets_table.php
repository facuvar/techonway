<?php
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h1>🔧 Actualizando estructura tabla tickets en Railway</h1>";
    
    // Lista de columnas que necesitamos agregar
    $columnsToAdd = [
        "assigned_to INT NULL COMMENT 'ID del técnico asignado'",
        "priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'Prioridad del ticket'",
        "scheduled_date DATE NULL COMMENT 'Fecha programada para la cita'",
        "scheduled_time TIME NULL COMMENT 'Hora programada para la cita'",
        "security_code VARCHAR(10) NULL COMMENT 'Código de seguridad para la cita'",
        "notes TEXT NULL COMMENT 'Notas adicionales del ticket'",
        "start_notes TEXT NULL COMMENT 'Notas al iniciar la visita'",
        "phone VARCHAR(20) NULL COMMENT 'Teléfono de contacto'"
    ];
    
    // Verificar columnas existentes
    $stmt = $pdo->prepare("DESCRIBE tickets");
    $stmt->execute();
    $existingColumns = $stmt->fetchAll();
    
    $existingColumnNames = [];
    foreach ($existingColumns as $column) {
        $existingColumnNames[] = $column['Field'];
    }
    
    echo "<h2>📋 Agregando columnas faltantes...</h2>";
    
    $addedCount = 0;
    foreach ($columnsToAdd as $columnDef) {
        // Extraer nombre de columna
        preg_match('/^(\w+)/', $columnDef, $matches);
        $columnName = $matches[1];
        
        if (!in_array($columnName, $existingColumnNames)) {
            try {
                $sql = "ALTER TABLE tickets ADD COLUMN $columnDef";
                $pdo->exec($sql);
                echo "✅ Agregada columna: <strong>$columnName</strong><br>";
                $addedCount++;
            } catch (Exception $e) {
                echo "❌ Error agregando $columnName: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "⚠️ Columna $columnName ya existe<br>";
        }
    }
    
    // Verificar si necesitamos agregar foreign key para assigned_to
    if (!in_array('assigned_to', $existingColumnNames)) {
        try {
            $sql = "ALTER TABLE tickets ADD FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL";
            $pdo->exec($sql);
            echo "✅ Agregada foreign key para assigned_to<br>";
        } catch (Exception $e) {
            echo "⚠️ No se pudo agregar foreign key: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>📊 Resumen:</h2>";
    echo "✅ Columnas agregadas: $addedCount<br>";
    
    // Verificar estructura final
    $stmt = $pdo->prepare("DESCRIBE tickets");
    $stmt->execute();
    $finalColumns = $stmt->fetchAll();
    
    echo "<h2>📋 Estructura final de tabla tickets:</h2>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        echo "<li><strong>" . $column['Field'] . "</strong> (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<h2>🎉 ¡Actualización completada!</h2>";
    echo "<p><a href='/admin/calendar_direct.php'>🔗 Probar calendario directo</a></p>";
    echo "<p><a href='/admin/calendar.php'>🔗 Probar calendario normal</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
