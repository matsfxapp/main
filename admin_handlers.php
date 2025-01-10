<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Set proper JSON headers
header('Content-Type: application/json');

// Determine the action
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
    $search = $conn->real_escape_string($search);

    $query = "SELECT user_id, username, email, is_banned FROM users ";
    if (!empty($search)) {
        $query .= "WHERE username LIKE '%$search%' OR email LIKE '%$search%' ";
    }

    $result = $conn->query($query);
    
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($users);
    } else {
        echo json_encode(['error' => 'Error retrieving users: ' . $conn->error]);
    }
}

function getUserDetails() {
    global $conn;
    
    $userId = $_GET['id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['error' => 'User ID is required']);
        return;
    }

    $userId = $conn->real_escape_string($userId);
    $query = "SELECT * FROM users WHERE user_id = '$userId'";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
}

function updateUser() {
    global $conn;
    
    $userId = $_POST['userId'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    
    // User badges
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $is_helper = isset($_POST['is_helper']) ? 1 : 0;
    $is_donator = isset($_POST['is_donator']) ? 1 : 0;
    $is_developer = isset($_POST['is_developer']) ? 1 : 0;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;


    if (!$userId || !$username || !$email) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    $userId = $conn->real_escape_string($userId);
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);

    $query = "UPDATE users SET 
        username = '$username', 
        email = '$email', 
        is_verified = $is_verified,
        is_helper = $is_helper,
        is_donator = $is_donator,
        is_developer = $is_developer,
        is_admin = $is_admin
        WHERE user_id = '$userId'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $conn->error]);
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

    $userId = $conn->real_escape_string($userId);
    $query = "UPDATE users SET is_banned = 1 WHERE id = '$userId'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error banning user: ' . $conn->error]);
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

    $userId = $conn->real_escape_string($userId);
    $query = "UPDATE users SET is_banned = 0 WHERE id = '$userId'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error unbanning user: ' . $conn->error]);
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

    $userId = $conn->real_escape_string($userId);
    $query = "DELETE FROM users WHERE id = '$user_id'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $conn->error]);
    }
}

function getSongs() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    $search = $conn->real_escape_string($search);

    $query = "SELECT song_id, title, artist, cover_art, upload_date FROM songs ";
    if (!empty($search)) {
        $query .= "WHERE title LIKE '%$search%' OR artist LIKE '%$search%' ";
    }

    $result = $conn->query($query);
    
    if ($result) {
        $songs = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($songs);
    } else {
        echo json_encode(['error' => 'Error retrieving songs: ' . $conn->error]);
    }
}

function getSongDetails() {
    global $conn;
    
    $songId = $_GET['id'] ?? null;
    
    if (!$songId) {
        echo json_encode(['error' => 'Song ID is required']);
        return;
    }

    $songId = $conn->real_escape_string($songId);
    $query = "SELECT * FROM songs WHERE id = '$song_id'";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $song = $result->fetch_assoc();
        echo json_encode($song);
    } else {
        echo json_encode(['error' => 'Song not found']);
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

    $songId = $conn->real_escape_string($songId);
    $title = $conn->real_escape_string($title);
    $artist = $conn->real_escape_string($artist);

    // Handle file upload
    $image_url = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $upload_dir = 'uploads/covers/';
        $filename = uniqid() . '_' . basename($_FILES['cover_image']['name']);
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $image_url = $upload_path;
        }
    }

    $query = "UPDATE songs SET 
        title = '$title', 
        artist = '$artist'";
    
    if ($image_url) {
        $query .= ", image_url = '$image_url'";
    }
    
    $query .= " WHERE id = '$songId'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating song: ' . $conn->error]);
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

    $songId = $conn->real_escape_string($songId);
    $query = "DELETE FROM songs WHERE id = '$songId'";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting song: ' . $conn->error]);
    }
}

// Close the database connection
$conn->close();