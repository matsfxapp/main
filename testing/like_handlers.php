<?php
require_once 'config/config.php';
require_once 'handlers/music_handlers.php';

function toggleLike($song_id) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $check_query = "SELECT * FROM likes WHERE user_id = :user_id AND song_id = :song_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':user_id' => $user_id, ':song_id' => $song_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $delete_query = "DELETE FROM likes WHERE user_id = :user_id AND song_id = :song_id";
        $stmt = $conn->prepare($delete_query);
        return $stmt->execute([':user_id' => $user_id, ':song_id' => $song_id]);
    } else {
        $insert_query = "INSERT INTO likes (user_id, song_id) VALUES (:user_id, :song_id)";
        $stmt = $conn->prepare($insert_query);
        return $stmt->execute([':user_id' => $user_id, ':song_id' => $song_id]);
    }
}

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