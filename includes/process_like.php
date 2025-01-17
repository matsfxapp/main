<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once __DIR__ . '/../config/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$songId = $data['song_id'] ?? null;
$userId = $_SESSION['user_id'];

// Validate input
if (empty($songId) || !is_numeric($songId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid song ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND song_id = ?");
    $stmt->execute([$userId, $songId]);
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$userId, $songId]);
        
        $stmt = $pdo->prepare("UPDATE song_likes_count SET likes_count = likes_count - 1 WHERE song_id = ?");
        $stmt->execute([$songId]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, song_id) VALUES (?, ?)");
        $stmt->execute([$userId, $songId]);
        
        $stmt = $pdo->prepare("INSERT INTO song_likes_count (song_id, likes_count) 
                              VALUES (?, 1)
                              ON DUPLICATE KEY UPDATE likes_count = likes_count + 1");
        $stmt->execute([$songId]);
    }
    
    // Get updated count
    $stmt = $pdo->prepare("SELECT likes_count FROM song_likes_count WHERE song_id = ?");
    $stmt->execute([$songId]);
    $result = $stmt->fetch();
    $newCount = $result ? $result['likes_count'] : 0;
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'likes_count' => $newCount,
        'is_liked' => !$exists
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log error for debugging
    error_log('Error processing like/unlike: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again later.'
    ]);
}
