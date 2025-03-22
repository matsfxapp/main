<?php
require_once 'config/config.php';
require_once 'music_handlers.php';

header('Content-Type: application/json');

$period = isset($_GET['period']) ? $_GET['period'] : 'all';

$validPeriods = ['day', 'week', 'month', 'year', 'all'];
if (!in_array($period, $validPeriods)) {
    $period = 'all';
}

$songs = getMostPlayedSongs(5, $period);

foreach ($songs as &$song) {
    if (!empty($song['file_path']) && strpos($song['file_path'], 'http') !== 0) {
        $song['file_path'] = getMinIOObjectUrl('songs', $song['file_path']);
    }
    
    if (!empty($song['cover_art']) && strpos($song['cover_art'], 'http') !== 0) {
        $song['cover_art'] = getMinIOObjectUrl('covers', $song['cover_art']);
    }
    
    foreach ($song as $key => $value) {
        if (is_string($value)) {
            $song[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
    }
}

echo json_encode($songs);