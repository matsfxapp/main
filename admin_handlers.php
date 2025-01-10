<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'getUsers':
        getUsers();
        break;
    case 'getUserDetails':
        getUserDetails();
        break;
    case 'updateUser':
        updateUser();
        break;
    case 'banUser':
        banUser();
        break;
    case 'unbanUser':
        unbanUser();
        break;
    case 'deleteUser':
        deleteUser();
        break;
    case 'getSongs':
        getSongs();
        break;
    case 'getSongDetails':
        getSongDetails();
        break;
    case 'updateSong':
        updateSong();
        break;
    case 'deleteSong':
        deleteSong();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getUsers() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT user_id, username, email, is_banned FROM users ";
    if (!empty($search)) {
        $query .= "WHERE username LIKE :search OR email LIKE :search";
        $stmt = $conn->prepare($query);
        $searchParam = "%{$search}%";
        $stmt->bindParam(':search', $searchParam);
    } else {
        $stmt = $conn->prepare($query);
    }
    
    try {
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode(['error' => "Error retrieving users: {$e->getMessage()}"]);
    }
}

function getUserDetails() {
    global $conn;
    
    $userId = $_GET['id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['error' => 'User ID is required']);
        return;
    }

    $query = "SELECT * FROM users WHERE user_id = :userId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId);
    
    try {
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['error' => 'User not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => "Error retrieving user: {$e->getMessage()}"]);
    }
}

function updateUser() {
    global $conn;
    
    $userId = $_POST['userId'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $is_helper = isset($_POST['is_helper']) ? 1 : 0;
    $is_donator = isset($_POST['is_donator']) ? 1 : 0;
    $is_developer = isset($_POST['is_developer']) ? 1 : 0;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$userId || !$username || !$email) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    $query = "UPDATE users SET 
        username = :username, 
        email = :email, 
        is_verified = :is_verified,
        is_helper = :is_helper,
        is_donator = :is_donator,
        is_developer = :is_developer,
        is_admin = :is_admin
        WHERE user_id = :userId";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':is_verified', $is_verified);
        $stmt->bindParam(':is_helper', $is_helper);
        $stmt->bindParam(':is_donator', $is_donator);
        $stmt->bindParam(':is_developer', $is_developer);
        $stmt->bindParam(':is_admin', $is_admin);
        $stmt->bindParam(':userId', $userId);
        
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error updating user: {$e->getMessage()}"]);
    }
}

function banUser() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    $query = "UPDATE users SET is_banned = 1 WHERE user_id = :userId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId);

    try {
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error banning user: {$e->getMessage()}"]);
    }
}

function unbanUser() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    $query = "UPDATE users SET is_banned = 0 WHERE user_id = :userId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId);

    try {
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error unbanning user: {$e->getMessage()}"]);
    }
}

function deleteUser() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    $query = "DELETE FROM users WHERE user_id = :userId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId);

    try {
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error deleting user: {$e->getMessage()}"]);
    }
}

function getSongs() {
    global $conn;
    
    $search = $_GET['search'] ?? '';

    $query = "SELECT song_id, title, artist, cover_art, upload_date FROM songs ";
    if (!empty($search)) {
        $query .= "WHERE title LIKE :search OR artist LIKE :search";
        $stmt = $conn->prepare($query);
        $searchParam = "%{$search}%";
        $stmt->bindParam(':search', $searchParam);
    } else {
        $stmt = $conn->prepare($query);
    }
    
    try {
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($songs);
    } catch (PDOException $e) {
        echo json_encode(['error' => "Error retrieving songs: {$e->getMessage()}"]);
    }
}

function getSongDetails() {
    global $conn;
    
    $songId = $_GET['id'] ?? null;
    
    if (!$songId) {
        echo json_encode(['error' => 'Song ID is required']);
        return;
    }

    $query = "SELECT * FROM songs WHERE song_id = :songId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':songId', $songId);
    
    try {
        $stmt->execute();
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($song) {
            echo json_encode($song);
        } else {
            echo json_encode(['error' => 'Song not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => "Error retrieving song: {$e->getMessage()}"]);
    }
}

function updateSong() {
    global $conn;
    
    $songId = $_POST['song_id'] ?? null;
    $title = $_POST['title'] ?? null;
    $artist = $_POST['artist'] ?? null;

    if (!$songId || !$title || !$artist) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    $image_url = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $upload_dir = 'uploads/covers/';
        $filename = uniqid() . '_' . basename($_FILES['cover_image']['name']);
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $image_url = $upload_path;
        }
    }

    $query = "UPDATE songs SET title = :title, artist = :artist";
    if ($image_url) {
        $query .= ", cover_art = :image_url";
    }
    $query .= " WHERE song_id = :songId";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':artist', $artist);
        $stmt->bindParam(':songId', $songId);
        if ($image_url) {
            $stmt->bindParam(':image_url', $image_url);
        }
        
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error updating song: {$e->getMessage()}"]);
    }
}

function deleteSong() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $songId = $data['song_id'] ?? null;

    if (!$songId) {
        echo json_encode(['success' => false, 'message' => 'Song ID is required']);
        return;
    }

    $query = "DELETE FROM songs WHERE song_id = :songId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':songId', $songId);

    try {
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error deleting song: {$e->getMessage()}"]);
    }
}