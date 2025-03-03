<?php
require_once 'config/config.php';
require_once 'music_handlers.php';

function getUserData($user_id) {
    global $pdo;
    
    if (!$pdo) {
        error_log("Database connection not established in getUserData()");
        return false;
    }
    
    try {
        $query = "SELECT user_id, username, email, profile_picture, bio, is_admin FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Check if profile picture is empty or doesn't contain a URL
            if (empty($userData['profile_picture']) || strpos($userData['profile_picture'], 'http') !== 0) {
                $userData['profile_picture'] = '/defaults/default-profile.jpg';
            }
        }
        
        return $userData;
    } catch (PDOException $e) {
        error_log("Database query failed in getUserData(): " . $e->getMessage());
        return false;
    }
}

function getUserSongs($user_id) {
    global $pdo, $minioConfig;
    
    if (!$pdo) {
        error_log("Database connection not established in getUserSongs()");
        return false;
    }
    
    try {
        $query = "SELECT * FROM songs WHERE uploaded_by = :user_id ORDER BY upload_date DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process URLs for song files and cover art
        foreach ($songs as &$song) {
            // Handle song URL
            if (empty($song['song_url']) && !empty($song['file_path'])) {
                $song['song_url'] = getMinIOObjectUrl('songs', $song['file_path']);
                
                // Update the database with the URL
                try {
                    $updateStmt = $pdo->prepare("UPDATE songs SET song_url = :song_url WHERE song_id = :song_id");
                    $updateStmt->execute([
                        ':song_url' => $song['song_url'],
                        ':song_id' => $song['song_id']
                    ]);
                } catch (PDOException $e) {
                    error_log("Failed to update song_url in database: " . $e->getMessage());
                }
            }
            
            // Handle cover URL
            if (empty($song['cover_url']) && !empty($song['cover_art'])) {
                $song['cover_url'] = getMinIOObjectUrl('covers', $song['cover_art']);
                
                // Update the database with the URL
                try {
                    $updateStmt = $pdo->prepare("UPDATE songs SET cover_url = :cover_url WHERE song_id = :song_id");
                    $updateStmt->execute([
                        ':cover_url' => $song['cover_url'],
                        ':song_id' => $song['song_id']
                    ]);
                } catch (PDOException $e) {
                    error_log("Failed to update cover_url in database: " . $e->getMessage());
                }
            }
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Database query failed in getUserSongs(): " . $e->getMessage());
        return false;
    }
}

function updateProfile($user_id, $data, $profile_picture = null) {
    global $pdo;
    
    if (!$pdo) {
        error_log("Database connection not established in updateProfile()");
        return ['success' => false, 'error' => 'Database connection not established'];
    }
    
    try {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        // Check if username or email already exists
        $check_query = "SELECT user_id FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }
        
        // Handle profile picture upload to MinIO
        $profile_picture_url = null;
        if ($profile_picture && $profile_picture['error'] == 0) {
            ensureMinIOBuckets(); // Ensure buckets exist
            $profileUpload = uploadToMinIO('user-profiles', $profile_picture);
            
            if (!$profileUpload['success']) {
                return ['success' => false, 'error' => 'Failed to upload profile picture to MinIO'];
            }
            
            // Store the full URL directly
            $profile_picture_url = $profileUpload['path'];
        }
        
        // Update user data
        $query = "UPDATE users SET username = :username, email = :email, bio = :bio";
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':bio' => $data['bio'],
            ':user_id' => $user_id
        ];
        
        if ($profile_picture_url) {
            // Store the full URL directly in profile_picture
            $query .= ", profile_picture = :profile_picture";
            $params[':profile_picture'] = $profile_picture_url;
        }
        
        $query .= " WHERE user_id = :user_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Database query failed in updateProfile(): " . $e->getMessage());
        return ['success' => false, 'error' => 'Database query failed'];
    }
}

function updatePassword($user_id, $current_password, $new_password, $confirm_password) {
    global $pdo;
    
    try {
        // Verify current password
        $query = "SELECT password FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters long'];
        }
        
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'error' => 'New passwords do not match'];
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([
            ':password' => $hashed_password,
            ':user_id' => $user_id
        ]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function updateBio($user_id, $bio) {
    global $pdo; // Changed from $conn to $pdo for consistency
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET bio = :bio WHERE user_id = :user_id");
        $stmt->execute([
            ':bio' => $bio,
            ':user_id' => $user_id
        ]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteSong($user_id, $song_id) {
    global $pdo; // Changed from $conn to $pdo for consistency
    
    try {
        // Get song info first
        $query = "SELECT file_path, cover_art FROM songs WHERE song_id = :song_id AND uploaded_by = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$song) {
            return ['success' => false, 'error' => 'Song not found or unauthorized'];
        }
        
        // Delete files from MinIO
        $s3 = getMinioClient();
        if ($s3) {
            try {
                // Delete song file
                $s3->deleteObject([
                    'Bucket' => 'songs',
                    'Key' => $song['file_path']
                ]);
                
                // Delete cover art if it exists
                if ($song['cover_art'] && $song['cover_art'] !== 'default-cover.jpg') {
                    $s3->deleteObject([
                        'Bucket' => 'covers',
                        'Key' => $song['cover_art']
                    ]);
                }
            } catch (Exception $e) {
                error_log("Error deleting files from MinIO: " . $e->getMessage());
                // Continue with database deletion even if file deletion fails
            }
        }
        
        // Delete from database
        $delete_query = "DELETE FROM songs WHERE song_id = :song_id AND uploaded_by = :user_id";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function updateSongDetails($user_id, $song_id, $details) {
    global $pdo;

    $updateFields = [];
    $params = [
        ':song_id' => $song_id,
        ':user_id' => $user_id
    ];
    
    if (isset($details['title'])) {
        $updateFields[] = 'title = :title';
        $params[':title'] = $details['title'];
    }
    if (isset($details['album'])) {
        $updateFields[] = 'album = :album';
        $params[':album'] = $details['album'];
    }
    if (isset($details['genre'])) {
        $updateFields[] = 'genre = :genre';
        $params[':genre'] = $details['genre'];
    }
    if (isset($details['visibility'])) {
        $updateFields[] = 'visibility = :visibility';
        $params[':visibility'] = $details['visibility'];
    }
    
    if (isset($_FILES['song_cover']) && $_FILES['song_cover']['error'] == 0) {
        ensureMinIOBuckets();
        $coverUpload = uploadToMinIO('music-covers', $_FILES['song_cover']);
        
        if ($coverUpload && $coverUpload['success']) {
            $updateFields[] = 'cover_art = :cover_art';
            $params[':cover_art'] = $coverUpload['path'];
            
            $updateFields[] = 'cover_art = :cover_art';
            $params[':cover_art'] = getMinIOObjectUrl('music-covers', $coverUpload['path']);
        }
    }

    if (empty($updateFields)) {
        return ['success' => false, 'error' => 'No updates provided'];
    }

    $query = "UPDATE songs SET " . implode(', ', $updateFields) . 
             " WHERE song_id = :song_id AND uploaded_by = :user_id";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
