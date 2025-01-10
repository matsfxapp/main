<?php
require_once 'config.php';

function ensureUploadDirectory() {
    $path = 'uploads/playlist_covers';
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

function convertToWebP($source, $destination, $quality = 80) {
    $extension = pathinfo($source, PATHINFO_EXTENSION);
    $extension = strtolower($extension);

    if ($extension == 'jpeg' || $extension == 'jpg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($extension == 'png') {
        $image = imagecreatefrompng($source);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    } else {
        return false;
    }

    return imagewebp($image, $destination, $quality);
}

function handlePlaylistCover($file) {
    $uploadDir = ensureUploadDirectory();
    $uniqueId = uniqid();
    $filename = $uniqueId . '_' . basename($file['name']);
    $uploadPath = $uploadDir . '/' . $filename;
    
    // Check if image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return false;
    }

    // Convert to WebP
    $webpFilename = $uniqueId . '.webp';
    $webpPath = $uploadDir . '/' . $webpFilename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        if (convertToWebP($uploadPath, $webpPath)) {
            // Delete original file after conversion
            unlink($uploadPath);
            return 'uploads/playlist_covers/' . $webpFilename;
        }
    }
    return false;
}

function createPlaylist($userId, $playlistName, $coverImage = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO playlists (user_id, playlist_name, cover_image) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $playlistName, $coverImage);
    $stmt->execute();
    return $conn->insert_id;
}

function addSongToPlaylist($playlistId, $songId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $playlistId, $songId);
    return $stmt->execute();
}

function getPlaylists($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY playlist_id ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function getPlaylist($playlistId) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.username FROM playlists p JOIN users u ON p.user_id = u.user_id WHERE p.playlist_id = ?");
    $stmt->bind_param("i", $playlistId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getPlaylistSongs($playlistId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT s.* FROM songs s
        JOIN playlist_songs ps ON s.song_id = ps.song_id
        WHERE ps.playlist_id = ?
        ORDER BY ps.added_at ASC
    ");
    $stmt->bind_param("i", $playlistId);
    $stmt->execute();
    return $stmt->get_result();
}

function getAllSongs() {
    global $conn;
    return $conn->query("SELECT * FROM songs ORDER BY Name ASC");
}

function updatePlaylistCover($playlistId, $coverImage) {
    global $conn;
    // Delete old cover if exists
    $stmt = $conn->prepare("SELECT cover_image FROM playlists WHERE playlist_id = ?");
    $stmt->bind_param("i", $playlistId);
    $stmt->execute();
    $result = $stmt->get_result();
    $playlist = $result->fetch_assoc();
    
    if ($playlist['cover_image'] && file_exists($playlist['cover_image'])) {
        unlink($playlist['cover_image']);
    }
    
    $stmt = $conn->prepare("UPDATE playlists SET cover_image = ? WHERE playlist_id = ?");
    $stmt->bind_param("si", $coverImage, $playlistId);
    return $stmt->execute();
}
?>