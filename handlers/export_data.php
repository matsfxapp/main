<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../user_handlers.php';
require_once __DIR__ . '/../music_handlers.php';

if (!isLoggedIn()) {
    http_response_code(403);
    die("Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'json';

// Get user data
$userData = getUserData($user_id);
unset($userData['password']); // Never export passwords

// Get user songs
$userSongs = getUserSongs($user_id);

// Get user's likes
try {
    $likesStmt = $pdo->prepare("
        SELECT s.song_id, s.title, s.artist, s.album, l.created_at as liked_at 
        FROM likes l
        JOIN songs s ON l.song_id = s.song_id
        WHERE l.user_id = ?
    ");
    $likesStmt->execute([$user_id]);
    $userLikes = $likesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user likes: " . $e->getMessage());
    $userLikes = [];
}

// Get play history
try {
    $playHistoryStmt = $pdo->prepare("
        SELECT s.song_id, s.title, s.artist, sp.play_date 
        FROM song_plays sp
        JOIN songs s ON sp.song_id = s.song_id
        WHERE sp.user_id = ?
        ORDER BY sp.play_date DESC
        LIMIT 100
    ");
    $playHistoryStmt->execute([$user_id]);
    $playHistory = $playHistoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching play history: " . $e->getMessage());
    $playHistory = [];
}

// Prepare export data
$exportData = [
    'user' => $userData,
    'songs' => $userSongs,
    'likes' => $userLikes,
    'play_history' => $playHistory,
    'export_date' => date('Y-m-d H:i:s')
];

// Set headers
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="matsfx_user_data_' . $user_id . '_' . date('Y-m-d') . '.json"');

if ($format === 'json') {
    // Export as JSON
    echo json_encode($exportData, JSON_PRETTY_PRINT);
} elseif ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="matsfx_user_data_' . $user_id . '_' . date('Y-m-d') . '.csv"');
    
    // User data CSV
    $csvOutput = "User Data\n";
    foreach ($userData as $key => $value) {
        $csvOutput .= "$key,$value\n";
    }
    
    // Songs CSV
    $csvOutput .= "\nSongs\n";
    $csvOutput .= "song_id,title,artist,album,genre\n";
    foreach ($userSongs as $song) {
        $csvOutput .= implode(',', [
            $song['song_id'], 
            '"' . str_replace('"', '""', $song['title']) . '"', 
            '"' . str_replace('"', '""', $song['artist']) . '"', 
            '"' . str_replace('"', '""', $song['album'] ?? '') . '"',
            '"' . str_replace('"', '""', $song['genre'] ?? '') . '"'
        ]) . "\n";
    }
    
    // Likes CSV
    $csvOutput .= "\nLikes\n";
    $csvOutput .= "song_id,title,artist,liked_at\n";
    foreach ($userLikes as $like) {
        $csvOutput .= implode(',', [
            $like['song_id'], 
            '"' . str_replace('"', '""', $like['title']) . '"', 
            '"' . str_replace('"', '""', $like['artist']) . '"', 
            $like['liked_at']
        ]) . "\n";
    }
    
    echo $csvOutput;
}
exit();