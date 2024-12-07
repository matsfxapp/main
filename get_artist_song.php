<?php
require_once 'config.php';
require_once 'music_handlers.php';

header('Content-Type: application/json');

if (isset($_GET['artist'])) {
    $songs = getArtistSongs($_GET['artist']);
    echo json_encode($songs);
} else {
    echo json_encode([]);
}
?>

<?php
require_once 'config.php';

$artist = $_GET['artist'] ?? '';
if (empty($artist)) {
    echo json_encode([]);
    exit;
}

try {
    $songQuery = $pdo->prepare("
        SELECT 
            title, 
            artist, 
            COALESCE(cover_art, 'defaults/default-cover.jpg') as cover_art, 
            file_path 
        FROM songs 
        WHERE artist = :artist
    ");
    $songQuery->execute(['artist' => $artist]);
    $songs = $songQuery->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($songs);
} catch (PDOException $e) {
    error_log("Artist songs retrieval error: " . $e->getMessage());
    echo json_encode([]);
}
exit;