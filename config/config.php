<?php
require_once 'minio.php';
require_once __DIR__ . '/../includes/auto_cleanup.php';

// Start session if it hasn't started already
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 2592000);
    ini_set('session.gc_maxlifetime', 2592000);
    session_start();
}

// Enhanced termination check - this is the new code
if (isset($_SESSION['user_id'])) {
    // We need to establish database connection before checking
    $dbConfig = [
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
        'name' => getenv('DB_NAME')
    ];
    
    global $pdo;
    if (!isset($pdo)) {
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
    }
    
    try {
        $stmt = $pdo->prepare("SELECT is_active, is_terminated FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user exists and either is_active=0 or is_terminated=1, set termination status
        if ($userData && (isset($userData['is_active']) && $userData['is_active'] == 0 || 
                         isset($userData['is_terminated']) && $userData['is_terminated'] == 1)) {
            $_SESSION['is_terminated'] = true;
            
            // Get termination details if needed
            $detailsStmt = $pdo->prepare("
                SELECT termination_reason, terminated_at, terminated_by 
                FROM users WHERE user_id = :user_id
            ");
            $detailsStmt->execute([':user_id' => $_SESSION['user_id']]);
            $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($details) {
                $_SESSION['termination_reason'] = $details['termination_reason'] ?? 'No reason provided';
                $_SESSION['terminated_at'] = $details['terminated_at'] ?? null;
                $_SESSION['terminated_by'] = $details['terminated_by'] ?? null;
            }
            
            // Redirect to terminated page if not already there
            $allowedScripts = ['terminated.php', 'logout.php', 'appeal.php'];
            $currentScript = basename($_SERVER['SCRIPT_NAME']);
            
            if (!in_array($currentScript, $allowedScripts)) {
                header("Location: /terminated.php");
                exit;
            }
        } else {
            // Ensure termination status is cleared if not terminated
            $_SESSION['is_terminated'] = false;
        }
    } catch (PDOException $e) {
        // Log error but don't stop execution
        error_log("Error checking termination status: " . $e->getMessage());
    }
}
// End of new termination check code

require_once 'auth.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

require_once __DIR__ . '/../themes/theme-handler.php';

// Database connection is already established above for the termination check
if (!isset($pdo)) {
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
if (function_exists('shouldRunCleanup') && shouldRunCleanup()) {
    processAccountDeletions($pdo);
}