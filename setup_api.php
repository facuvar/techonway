<?php
/**
 * API para el setup de Railway
 */

header('Content-Type: application/json');

// Solo permitir en Railway
$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
           strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false);

if ($isLocal) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo disponible en Railway']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_env':
        checkEnvironmentVariables();
        break;
        
    case 'check_db':
        checkDatabaseConnection();
        break;
        
    case 'import_db':
        importDatabase();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function checkEnvironmentVariables() {
    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    $missing = [];
    
    foreach ($required as $var) {
        if (empty($_ENV[$var])) {
            $missing[] = $var;
        }
    }
    
    if (empty($missing)) {
        echo json_encode([
            'success' => true,
            'message' => 'Todas las variables están configuradas'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'missing' => $missing
        ]);
    }
}

function checkDatabaseConnection() {
    try {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Verificar si ya hay tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);
        
        echo json_encode([
            'success' => true,
            'info' => "Base de datos: {$_ENV['DB_NAME']}, Tablas existentes: {$tableCount}",
            'tables' => $tableCount
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function importDatabase() {
    try {
        // Verificar que existe el archivo de backup
        $backup_file = __DIR__ . '/database_backup.sql';
        if (!file_exists($backup_file)) {
            echo json_encode([
                'success' => false,
                'error' => 'Archivo database_backup.sql no encontrado'
            ]);
            return;
        }
        
        // Conectar a la base de datos
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Leer archivo SQL
        $sql_content = file_get_contents($backup_file);
        if ($sql_content === false) {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo leer el archivo SQL'
            ]);
            return;
        }
        
        // Dividir en statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(--|\/\*)/', $stmt);
            }
        );
        
        // Ejecutar statements
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            try {
                if (trim($statement)) {
                    $pdo->exec($statement);
                    $success_count++;
                }
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = substr($statement, 0, 100) . '... - ' . $e->getMessage();
            }
        }
        
        // Verificar tablas creadas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableStats = [];
        
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $tableStats[] = "$table: $count registros";
        }
        
        echo json_encode([
            'success' => true,
            'stats' => count($tables) . " tablas, $success_count statements exitosos",
            'details' => [
                'tables' => count($tables),
                'statements_success' => $success_count,
                'statements_error' => $error_count,
                'table_stats' => $tableStats,
                'errors' => array_slice($errors, 0, 5) // Solo primeros 5 errores
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
