<?php
/**
 * Script para importar la base de datos a Railway
 * Este script se ejecuta UNA VEZ después del despliegue inicial
 */

// Solo permitir ejecución en Railway (no en localhost)
$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
           strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false);

if ($isLocal) {
    die("❌ Este script solo debe ejecutarse en Railway, no en localhost");
}

echo "<h1>🚀 Importador de Base de Datos - Railway</h1>";
echo "<pre>";

// Verificar que tenemos las variables de entorno
$required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$missing_vars = [];

foreach ($required_vars as $var) {
    if (empty($_ENV[$var])) {
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    echo "❌ ERROR: Faltan variables de entorno: " . implode(', ', $missing_vars) . "\n";
    echo "Configúralas en Railway y vuelve a intentar.\n";
    exit;
}

echo "✅ Variables de entorno encontradas\n";

// Verificar que existe el archivo de backup
$backup_file = __DIR__ . '/database_backup.sql';
if (!file_exists($backup_file)) {
    echo "❌ ERROR: No se encontró database_backup.sql\n";
    echo "Asegúrate de que el archivo esté en el repositorio.\n";
    exit;
}

echo "✅ Archivo database_backup.sql encontrado (" . number_format(filesize($backup_file)) . " bytes)\n";

// Conectar a la base de datos de Railway
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Conexión a Railway MySQL exitosa\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: No se pudo conectar a la base de datos\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit;
}

// Leer y ejecutar el archivo SQL
echo "📥 Leyendo archivo SQL...\n";

$sql_content = file_get_contents($backup_file);
if ($sql_content === false) {
    echo "❌ ERROR: No se pudo leer el archivo SQL\n";
    exit;
}

echo "✅ Archivo SQL leído correctamente\n";

// Dividir en statements individuales
$statements = array_filter(
    array_map('trim', explode(';', $sql_content)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^(--|\/\*)/', $stmt);
    }
);

echo "📋 Encontrados " . count($statements) . " statements SQL\n";

// Ejecutar cada statement
$success_count = 0;
$error_count = 0;

echo "🔄 Ejecutando importación...\n";

foreach ($statements as $i => $statement) {
    try {
        if (trim($statement)) {
            $pdo->exec($statement);
            $success_count++;
            
            // Mostrar progreso cada 10 statements
            if (($i + 1) % 10 === 0) {
                echo "   Procesados: " . ($i + 1) . "/" . count($statements) . "\n";
            }
        }
    } catch (PDOException $e) {
        $error_count++;
        echo "⚠️  Error en statement " . ($i + 1) . ": " . $e->getMessage() . "\n";
        
        // Mostrar el statement problemático (primeros 100 caracteres)
        echo "   SQL: " . substr(trim($statement), 0, 100) . "...\n";
    }
}

echo "\n🎉 IMPORTACIÓN COMPLETADA\n";
echo "✅ Statements exitosos: $success_count\n";
echo "❌ Statements con error: $error_count\n";

// Verificar algunas tablas principales
echo "\n🔍 Verificando tablas importadas:\n";

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas encontradas: " . count($tables) . "\n";
    
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "   - $table: $count registros\n";
    }
    
} catch (PDOException $e) {
    echo "⚠️  Error verificando tablas: " . $e->getMessage() . "\n";
}

echo "\n✅ IMPORTACIÓN FINALIZADA\n";
echo "🔗 Ahora puedes probar tu aplicación en Railway\n";
echo "\n⚠️  IMPORTANTE: Elimina este archivo después de usarlo por seguridad\n";

echo "</pre>";
?>
