<?php
// Track play count for songs
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

// Log request for debugging
error_log('Received play tracking request');

// Get the request body
$raw_data = file_get_contents('php://input');
error_log('Raw request data: ' . $raw_data);

$data = json_decode($raw_data, true);
$songId = $data['song_id'] ?? null;

// Log parsed data
error_log('Parsed song_id: ' . ($songId ?? 'null'));

// Validate input
if (empty($songId) || !is_numeric($songId)) {
    error_log('Invalid song ID for tracking: ' . ($songId ?? 'null'));
    echo json_encode(['success' => false, 'error' => 'Invalid song ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Increment play count in songs table
    $stmt = $pdo->prepare("UPDATE songs SET play_count = play_count + 1 WHERE song_id = ?");
    $result = $stmt->execute([$songId]);
    
    if (!$result) {
        error_log('Failed to update play count for song ID: ' . $songId);
        throw new Exception('Failed to update play count');
    }
    
    // Record user play if logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Insert play record
        $stmt = $pdo->prepare("
            INSERT INTO song_plays 
            (song_id, user_id, play_date) 
            VALUES (?, ?, NOW())
        ");
        $result = $stmt->execute([$songId, $userId]);
        
        if (!$result) {
            error_log('Failed to record play for user ID: ' . $userId);
            throw new Exception('Failed to record play');
        }
    }
    
    // Get updated play count
    $stmt = $pdo->prepare("SELECT play_count FROM songs WHERE song_id = ?");
    $stmt->execute([$songId]);
    $result = $stmt->fetch();
    $playCount = $result ? $result['play_count'] : 0;
    
    error_log('Successfully updated play count for song ID ' . $songId . ' to ' . $playCount);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'play_count' => $playCount,
        'song_id' => $songId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log error for debugging
    error_log('Error tracking play: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while tracking play count: ' . $e->getMessage()
    ]);
}