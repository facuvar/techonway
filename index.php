<?php
/**
 * Main entry point - redirects to login page
 */

// Suprimir warnings para evitar headers prematuros
error_reporting(E_ERROR | E_PARSE);

// Temporary redirect to emergency system while database recovers
if (!headers_sent()) {
    header('Location: emergency_login.php');
    exit;
} else {
    echo '<script>window.location.href = "emergency_login.php";</script>';
    echo '<meta http-equiv="refresh" content="0;url=emergency_login.php">';
    echo '<p>Redirecting to <a href="emergency_login.php">emergency login</a>...</p>';
    exit;
}
