<?php
// Archivo index.php para Railway - punto de entrada principal
echo "🚀 TechonWay está funcionando en Railway!\n\n";

echo "✅ PHP Version: " . phpversion() . "\n";
echo "✅ Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "✅ Current Directory: " . getcwd() . "\n";
echo "✅ Files in directory:\n";

$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "   - $file\n";
    }
}

echo "\n🔗 Enlaces disponibles:\n";
echo "   - <a href='/login.php'>Login</a>\n";
echo "   - <a href='/import_database.php'>Importar Base de Datos</a>\n";
echo "   - <a href='/admin/dashboard.php'>Dashboard Admin</a>\n";

// Si hay parámetros específicos, redirigir
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    $allowed_pages = ['login', 'dashboard', 'admin'];
    
    if (in_array($page, $allowed_pages)) {
        header("Location: /{$page}.php");
        exit;
    }
}
?>