<?php
/**
 * Simple i18n loader and translate helper
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Supported languages
$SUPPORTED_LANGS = ['es', 'en'];

// Determine current language
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], $SUPPORTED_LANGS)) {
    $_SESSION['lang'] = 'es';
}

// Load language file
$lang = [];
$langFile = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    $lang = require $langFile;
} else {
    // Fallback to Spanish if language file doesn't exist
    $langFile = __DIR__ . '/../lang/es.php';
    if (file_exists($langFile)) {
        $lang = require $langFile;
    }
}

/**
 * Translation helper
 *
 * @param string $key
 * @param string|null $fallback
 * @return string
 */
function __($key, $fallback = null) {
    global $lang;
    if (isset($lang[$key])) {
        return $lang[$key];
    }
    return $fallback !== null ? $fallback : $key;
}




