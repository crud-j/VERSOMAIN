<?php
// backend/config.php - Thin wrapper to load main project config and define optional constants

// Load the main project configuration which defines getDbConnection(), helpers, and constants
require_once __DIR__ . '/../config.php';

// Google OAuth constants (optional). Define here only if not already defined in environment.
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', '1007094319099-0k29ipdh5q797sbl3aa4q7b2l3360on0.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', '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');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    // Use BASE_URL from main config if available, else fallback to localhost
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost/WebProj';
    define('GOOGLE_REDIRECT_URI', $base . '/google_callback.php');
}
