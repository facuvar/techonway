<?php
/**
 * Migración para agregar columna avatar a usuarios
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Verificar si la columna ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $exists = (bool) $stmt->fetch();
    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER phone");
        echo "Columna 'avatar' agregada a 'users'.";
    } else {
        echo "La columna 'avatar' ya existe.";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error modificando la tabla users: " . $e->getMessage();
}




