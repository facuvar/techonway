<?php
require_once __DIR__ . '/includes/init.php';

$lang = $_GET['lang'] ?? 'es';
$allowed = ['es', 'en'];
if (!in_array($lang, $allowed)) {
    $lang = 'es';
}

$_SESSION['lang'] = $lang;

$redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'dashboard.php');
header('Location: ' . $redirect);
exit;




