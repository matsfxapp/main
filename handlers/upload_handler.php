<?php
require_once '../config/config.php';
require_once '../music_handlers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to upload']);
    exit();
}

$response = ['success' => false, 'message' => 'Unknown error occurred'];

try {
    ensureMinIOBuckets();
    
    $currentUser = $_SESSION['username'] ?? 'Unknown User';
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    // Get form data
    $title = sanitizeInput($_POST['title'] ?? '');
    $artist = sanitizeInput($currentUser);
    $useExistingAlbum = isset($_POST['use_existing_album']) ? (int)$_POST['use_existing_album'] : 0;
    
    // Determine album
    if ($useExistingAlbum === 0) {
        // New album
        $album = sanitizeInput($_POST['album'] ?? '');
    } elseif ($useExistingAlbum === 1) {
        // Existing album
        $album = sanitizeInput($_POST['existing_album'] ?? '');
    } else {
        // No album (single track)
        $album = '';
    }
    
    $genre = sanitizeInput($_POST['genre'] ?? '');
    
    // Validate song file
    if (!isset($_FILES['song_file']) || $_FILES['song_file']['error'] !== UPLOAD_ERR_OK) {
        $response = ['success' => false, 'message' => 'Please select a valid song file'];
        echo json_encode($response);
        exit();
    }
    
    // Verify file type and size
    $validSongTypes = $minioConfig['allowed_types']['songs'] ?? ['audio/mpeg', 'audio/wav', 'audio/x-wav'];
    $validImageTypes = $minioConfig['allowed_types']['images'] ?? ['image/jpeg', 'image/png', 'image/webp'];
    
    $songFileType = $_FILES['song_file']['type'];
    if (!in_array($songFileType, $validSongTypes)) {
        $response = ['success' => false, 'message' => 'Invalid song file type. Please upload MP3 or WAV files only.'];
        echo json_encode($response);
        exit();
    }
    
    $maxSongSize = $minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024);
    if ($_FILES['song_file']['size'] > $maxSongSize) {
        $response = ['success' => false, 'message' => 'Song file is too large. Maximum size is ' . ($maxSongSize / (1024 * 1024)) . 'MB.'];
        echo json_encode($response);
        exit();
    }
    
    // Handle cover art
    $coverArt = null;
    $existingCover = null;
    
    if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_OK) {
        // Verify cover art file type and size
        $coverFileType = $_FILES['cover_art']['type'];
        $validImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($coverFileType, $validImageTypes)) {
            $response = [
                'success' => false, 
                'message' => 'Please upload cover art in JPG, PNG or WebP format only.'
            ];
            echo json_encode($response);
            exit();
        }
        
        $maxCoverSize = $minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024);
        if ($_FILES['cover_art']['size'] > $maxCoverSize) {
            $response = ['success' => false, 'message' => 'Cover art file is too large. Maximum size is ' . ($maxCoverSize / (1024 * 1024)) . 'MB.'];
            echo json_encode($response);
            exit();
        }
        
        $coverArt = $_FILES['cover_art'];
    } elseif ($useExistingAlbum === 1 && !empty($album)) {
        // Try to get existing album cover
        try {
            $stmt = $pdo->prepare("SELECT cover_art FROM songs WHERE album = :album AND uploaded_by = :user_id AND cover_art IS NOT NULL AND cover_art != '/defaults/default-cover.jpg' LIMIT 1");
            $stmt->execute([':album' => $album, ':user_id' => $currentUserId]);
            $existingCover = $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Failed to fetch existing album cover: " . $e->getMessage());
        }
    }
    
    // Upload the song
    $uploadResult = uploadSong($title, $artist, $album, $genre, $_FILES['song_file'], $coverArt, $existingCover);
    
    if ($uploadResult['success']) {
        $response = [
            'success' => true,
            'message' => 'Song uploaded successfully!',
            'song_id' => $uploadResult['song_id'] ?? null
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error uploading song: ' . $uploadResult['message']
        ];
    }
} catch (Exception $e) {
    $response = [
        'success' => false, 
        'message' => 'Upload failed: ' . $e->getMessage()
    ];
    error_log("Upload error: " . $e->getMessage());
}

echo json_encode($response);
