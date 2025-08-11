<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Crear tabla settings si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

echo "Tabla 'settings' verificada/creada.\n";




