<?php
require_once 'config.php';
require_once 'music_handlers.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['song_id'])) {
    $song_id = (int)$_POST['song_id'];
    
    if (toggleLike($song_id)) {
        $query = "SELECT COUNT(*) as count FROM likes WHERE song_id = :song_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':song_id' => $song_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'likes_count' => $result['count']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to toggle like']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>