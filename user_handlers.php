<?php
require_once 'config.php';

function getUserData($user_id) {
    global $conn;
    
    $query = "SELECT user_id, username, email, profile_picture FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserSongs($user_id) {
    global $conn;
    
    $query = "SELECT * FROM songs WHERE uploaded_by = :user_id ORDER BY upload_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateProfile($user_id, $data, $profile_picture = null) {
    global $conn;
    
    try {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        // Check if username or email already exists
        $check_query = "SELECT user_id FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }
        
        // Handle profile picture upload
        $profile_picture_path = null;
        if ($profile_picture && $profile_picture['error'] == 0) {
            $upload_dir = "uploads/profiles/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = uniqid() . "_" . basename($profile_picture["name"]);
            $profile_picture_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($profile_picture["tmp_name"], $profile_picture_path)) {
                return ['success' => false, 'error' => 'Failed to upload profile picture'];
            }
        }
        
        // Update user data
        $query = "UPDATE users SET username = :username, email = :email";
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':user_id' => $user_id
        ];
        
        if ($profile_picture_path) {
            $query .= ", profile_picture = :profile_picture";
            $params[':profile_picture'] = $profile_picture_path;
        }
        
        $query .= " WHERE user_id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function updatePassword($user_id, $current_password, $new_password, $confirm_password) {
    global $conn;
    
    try {
        // Verify current password
        $query = "SELECT password FROM users WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
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
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([
            ':password' => $hashed_password,
            ':user_id' => $user_id
        ]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteSong($user_id, $song_id) {
    global $conn;
    
    try {
        // Get song info first
        $query = "SELECT file_path, cover_art FROM songs WHERE song_id = :song_id AND uploaded_by = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$song) {
            return ['success' => false, 'error' => 'Song not found or unauthorized'];
        }
        
        // Delete files
        if (file_exists($song['file_path'])) {
            unlink($song['file_path']);
        }
        if ($song['cover_art'] && file_exists($song['cover_art'])) {
            unlink($song['cover_art']);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM songs WHERE song_id = :song_id AND uploaded_by = :user_id";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function updateSongDetails($user_id, $song_id, $data) {
    global $conn;
    
    try {
        // Verify song ownership
        $check_query = "SELECT song_id FROM songs WHERE song_id = :song_id AND uploaded_by = :user_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Song not found or unauthorized'];
        }
        
        // Update song details
        $update_query = "UPDATE songs SET 
            title = :title,
            artist = :artist,
            album = :album,
            genre = :genre
            WHERE song_id = :song_id AND uploaded_by = :user_id";
            
        $update_stmt = $conn->prepare($update_query);
        $result = $update_stmt->execute([
            ':title' => $data['title'],
            ':artist' => $data['artist'],
            ':album' => $data['album'],
            ':genre' => $data['genre'],
            ':song_id' => $song_id,
            ':user_id' => $user_id
        ]);
        
        if ($result) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to update song details'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}
?>
