<?php
// Test mínimo para index
echo "Test básico funcionando";

try {
    require_once 'includes/init.php';
    echo " - Sistema cargado";
} catch (Exception $e) {
    echo " - ERROR: " . $e->getMessage();
}
?>
