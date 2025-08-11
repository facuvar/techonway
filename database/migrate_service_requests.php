<?php
/**
 * Migración para crear la tabla service_requests si no existe
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(100) NOT NULL DEFAULT 'Visita técnica',
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        address VARCHAR(255) NOT NULL,
        detail TEXT NULL,
        ticket_id INT NULL,
        status ENUM('pending','in_progress','closed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_service_requests_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Asegurar columnas y FK si la tabla ya existía
    $columns = $pdo->query("SHOW COLUMNS FROM service_requests")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ticket_id', $columns)) {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN ticket_id INT NULL AFTER detail");
    }
    // Intentar crear FK si no existe
    try {
        $pdo->exec("ALTER TABLE service_requests ADD CONSTRAINT fk_service_requests_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL");
    } catch (Exception $ex) {
        // Puede existir previamente, ignorar error
    }

    echo "Tabla service_requests verificada/creada correctamente.";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error creando la tabla: " . $e->getMessage();
}


