<?php
/**
 * Initialization file
 * Include this file at the beginning of every page
 */

// Cargar configuración de zona horaria para Argentina
require_once dirname(__FILE__) . '/timezone_config.php';

// Configurar sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Detectar si estamos en Railway
    $isRailway = isset($_ENV['RAILWAY_ENVIRONMENT']);
    
    // Configurar duración de sesión (8 horas = 28800 segundos)
    ini_set('session.gc_maxlifetime', 28800);
    ini_set('session.cookie_lifetime', 28800);
    
    // Configuración específica para Railway
    if ($isRailway) {
        // En Railway, usar configuración más simple
        session_set_cookie_params([
            'lifetime' => 28800,
            'path' => '/',
            'domain' => '',  // Dejar vacío para Railway
            'secure' => true,  // Siempre HTTPS en Railway
            'httponly' => true,
            'samesite' => 'None'  // Cambiar a None para Railway
        ]);
        
        // Configurar nombre de sesión único
        session_name('TECHONWAY_SESSION');
    } else {
        // Configuración para local
        session_set_cookie_params([
            'lifetime' => 28800,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    session_start();
    
    // Debug específico para Railway
    if ($isRailway && (isset($_GET['debug_session']) || !isset($_SESSION['user_id']))) {
        error_log("Railway Session Debug - Session ID: " . session_id() . ", Cookie params: " . json_encode(session_get_cookie_params()) . ", Session data: " . json_encode($_SESSION ?? []));
    }
    
    // Regenerar ID de sesión cada 30 minutos para seguridad
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Set error reporting - suprimir warnings en producción
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    // En localhost mostrar todos los errores
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // En servidor solo errores críticos para evitar headers prematuros
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('INCLUDE_PATH', BASE_PATH . '/includes');
define('TEMPLATE_PATH', BASE_PATH . '/templates');
// Detectar si estamos en local o en servidor
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    define('BASE_URL', '/sistema-techonway/');
} else {
    define('BASE_URL', '/');
}

// Load required files
// Registrar autoloader de Composer si existe
$isLocal = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
if ($isLocal && file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Cargar Logger PRIMERO para que esté disponible para Database y Auth
if (file_exists(INCLUDE_PATH . '/Logger.php')) {
    require_once INCLUDE_PATH . '/Logger.php';
}

// Inicializar sistema de logs - Solo en local para evitar sobrecarga en VPS
if ($isLocal) {
    Logger::init();
    
    // Registrar handlers para errores y excepciones automáticas
    set_error_handler(['Logger', 'logPhpError']);
    set_exception_handler(['Logger', 'logException']);
}

require_once INCLUDE_PATH . '/Database.php';
require_once INCLUDE_PATH . '/Auth.php';
require_once INCLUDE_PATH . '/i18n.php';
require_once INCLUDE_PATH . '/SecurityCodeGenerator.php';
require_once INCLUDE_PATH . '/EmailNotifier.php';

// Initialize auth
$auth = new Auth();

// Helper functions
function redirect($url) {
    // If URL doesn't start with http or /, add BASE_URL
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = BASE_URL . $url;
    } elseif (strpos($url, '/') === 0 && strpos($url, BASE_URL) !== 0) {
        $url = BASE_URL . $url;
    }
    
    // Verificar que no se hayan enviado headers
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        // Fallback JavaScript si headers ya fueron enviados
        echo '<script>window.location.href = "' . htmlspecialchars($url) . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '">';
        exit;
    }
}

function escape($string) {
    // Verificar si el valor es nulo y devolver una cadena vacía en ese caso
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function flash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function isActive($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}
