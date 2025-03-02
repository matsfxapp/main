<?php
require_once 'config/config.php';
require_once 'music_handlers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['song_id'])) {
    $songId = intval($_POST['song_id']);
    
    // Validate the song ID
    if ($songId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid song ID'
        ]);
        exit;
    }
    
    // Increment the play count
    $result = incrementPlayCount($songId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Play count updated'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update play count'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}