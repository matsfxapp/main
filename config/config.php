<?php
require_once 'minio.php';
require_once __DIR__ . '/../includes/auto_cleanup.php';

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 2592000);
    ini_set('session.gc_maxlifetime', 2592000);
    session_start();
}

require_once 'auth.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

require_once __DIR__ . '/../themes/theme-handler.php';

$dbConfig = [
    'host' => getenv('DB_HOST'),
    'user' => getenv('DB_USER'),
    'pass' => getenv('DB_PASS'),
    'name' => getenv('DB_NAME')
];

global $pdo;
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}", 
        $dbConfig['user'], 
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            logoutUser();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Automatically process account deletions occasionally
if (shouldRunCleanup()) {
    processAccountDeletions($pdo);
}