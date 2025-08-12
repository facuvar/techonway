<?php
// Capturador mínimo de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP funciona: " . phpversion() . "<br>";

// Test 1: Archivo login.php
echo "<br>Test login.php:<br>";
if(file_exists('login.php')) {
    echo "- Archivo existe<br>";
    $syntax = shell_exec('php -l login.php 2>&1');
    echo "- Sintaxis: " . $syntax . "<br>";
} else {
    echo "- NO EXISTE<br>";
}

// Test 2: includes/init.php
echo "<br>Test includes/init.php:<br>";
if(file_exists('includes/init.php')) {
    echo "- Archivo existe<br>";
    $syntax = shell_exec('php -l includes/init.php 2>&1');
    echo "- Sintaxis: " . $syntax . "<br>";
} else {
    echo "- NO EXISTE<br>";
}
?>
