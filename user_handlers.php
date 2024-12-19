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
        $query = "UPDATE users SET username = :username, email = :email, bio = :bio";
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':bio' => $data['bio'],  // Added bio
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

function updateSongDetails($user_id, $song_id, $details) {
    global $conn;
    
    // Prepare the update query with new fields
    $updateFields = [];
    $paramTypes = '';
    $params = [];
    
    // Existing fields
    if (isset($details['title'])) {
        $updateFields[] = 'title = ?';
        $paramTypes .= 's';
        $params[] = $details['title'];
    }
    if (isset($details['album'])) {
        $updateFields[] = 'album = ?';
        $paramTypes .= 's';
        $params[] = $details['album'];
    }
    if (isset($details['genre'])) {
        $updateFields[] = 'genre = ?';
        $paramTypes .= 's';
        $params[] = $details['genre'];
    }
    
    // New fields
    if (isset($details['visibility'])) {
        $updateFields[] = 'visibility = ?';
        $paramTypes .= 's';
        $params[] = $details['visibility'];
    }
    
    // Handle song cover upload
    if (isset($_FILES['song_cover']) && $_FILES['song_cover']['error'] == 0) {
        $uploadDir = 'uploads/song_covers/';
        $uploadFile = $uploadDir . uniqid() . '_' . basename($_FILES['song_cover']['name']);
        
        if (move_uploaded_file($_FILES['song_cover']['tmp_name'], $uploadFile)) {
            $updateFields[] = 'cover_image = ?';
            $paramTypes .= 's';
            $params[] = $uploadFile;
        }
    }
    
    // If no updates, return
    if (empty($updateFields)) {
        return ['success' => false, 'error' => 'No updates provided'];
    }
    
    // Add song_id and user_id to params
    $paramTypes .= 'is';
    $params[] = $song_id;
    $params[] = $user_id;
    
    // Construct the query
    $query = "UPDATE songs SET " . implode(', ', $updateFields) . 
             " WHERE song_id = ? AND user_id = ?";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param($paramTypes, ...$params);
    
    if ($stmt->execute()) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => $stmt->error];
    }
}
?>