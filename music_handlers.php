<?php
require_once 'config.php';

function uploadSong($title, $artist, $album, $genre, $file, $cover_art) {
    global $conn;
    
    // if not exist create folders
    $upload_dir = "uploads/songs/";
    $cover_dir = "uploads/covers/";
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    if (!file_exists($cover_dir)) {
        mkdir($cover_dir, 0777, true);
    }
    
    // Generate filenames
    $song_filename = uniqid() . "_" . basename($file["name"]);
    $song_path = $upload_dir . $song_filename;
    
    // Handle cover art
    $cover_path = null;
    if ($cover_art && $cover_art["error"] == 0) {
        $cover_filename = uniqid() . "_" . basename($cover_art["name"]);
        $cover_path = $cover_dir . $cover_filename;
        move_uploaded_file($cover_art["tmp_name"], $cover_path);
    }
    
    // Upload song file
    if (move_uploaded_file($file["tmp_name"], $song_path)) {
        try {
            $query = "INSERT INTO songs (title, artist, album, genre, file_path, cover_art, uploaded_by) 
                     VALUES (:title, :artist, :album, :genre, :file_path, :cover_path, :uploaded_by)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':title' => $title,
                ':artist' => $artist,
                ':album' => $album,
                ':genre' => $genre,
                ':file_path' => $song_path,
                ':cover_path' => $cover_path,
                ':uploaded_by' => $_SESSION['user_id']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

function getAllSongs() {
    global $conn;
    
    try {
        $query = "SELECT * FROM songs ORDER BY upload_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function searchArtists($search) {
    global $conn;
    try {
        $search = '%' . $search . '%';
        $stmt = $conn->prepare("SELECT DISTINCT artist FROM songs WHERE artist LIKE :search");
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function getArtistSongs($artist) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM songs WHERE artist = :artist");
        $stmt->bindValue(':artist', $artist, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function getArtistProfilePicture($artist) {
    global $conn;
    
    $artistProfileDir = 'uploads/artist_profiles/';
    if (!file_exists($artistProfileDir)) {
        mkdir($artistProfileDir, 0777, true);
    }
    
    $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($validExtensions as $ext) {
        $artistProfilePath = $artistProfileDir . sanitizeFilename($artist) . '.' . $ext;
        if (file_exists($artistProfilePath)) {
            return $artistProfilePath;
        }
    }
    
    try {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE username = :username");
        $stmt->execute(['username' => $artist]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $profile_picture = $row['profile_picture'];
            if ($profile_picture && file_exists('uploads/profiles/' . $profile_picture)) {
                return 'uploads/profiles/' . $profile_picture;
            }
        }
        
        return 'defaults/default-profile.jpg';
        
    } catch (PDOException $e) {
        error_log("Error fetching profile picture: " . $e->getMessage());
        return 'defaults/default-profile.jpg';
    }
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $filename);
    return strtolower($filename);
}
