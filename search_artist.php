<?php
require_once 'config.php';
require_once 'music_handlers.php';

header('Content-Type: application/json');

if (isset($_GET['search'])) {
    $artists = searchArtists($_GET['search']);
    echo json_encode($artists);
} else {
    echo json_encode([]);
}
?>