<?php
/**
 * Database configuration
 */

// Detectar si estamos en local o en servidor
$isLocal = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
    || (php_sapi_name() === 'cli');

// Cargar configuración local si existe (solo para desarrollo)
$localConfig = null;
if ($isLocal && file_exists(__DIR__ . '/local.php')) {
    $localConfig = require __DIR__ . '/local.php';
}

if ($isLocal) {
    // Configuración para localhost (usa local.php si existe)
    if ($localConfig && isset($localConfig['database'])) {
        $dbConfig = $localConfig['database'];
        return [
            'host' => $dbConfig['host'],
            'dbname' => $dbConfig['dbname'],
            'username' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'charset' => $dbConfig['charset'],
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    } else {
        // Fallback para localhost sin local.php
        return [
            'host' => 'localhost',
            'dbname' => 'techonway',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
} else {
    // Configuración para servidor (Railway con variables de entorno)
    // Usar local.php para servidor de pruebas si existe
    if ($localConfig && isset($localConfig['database_server'])) {
        $dbConfig = $localConfig['database_server'];
        return [
            'host' => $_ENV['DB_HOST'] ?? $dbConfig['host'],
            'dbname' => $_ENV['DB_NAME'] ?? $dbConfig['dbname'],
            'username' => $_ENV['DB_USER'] ?? $dbConfig['username'],
            'password' => $_ENV['DB_PASSWORD'] ?? $dbConfig['password'],
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    } else {
        // Configuración solo con variables de entorno (Railway)
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['DB_NAME'] ?? '',
            'username' => $_ENV['DB_USER'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
}
