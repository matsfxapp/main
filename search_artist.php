<?php
require_once 'config.php';

// search input
$search = $_GET['search'] ?? '';
if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // artist search
    $artistQuery = $pdo->prepare("
        SELECT DISTINCT artist 
        FROM users 
        WHERE artist LIKE :search 
        LIMIT 5
    ");
    $artistQuery->execute(['search' => "%$search%"]);
    $artists = $artistQuery->fetchAll(PDO::FETCH_ASSOC);

    // return results
    $results = [];

    // artist results
    foreach ($artists as $artist) {
        $results[] = [
            'type' => 'artist',
            'name' => $artist['artist']
        ];
    }

    // if not enough artist results search songs
    if (count($results) < 5) {
        $songQuery = $pdo->prepare("
            SELECT DISTINCT artist 
            FROM songs 
            WHERE artist LIKE :search 
            LIMIT " . (5 - count($results))
        );
        $songQuery->execute(['search' => "%$search%"]);
        $songArtists = $songQuery->fetchAll(PDO::FETCH_ASSOC);

        // Add unique song artists to results
        foreach ($songArtists as $artist) {
            // Avoid duplicates in $results array
            if (!in_array($artist['artist'], array_column($results, 'name'))) {
                $results[] = [
                    'type' => 'artist',
                    'name' => $artist['artist']
                ];
            }
        }
    }

    // return the results as JSON
    echo json_encode($results);
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([]);
}
exit;
