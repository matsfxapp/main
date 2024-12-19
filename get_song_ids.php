<?php
header('Content-Type: application/json');
require_once 'config.php';

$title = $_GET['title'] ?? '';
$artist = $_GET['artist'] ?? '';

if (empty($title) || empty($artist)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Title and artist are required'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT song_id FROM songs WHERE title = :title AND artist = :artist LIMIT 1");
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':artist', $artist, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'song_id' => $result['song_id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Song not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>