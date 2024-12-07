<?php
require_once 'config.php';

$artist = $_GET['artist'] ?? '';
if (empty($artist)) {
    echo json_encode(['profile_picture' => null]);
    exit;
}

try {
    $profileQuery = $pdo->prepare("
        SELECT profile_picture 
        FROM users 
        WHERE artist = :artist
    ");
    $profileQuery->execute(['artist' => $artist]);
    $profileData = $profileQuery->fetch(PDO::FETCH_ASSOC);

    $profilePicture = $profileData['profile_picture'] ?? null;
    echo json_encode(['profile_picture' => $profilePicture]);
} catch (PDOException $e) {
    error_log("Artist profile retrieval error: " . $e->getMessage());
    echo json_encode(['profile_picture' => null]);
}
exit;