<?php
require_once 'config/config.php';
require_once 'config/minio.php';

function uploadToMinIO($bucket, $file) {
    global $minioConfig;
    
    // Initialize result
    $result = [
        'success' => false,
        'message' => 'Unknown error',
        'path' => null,
        'url' => null
    ];
    
    if (!is_array($file) || $file['error'] !== 0) {
        $result['message'] = "Invalid file data or file has error: " . ($file['error'] ?? 'unknown');
        error_log($result['message']);
        return $result;
    }
    
    try {
        // Create a MinIO client
        $s3 = getMinioClient();
        
        // Get the proper bucket name from config
        $bucketName = isset($minioConfig['buckets'][$bucket]) ? 
                      $minioConfig['buckets'][$bucket] : 
                      $bucket;
        
        // Ensure the bucket exists
        ensureMinIOBuckets();
        
        // Generate a safe filename
        $file_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file["name"]));
        
        // Upload the file
        $s3Result = $s3->putObject([
            'Bucket' => $bucketName,
            'Key' => $file_key,
            'SourceFile' => $file["tmp_name"],
            'ContentType' => $file["type"],
            'ACL' => 'public-read'
        ]);
        
        // Store just the bucket/key path (not full URL)
        $bucket_path = $bucketName . '/' . $file_key;
        
        // Return success with file data
        $result['success'] = true;
        $result['message'] = 'File uploaded successfully';
        $result['path'] = $bucket_path;  // Store as "bucket/filename"
        $result['url'] = getMinIOObjectUrl($bucketName, $file_key);
        return $result;
    } catch (Exception $e) {
        $result['message'] = "Error uploading to MinIO: " . $e->getMessage();
        error_log($result['message']);
        return $result;
    }
}

function uploadSong($title, $artist, $album, $genre, $file, $cover_art = null, $existing_cover = null) {
    global $pdo, $minioConfig;
    
    error_log("Starting upload process for: $title by $artist");
    
    $result = [
        'success' => false,
        'message' => 'Unknown error occurred',
        'song_id' => null
    ];
    
    if (!isset($file) || !is_array($file) || $file["error"] !== 0) {
        $errorMsg = "File upload error code: " . ($file["error"] ?? 'No file');
        error_log($errorMsg);
        $result['message'] = $errorMsg;
        return $result;
    }
    
    if (!$pdo) {
        $errorMsg = "Database connection not established";
        error_log($errorMsg);
        $result['message'] = $errorMsg;
        return $result;
    }
    
    $songs_bucket = $minioConfig['buckets']['songs'] ?? 'music-songs';
    $covers_bucket = $minioConfig['buckets']['covers'] ?? 'music-covers';
    
    try {
        $s3 = getMinioClient();
        
        try {
            ensureMinIOBuckets();
        } catch (Exception $e) {
            $result['message'] = "Storage setup failed: " . $e->getMessage();
            return $result;
        }
        
        // Upload song file
        $song_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file["name"]));
        error_log("Uploading song file: {$file['name']} to bucket $songs_bucket");
        
        try {
            $result_song = $s3->putObject([
                'Bucket' => $songs_bucket,
                'Key' => $song_key,
                'SourceFile' => $file["tmp_name"],
                'ContentType' => $file["type"],
                'ACL' => 'public-read'
            ]);
            
            // Store as bucket/key format
            $song_filename = $songs_bucket . '/' . $song_key;
            error_log("Song uploaded successfully: $song_filename");
        } catch (Exception $e) {
            $errorMsg = "Failed to upload song file: " . $e->getMessage();
            error_log($errorMsg);
            $result['message'] = $errorMsg;
            return $result;
        }
        
        // Upload cover art
        $cover_filename = null;
        if (is_array($cover_art) && $cover_art["error"] === 0) {
            $cover_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($cover_art["name"]));
            error_log("Uploading cover art: {$cover_art['name']}");
            
            try {
                $result_cover = $s3->putObject([
                    'Bucket' => $covers_bucket,
                    'Key' => $cover_key,
                    'SourceFile' => $cover_art["tmp_name"],
                    'ContentType' => $cover_art["type"],
                    'ACL' => 'public-read'
                ]);
                
                $cover_filename = $covers_bucket . '/' . $cover_key;
                error_log("Cover uploaded successfully: $cover_filename");
            } catch (Exception $e) {
                error_log("Failed to upload cover art: " . $e->getMessage());
                $cover_filename = $existing_cover;
            }
        } elseif ($existing_cover) {
            $cover_filename = $existing_cover;
        }
        
        // Insert into database
        $query = "INSERT INTO songs (title, artist, album, genre, file_path, cover_art, uploaded_by, visibility) 
                  VALUES (:title, :artist, :album, :genre, :file_path, :cover_art, :uploaded_by, 'public')";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':title' => $title,
                ':artist' => $artist,
                ':album' => $album,
                ':genre' => $genre,
                ':file_path' => $song_filename,
                ':cover_art' => $cover_filename,
                ':uploaded_by' => $_SESSION['user_id']
            ]);
            
            $songId = $pdo->lastInsertId();
            error_log("Song inserted into database with ID: $songId");
            
            $result['success'] = true;
            $result['message'] = "Song uploaded successfully!";
            $result['song_id'] = $songId;
            return $result;
        } catch (PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage();
            error_log($errorMsg);
            $result['message'] = $errorMsg;
            return $result;
        }
    } catch (Exception $e) {
        $errorMsg = "Upload error: " . $e->getMessage();
        error_log($errorMsg);
        $result['message'] = $errorMsg;
        return $result;
    }
}

