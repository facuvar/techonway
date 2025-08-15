<?php
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h1>🔧 Reparando TODAS las tablas en Railway</h1>";
    
    // 1. Arreglar tabla USERS
    echo "<h2>👥 Tabla USERS</h2>";
    
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $userColumns = $stmt->fetchAll();
    
    $userColumnNames = [];
    foreach ($userColumns as $column) {
        $userColumnNames[] = $column['Field'];
    }
    
    $userColumnsToAdd = [
        "last_name VARCHAR(100) NULL COMMENT 'Apellido del usuario'",
        "phone VARCHAR(20) NULL COMMENT 'Teléfono del usuario'",
        "avatar VARCHAR(255) NULL COMMENT 'Ruta del avatar del usuario'"
    ];
    
    foreach ($userColumnsToAdd as $columnDef) {
        preg_match('/^(\w+)/', $columnDef, $matches);
        $columnName = $matches[1];
        
        if (!in_array($columnName, $userColumnNames)) {
            try {
                $sql = "ALTER TABLE users ADD COLUMN $columnDef";
                $pdo->exec($sql);
                echo "✅ Agregada columna users.$columnName<br>";
            } catch (Exception $e) {
                echo "❌ Error agregando users.$columnName: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "⚠️ Columna users.$columnName ya existe<br>";
        }
    }
    
    // 2. Arreglar tabla TICKETS
    echo "<h2>🎫 Tabla TICKETS</h2>";
    
    $stmt = $pdo->prepare("DESCRIBE tickets");
    $stmt->execute();
    $ticketColumns = $stmt->fetchAll();
    
    $ticketColumnNames = [];
    foreach ($ticketColumns as $column) {
        $ticketColumnNames[] = $column['Field'];
    }
    
    $ticketColumnsToAdd = [
        "assigned_to INT NULL COMMENT 'ID del técnico asignado'",
        "priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'Prioridad del ticket'",
        "scheduled_date DATE NULL COMMENT 'Fecha programada para la cita'",
        "scheduled_time TIME NULL COMMENT 'Hora programada para la cita'",
        "security_code VARCHAR(10) NULL COMMENT 'Código de seguridad para la cita'",
        "notes TEXT NULL COMMENT 'Notas adicionales del ticket'",
        "start_notes TEXT NULL COMMENT 'Notas al iniciar la visita'",
        "phone VARCHAR(20) NULL COMMENT 'Teléfono de contacto'"
    ];
    
    foreach ($ticketColumnsToAdd as $columnDef) {
        preg_match('/^(\w+)/', $columnDef, $matches);
        $columnName = $matches[1];
        
        if (!in_array($columnName, $ticketColumnNames)) {
            try {
                $sql = "ALTER TABLE tickets ADD COLUMN $columnDef";
                $pdo->exec($sql);
                echo "✅ Agregada columna tickets.$columnName<br>";
            } catch (Exception $e) {
                echo "❌ Error agregando tickets.$columnName: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "⚠️ Columna tickets.$columnName ya existe<br>";
        }
    }
    
    // 3. Verificar/crear tabla VISITS
    echo "<h2>🏠 Tabla VISITS</h2>";
    
    try {
        $stmt = $pdo->prepare("DESCRIBE visits");
        $stmt->execute();
        echo "✅ Tabla visits ya existe<br>";
    } catch (Exception $e) {
        echo "❌ Tabla visits no existe. Creándola...<br>";
        
        $createVisitsSQL = "
        CREATE TABLE visits (
            id int NOT NULL AUTO_INCREMENT,
            ticket_id int NOT NULL,
            technician_id int NOT NULL,
            start_time timestamp NULL,
            end_time timestamp NULL,
            notes text,
            completed tinyint(1) DEFAULT '0',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ticket_id (ticket_id),
            KEY technician_id (technician_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $pdo->exec($createVisitsSQL);
            echo "✅ Tabla visits creada exitosamente<br>";
        } catch (Exception $e) {
            echo "❌ Error creando tabla visits: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. Verificar/crear tabla SETTINGS
    echo "<h2>⚙️ Tabla SETTINGS</h2>";
    
    try {
        $stmt = $pdo->prepare("DESCRIBE settings");
        $stmt->execute();
        echo "✅ Tabla settings ya existe<br>";
    } catch (Exception $e) {
        echo "❌ Tabla settings no existe. Creándola...<br>";
        
        $createSettingsSQL = "
        CREATE TABLE settings (
            id int NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $pdo->exec($createSettingsSQL);
            echo "✅ Tabla settings creada exitosamente<br>";
        } catch (Exception $e) {
            echo "❌ Error creando tabla settings: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>🎉 ¡Reparación completada!</h2>";
    echo "<p><strong>Prueba ahora:</strong></p>";
    echo "<p><a href='/admin/calendar_direct.php'>🔗 Calendario directo</a></p>";
    echo "<p><a href='/admin/calendar.php'>🔗 Calendario normal</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR GENERAL:</h2>";
    echo "<div style='background:#ffe6e6; padding:10px; border:1px solid red;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
