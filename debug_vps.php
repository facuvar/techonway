<?php
/**
 * Debug completo para VPS - Ver errores detallados
 */

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_errors.log');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug VPS - TechonWay</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #333; background: #2a2a2a; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #0099ff; }
        h2 { color: #ffff00; border-bottom: 2px solid #ffff00; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
        .test-btn { background: #333; color: #fff; padding: 10px; margin: 5px; border: none; cursor: pointer; }
        .test-btn:hover { background: #555; }
    </style>
</head>
<body>
    <h1>🔍 Debug Completo VPS - TechonWay</h1>
    
    <div class="section">
        <h2>📊 Información del Servidor</h2>
        <div class="info">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible'; ?><br>
            <strong>Script Path:</strong> <?php echo __DIR__; ?><br>
            <strong>Current User:</strong> <?php echo get_current_user(); ?><br>
            <strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?><br>
            <strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?><br>
            <strong>Display Errors:</strong> <?php echo ini_get('display_errors') ? 'ON' : 'OFF'; ?><br>
            <strong>Error Reporting:</strong> <?php echo error_reporting(); ?><br>
        </div>
    </div>
    
    <div class="section">
        <h2>📁 Archivos del Sistema</h2>
        <?php
        $files_to_check = [
            'includes/init.php',
            'config/database.php',
            'config/local.php',
            'login.php',
            'dashboard.php',
            '.htaccess'
        ];
        
        foreach ($files_to_check as $file) {
            $exists = file_exists($file);
            $readable = $exists ? is_readable($file) : false;
            $size = $exists ? filesize($file) : 0;
            
            echo "<div class='" . ($exists ? 'success' : 'error') . "'>";
            echo "<strong>$file:</strong> ";
            echo $exists ? "✅ Existe" : "❌ No existe";
            if ($exists) {
                echo " | " . ($readable ? "✅ Legible" : "❌ No legible");
                echo " | Tamaño: " . number_format($size) . " bytes";
            }
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>🔧 Extensiones PHP</h2>
        <?php
        $required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "<div class='" . ($loaded ? 'success' : 'error') . "'>";
            echo "<strong>$ext:</strong> " . ($loaded ? "✅ Cargada" : "❌ No cargada");
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>🧪 Pruebas de Funcionalidad</h2>
        
        <button class="test-btn" onclick="testIncludes()">Probar Includes</button>
        <button class="test-btn" onclick="testDatabase()">Probar Base de Datos</button>
        <button class="test-btn" onclick="testSession()">Probar Sesiones</button>
        <button class="test-btn" onclick="viewErrorLog()">Ver Log de Errores</button>
        
        <div id="test-results" style="margin-top: 20px;"></div>
    </div>
    
    <div class="section">
        <h2>📝 Últimos Errores de PHP</h2>
        <pre id="error-log">
<?php
$error_log = __DIR__ . '/debug_errors.log';
if (file_exists($error_log)) {
    $errors = file_get_contents($error_log);
    echo htmlspecialchars($errors);
} else {
    echo "No hay errores registrados aún.";
}
?>
        </pre>
    </div>
    
    <div class="section">
        <h2>🔍 Probar Carga de Archivos Específicos</h2>
        <?php
        // Probar cargar init.php paso a paso
        echo "<h3>Probando includes/init.php:</h3>";
        
        try {
            echo "<div class='info'>Intentando incluir includes/init.php...</div>";
            
            if (!file_exists('includes/init.php')) {
                echo "<div class='error'>❌ includes/init.php no existe</div>";
            } else {
                echo "<div class='success'>✅ includes/init.php existe</div>";
                
                // Leer las primeras líneas para verificar sintaxis
                $content = file_get_contents('includes/init.php', false, null, 0, 500);
                echo "<div class='info'>Primeras líneas del archivo:</div>";
                echo "<pre>" . htmlspecialchars($content) . "</pre>";
                
                // Verificar sintaxis
                $syntax_check = shell_exec("php -l includes/init.php 2>&1");
                if (strpos($syntax_check, 'No syntax errors') !== false) {
                    echo "<div class='success'>✅ Sintaxis correcta</div>";
                } else {
                    echo "<div class='error'>❌ Error de sintaxis: " . htmlspecialchars($syntax_check) . "</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
    
    <script>
    function testIncludes() {
        fetch('debug_vps.php?test=includes')
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML = data;
            });
    }
    
    function testDatabase() {
        fetch('debug_vps.php?test=database')
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML = data;
            });
    }
    
    function testSession() {
        fetch('debug_vps.php?test=session')
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML = data;
            });
    }
    
    function viewErrorLog() {
        fetch('debug_vps.php?test=errorlog')
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML = data;
            });
    }
    </script>
    
</body>
</html>

<?php
// Manejar tests AJAX
if (isset($_GET['test'])) {
    $test = $_GET['test'];
    
    switch ($test) {
        case 'includes':
            echo "<h3>🧪 Probando Includes</h3>";
            try {
                require_once 'includes/init.php';
                echo "<div class='success'>✅ includes/init.php cargado exitosamente</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error cargando init.php: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'database':
            echo "<h3>🧪 Probando Base de Datos</h3>";
            try {
                require_once 'config/database.php';
                $config = getDatabaseConfig();
                echo "<div class='info'>Configuración cargada: " . json_encode($config, JSON_PRETTY_PRINT) . "</div>";
                
                $pdo = new PDO(
                    "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
                echo "<div class='success'>✅ Conexión a base de datos exitosa</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'session':
            echo "<h3>🧪 Probando Sesiones</h3>";
            try {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['test'] = 'funcionando';
                echo "<div class='success'>✅ Sesiones funcionando correctamente</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error de sesión: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'errorlog':
            echo "<h3>📝 Log de Errores Actualizado</h3>";
            $error_log = __DIR__ . '/debug_errors.log';
            if (file_exists($error_log)) {
                $errors = file_get_contents($error_log);
                echo "<pre>" . htmlspecialchars($errors) . "</pre>";
            } else {
                echo "<div class='info'>No hay errores registrados.</div>";
            }
            break;
    }
    exit;
}
?>
