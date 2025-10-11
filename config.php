<?php 
// config.php - Project-wide configuration and helper functions.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database Credentials ---
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '@l03e1t3'); // For default XAMPP, the password is often empty.
if (!defined('DB_NAME')) define('DB_NAME', 'versogym');

// --- Site Configuration ---
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/WebProj');

// Uploads config (avatars)
if (!defined('AVATARS_DIR')) define('AVATARS_DIR', __DIR__ . '/uploads/avatars'); // server filesystem path
if (!defined('AVATAR_URL_PATH')) define('AVATAR_URL_PATH', '/uploads/avatars'); // web-accessible path (leading slash ok)

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Returns a mysqli connection. Uses exceptions for errors.
 *
 * @return mysqli
 * @throws Exception
 */
if (!function_exists('getDbConnection')) {
    function getDbConnection(): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            $conn->set_charset('utf8mb4');
            return $conn;
        } catch (mysqli_sql_exception $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            // Throw a generic exception so calling code can handle display-friendly messages
            throw new Exception('Database connection failed.');
        }
    }
}

/**
 * Clean input for safe output/DB usage (not a replacement for prepared statements).
 *
 * @param string|null $value
 * @return string
 */
if (!function_exists('clean_input')) {
    function clean_input($data) {
        // sanitize a string for DB usage (we also use prepared statements)
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}
/**
 * Generate a CSRF token and store in session.
 *
 * @return string
 */
if (!function_exists('csrf_generate')) {
    function csrf_generate(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Validate CSRF token provided by a form.
 *
 * @param string|null $token
 * @return bool
 */
if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
    }
}

/**
 * Shortcut for escaping output
 *
 * @param string|null $s
 * @return string
 */
if (!function_exists('e')) {
    function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Is user logged in?
 *
 * @return bool
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

/**
 * Require login helper (redirects to login.php if not logged in).
 *
 * @return void
 */
if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            header('Location: login.php');
            exit();
        }
    }
}

/**
 * Save a data URL (base64) image to the avatars directory and return the web path.
 * Returns null on failure.
 *
 * Usage: $path = save_profile_image($dataUrl, $userId);
 *
 * @param string $dataUrl
 * @param int|null $userId
 * @return string|null
 */
if (!function_exists('save_profile_image')) {
    function save_profile_image(string $dataUrl, ?int $userId = null): ?string
    {
        // Validate data url
        if (strpos($dataUrl, 'data:') !== 0) return null;
        if (!preg_match('#^data:image/([a-zA-Z0-9+]+);base64,#', $dataUrl, $m)) return null;
        $ext = strtolower($m[1]);
        // normalize common types
        if ($ext === 'jpeg') $ext = 'jpg';
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) return null;

        // Decode base64 payload
        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) return null;
        $data = base64_decode($parts[1]);
        if ($data === false) return null;

        // Ensure target directory exists
        $dir = AVATARS_DIR;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log('Failed to create avatars directory: ' . $dir);
                return null;
            }
        }

        // Build a safe filename
        $timestamp = time();
        $uid = $userId ? intval($userId) : rand(1000, 9999);
        $filename = sprintf('avatar_%d_%d.%s', $uid, $timestamp, $ext);
        $filePath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Write file
        $written = @file_put_contents($filePath, $data);
        if ($written === false) {
            error_log('Failed to write avatar file: ' . $filePath);
            return null;
        }

        // Return a web-accessible path (consistent with other code expecting /uploads/avatars/...)
        $webPath = rtrim(AVATAR_URL_PATH, '/') . '/' . $filename;
        return $webPath;
    }
}