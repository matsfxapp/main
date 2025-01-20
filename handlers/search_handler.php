<?php
require_once __DIR__ . '/../config/config.php';

function searchAll($query) {
    global $pdo;
    $query = '%' . trim($query) . '%';

    $artistStmt = $pdo->prepare("
        SELECT DISTINCT s.artist, u.profile_picture, 'artist' as type 
        FROM songs s
        LEFT JOIN users u ON s.uploaded_by = u.user_id 
        WHERE s.artist LIKE :query 
        LIMIT 5
    ");
    $artistStmt->execute(['query' => $query]);
    $artists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);

    $songStmt = $pdo->prepare("
        SELECT song_id, title, artist, cover_art, 'song' as type 
        FROM songs 
        WHERE title LIKE :query 
        LIMIT 5
    ");
    $songStmt->execute(['query' => $query]);
    $songs = $songStmt->fetchAll(PDO::FETCH_ASSOC);

    $userStmt = $pdo->prepare("
        SELECT DISTINCT u.username, u.profile_picture, 'user' as type 
        FROM users u
        WHERE u.username LIKE :query
          AND NOT EXISTS (
              SELECT 1 FROM songs s WHERE s.uploaded_by = u.user_id
          )
        LIMIT 5
    ");
    $userStmt->execute(['query' => $query]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'artists' => $artists,
        'songs' => $songs,
        'users' => $users
    ];
}

if (isset($_GET['query'])) {
    header('Content-Type: application/json');
    $results = searchAll($_GET['query']);
    echo json_encode($results);
    exit();
}
?>