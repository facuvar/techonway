<?php
/**
 * Migración automática para Railway
 * 
 * Este script se ejecuta automáticamente en Railway para sincronizar
 * la base de datos de producción con las nuevas columnas
 */

// Solo ejecutar en Railway (no en local)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    die("Este script solo se ejecuta en Railway\n");
}

require_once __DIR__ . '/includes/init.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Log de inicio
    error_log("Railway Migration: Iniciando migración automática");
    
    // Verificar si assigned_to existe
    $columns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('assigned_to', $columns)) {
        // Crear campo assigned_to
        $pdo->exec("ALTER TABLE tickets ADD COLUMN assigned_to INT NULL AFTER technician_id");
        
        // Sincronizar datos
        $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
        
        error_log("Railway Migration: Campo assigned_to creado y sincronizado");
    } else {
        // Verificar sincronización
        $unsyncedCount = $pdo->query("
            SELECT COUNT(*) 
            FROM tickets 
            WHERE (technician_id IS NULL AND assigned_to IS NOT NULL) 
               OR (technician_id IS NOT NULL AND assigned_to IS NULL)
               OR (technician_id != assigned_to)
        ")->fetchColumn();
        
        if ($unsyncedCount > 0) {
            // Re-sincronizar
            $pdo->exec("UPDATE tickets SET assigned_to = technician_id WHERE technician_id IS NOT NULL");
            error_log("Railway Migration: Re-sincronizados {$unsyncedCount} tickets");
        }
    }
    
    error_log("Railway Migration: Migración completada exitosamente");
    
} catch (Exception $e) {
    error_log("Railway Migration ERROR: " . $e->getMessage());
}
?>
