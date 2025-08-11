<?php
/**
 * Initialization file
 * Include this file at the beginning of every page
 */

// Cargar configuración de zona horaria para Argentina
require_once dirname(__FILE__) . '/timezone_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
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
require_once INCLUDE_PATH . '/Database.php';
require_once INCLUDE_PATH . '/Auth.php';
require_once INCLUDE_PATH . '/i18n.php';

// Inicializar sistema de logs (DESACTIVADO temporalmente para debug)
if ($isLocal) {
    require_once INCLUDE_PATH . '/Logger.php';
    Logger::init();
    
    // Registrar handlers para errores y excepciones automáticas
    set_error_handler(['Logger', 'logPhpError']);
    set_exception_handler(['Logger', 'logException']);
}

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
