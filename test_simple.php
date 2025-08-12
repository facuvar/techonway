<?php
// Test ultra simple para VPS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Simple VPS</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Fecha: " . date('Y-m-d H:i:s') . "</p>";

// Test básico de archivos
echo "<h2>Archivos:</h2>";
$files = ['login.php', 'includes/init.php', 'config/database.php'];
foreach($files as $file) {
    echo "<p>$file: " . (file_exists($file) ? "SI" : "NO") . "</p>";
}

// Test de includes/init.php
echo "<h2>Test init.php:</h2>";
try {
    include 'includes/init.php';
    echo "<p style='color:green'>EXITO: init.php cargado</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<p>Fin del test</p>";
?>
