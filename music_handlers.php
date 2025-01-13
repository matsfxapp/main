<?php
require_once 'config/config.php';

try {
    $conn = new PDO(
        "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error");
}

function uploadSong($title, $artist, $album, $genre, $file, $cover_art) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection lost");
        return false;
    }
    
    $upload_dir = "uploads/songs/";
    $cover_dir = "uploads/covers/";
    $profile_dir = "uploads/profiles/";
    
    foreach ([$upload_dir, $cover_dir, $profile_dir] as $dir) {
        if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: $dir");
            return false;
        }
    }
    
    $song_filename = uniqid() . "_" . basename($file["name"]);
    $song_path = $upload_dir . $song_filename;
    $cover_path = 'defaults/default-cover.jpg';
    if (is_array($cover_art) && $cover_art["error"] === 0) {
        $cover_filename = uniqid() . "_" . basename($cover_art["name"]);
        $cover_path = $cover_dir . $cover_filename;
        if (!move_uploaded_file($cover_art["tmp_name"], $cover_path)) {
            error_log("Failed to upload cover art");
            return false;
        }
    }

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
                ':uploaded_by' => $_SESSION['user_id'] ?? 0
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
    
    error_log("Failed to upload song file");
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
        $search = "%$search%";
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
            if ($profile_picture && file_exists("uploads/profiles/$profile_picture")) {
                return "uploads/profiles/$profile_picture";
            }
        }
        
        return 'defaults/default-profile.jpg';
        
    } catch (PDOException $e) {
        error_log("Error fetching profile picture: " . $e->getMessage());
        return 'defaults/default-profile.jpg';
    }
}

function sanitizeFilename($filename) {
    if (empty($filename)) {
        return false;
    }
    $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $filename);
    $filename = strtolower($filename);
    return strlen($filename) > 0 ? $filename : false;
}