function getAllSongs() {
    global $pdo;
    
    if (!$pdo) {
        error_log("Database connection not established in getAllSongs()");
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs ORDER BY upload_date DESC");
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert stored paths to URLs
        foreach ($songs as &$song) {
            if (!empty($song['file_path'])) {
                $parts = explode('/', $song['file_path'], 2);
                if (count($parts) == 2) {
                    $song['song_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                } else {
                    $song['song_url'] = getMinIOObjectUrl('music-songs', $song['file_path']);
                }
            }
            
            if (!empty($song['cover_art'])) {
                $parts = explode('/', $song['cover_art'], 2);
                if (count($parts) == 2) {
                    $song['cover_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                } else {
                    $song['cover_url'] = getMinIOObjectUrl('music-covers', $song['cover_art']);
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Database error in getAllSongs(): " . $e->getMessage());
        return [];
    }
}

function incrementPlayCount($songId) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE songs SET play_count = play_count + 1 WHERE song_id = :song_id");
        $stmt->bindParam(':song_id', $songId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error in incrementPlayCount(): " . $e->getMessage());
        return false;
    }
}

function getPopularSongs($limit = 10) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs ORDER BY play_count DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($songs as &$song) {
            if (!empty($song['file_path'])) {
                $parts = explode('/', $song['file_path'], 2);
                if (count($parts) == 2) {
                    $song['song_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
            if (!empty($song['cover_art'])) {
                $parts = explode('/', $song['cover_art'], 2);
                if (count($parts) == 2) {
                    $song['cover_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Error in getPopularSongs(): " . $e->getMessage());
        return [];
    }
}

function getMostPlayedSongs($limit = 10) {
    return getPopularSongs($limit);
}

function getRecentlyPlayedSongs($user_id, $limit = 10) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, rp.played_at 
            FROM recently_played rp
            JOIN songs s ON rp.song_id = s.song_id
            WHERE rp.user_id = :user_id
            ORDER BY rp.played_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($songs as &$song) {
            if (!empty($song['file_path'])) {
                $parts = explode('/', $song['file_path'], 2);
                if (count($parts) == 2) {
                    $song['song_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
            if (!empty($song['cover_art'])) {
                $parts = explode('/', $song['cover_art'], 2);
                if (count($parts) == 2) {
                    $song['cover_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Error in getRecentlyPlayedSongs(): " . $e->getMessage());
        return [];
    }
}

function getSongsByGenre($genre, $limit = 10) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs WHERE genre = :genre ORDER BY upload_date DESC LIMIT :limit");
        $stmt->bindParam(':genre', $genre, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($songs as &$song) {
            if (!empty($song['file_path'])) {
                $parts = explode('/', $song['file_path'], 2);
                if (count($parts) == 2) {
                    $song['song_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
            if (!empty($song['cover_art'])) {
                $parts = explode('/', $song['cover_art'], 2);
                if (count($parts) == 2) {
                    $song['cover_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Error in getSongsByGenre(): " . $e->getMessage());
        return [];
    }
}

function getNewReleases($limit = 10) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs ORDER BY upload_date DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($songs as &$song) {
            if (!empty($song['file_path'])) {
                $parts = explode('/', $song['file_path'], 2);
                if (count($parts) == 2) {
                    $song['song_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
            if (!empty($song['cover_art'])) {
                $parts = explode('/', $song['cover_art'], 2);
                if (count($parts) == 2) {
                    $song['cover_url'] = getMinIOObjectUrl($parts[0], $parts[1]);
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Error in getNewReleases(): " . $e->getMessage());
        return [];
    }
}