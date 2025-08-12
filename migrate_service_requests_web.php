<?php
/**
 * Migración web para crear la tabla service_requests en el servidor
 * Ejecutar desde: https://demo.techonway.com/migrate_service_requests_web.php
 */

// Solo permitir ejecución desde el servidor (no localhost)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    die('Este script solo puede ejecutarse en el servidor de producción.');
}

require_once __DIR__ . '/includes/init.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>Migración de tabla service_requests</h2>\n";
    echo "<p>Iniciando migración...</p>\n";

    // Crear la tabla service_requests
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "<p>✅ Tabla service_requests creada/verificada correctamente.</p>\n";

    // Verificar que la tabla se creó correctamente
    $result = $pdo->query("SHOW TABLES LIKE 'service_requests'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Tabla service_requests existe en la base de datos.</p>\n";
        
        // Mostrar estructura de la tabla
        $columns = $pdo->query("DESCRIBE service_requests")->fetchAll();
        echo "<h3>Estructura de la tabla:</h3>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>\n";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>❌ Error: La tabla service_requests no se pudo crear.</p>\n";
    }

    echo "<h3>Resultado</h3>\n";
    echo "<p><strong>✅ Migración completada exitosamente.</strong></p>\n";
    echo "<p>Ahora puedes probar el formulario en: <a href='service_request.php'>service_request.php</a></p>\n";

} catch (Exception $e) {
    echo "<h3>Error</h3>\n";
    echo "<p><strong>❌ Error durante la migración:</strong></p>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("Migration error: " . $e->getMessage());
}
?>
