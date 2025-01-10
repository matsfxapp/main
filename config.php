<?php
require_once 'themes/theme-handler.php';

define('DB_HOST', 'localhost:3306');
define('DB_USER', 'mathis_1234554321');
define('DB_PASS', 'h^k3Du464');
define('DB_NAME', 'tziipreq_');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo = $conn;
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function sanitizeInput($data) {
    global $conn;
    return htmlspecialchars(trim($data), ENT_QUOTES);
}
?>