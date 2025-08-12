<?php
/**
 * Punto de entrada principal - TechonWay
 * Redirige automáticamente al login
 */

// Si el usuario ya está en una URL específica, no redirigir
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Si es la raíz o index.php, redirigir a login
if ($requestUri === '/' || $requestUri === '/index.php') {
    header("Location: /login.php");
    exit;
}

// Si hay parámetros específicos, redirigir
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    $allowed_pages = ['login', 'dashboard', 'admin'];
    
    if (in_array($page, $allowed_pages)) {
        header("Location: /{$page}.php");
        exit;
    }
}

// Mostrar página de diagnóstico solo si se solicita específicamente
if (isset($_GET['debug']) && $_GET['debug'] === 'railway') {
    echo "🚀 TechonWay está funcionando en Railway! (v2.1 - Logger Fix)\n\n";
    echo "✅ PHP Version: " . phpversion() . "\n";
    echo "✅ Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "✅ Current Directory: " . getcwd() . "\n";
    echo "✅ Logger Fix Applied: " . (class_exists('Logger') ? 'YES' : 'NO') . "\n";
    echo "\n🔗 Enlaces disponibles:\n";
    echo "   - <a href='/login.php'>Login</a>\n";
    echo "   - <a href='/import_database.php'>Importar Base de Datos</a>\n";
    echo "   - <a href='/admin/dashboard.php'>Dashboard Admin</a>\n";
} else {
    // Por defecto, redirigir a login
    header("Location: /login.php");
    exit;
}
?>