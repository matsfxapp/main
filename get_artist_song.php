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