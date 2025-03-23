<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../music_handlers.php';

header('Content-Type: application/json');

$genre = isset($_GET['genre']) ? $_GET['genre'] : '';

if (empty($genre)) {
    echo json_encode(['error' => 'No genre specified']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM songs 
        WHERE genre = :genre 
        ORDER BY play_count DESC 
        LIMIT 6
    ");
    
    $stmt->execute([':genre' => $genre]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($songs as &$song) {
        if (!empty($song['file_path']) && strpos($song['file_path'], 'http') !== 0) {
            $song['file_path'] = getMinIOObjectUrl('songs', $song['file_path']);
        }
        
        if (!empty($song['cover_art']) && strpos($song['cover_art'], 'http') !== 0) {
            $song['cover_art'] = getMinIOObjectUrl('covers', $song['cover_art']);
        }
    }
    
    echo json_encode($songs);
} catch (PDOException $e) {
    error_log("Error fetching genre songs: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>