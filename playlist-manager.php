<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Session handling
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.');
    exit();
}

class PlaylistManager {
    private $conn;
    private $userId;
    private $allowedFileTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
    private $maxFileSize = 20971520; // 20MB in bytes
    
    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
    }
    
    public function createPlaylist($name, $description, $isPublic) {
        // Validate input
        if (empty($name)) {
            throw new Exception('Playlist name is required');
        }
        
        $sql = "INSERT INTO playlists (user_id, name, description, is_public, created_at) 
                VALUES (:user_id, :name, :description, :is_public, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'user_id' => $this->userId,
            'name' => $name,
            'description' => $description,
            'is_public' => $isPublic ? 1 : 0
        ]);
    }
    
    public function getUserPlaylists() {
        $sql = "SELECT p.*, COUNT(DISTINCT s.id) as song_count,
                       SUM(CASE WHEN s.is_local = 1 THEN 1 ELSE 0 END) as local_song_count
                FROM playlists p 
                LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id 
                LEFT JOIN songs s ON ps.song_id = s.id
                WHERE p.user_id = :user_id 
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['user_id' => $this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPublicPlaylists($limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username, COUNT(DISTINCT s.id) as song_count 
                FROM playlists p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id 
                LEFT JOIN songs s ON ps.song_id = s.id
                WHERE p.is_public = 1 AND p.has_local_songs = 0 
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addSong($playlistId, $songData, $isLocal = false) {
        // Verify playlist ownership
        $sql = "SELECT id FROM playlists WHERE id = :playlist_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'playlist_id' => $playlistId,
            'user_id' => $this->userId
        ]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Playlist not found or access denied');
        }
        
        $this->conn->beginTransaction();
        
        try {
            if ($isLocal) {
                // Validate file upload
                if (!isset($songData['tmp_name']) || !is_uploaded_file($songData['tmp_name'])) {
                    throw new Exception('No file uploaded');
                }
                
                if (!in_array($songData['type'], $this->allowedFileTypes)) {
                    throw new Exception('Invalid file type. Only MP3, WAV, and OGG files are allowed');
                }
                
                if ($songData['size'] > $this->maxFileSize) {
                    throw new Exception('File too large. Maximum size is 20MB');
                }
                
                // Create upload directory if it doesn't exist
                $uploadDir = 'uploads/' . $this->userId . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($songData['name']));
                $filePath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($songData['tmp_name'], $filePath)) {
                    throw new Exception('Failed to upload file');
                }
                
                // Get audio metadata if possible
                $title = pathinfo($songData['name'], PATHINFO_FILENAME);
                $artist = 'Unknown';
                
                if (function_exists('taglib_read')) {
                    $tags = taglib_read($filePath);
                    if ($tags) {
                        $title = $tags['title'] ?: $title;
                        $artist = $tags['artist'] ?: $artist;
                    }
                }
                
                // Insert song data
                $sql = "INSERT INTO songs (title, artist, file_path, is_local, uploaded_by) 
                        VALUES (:title, :artist, :file_path, 1, :user_id)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'title' => $title,
                    'artist' => $artist,
                    'file_path' => $filePath,
                    'user_id' => $this->userId
                ]);
                
                $songId = $this->conn->lastInsertId();
                
                // Update playlist status
                $sql = "UPDATE playlists SET has_local_songs = 1, is_public = 0 
                        WHERE id = :playlist_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(['playlist_id' => $playlistId]);
            } else {
                // For non-local songs, verify the song exists
                $sql = "SELECT id FROM songs WHERE id = :song_id AND is_local = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(['song_id' => $songData['song_id']]);
                
                if (!$stmt->fetch()) {
                    throw new Exception('Song not found');
                }
                
                $songId = $songData['song_id'];
            }
            
            // Check if song is already in playlist
            $sql = "SELECT 1 FROM playlist_songs 
                    WHERE playlist_id = :playlist_id AND song_id = :song_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'playlist_id' => $playlistId,
                'song_id' => $songId
            ]);
            
            if ($stmt->fetch()) {
                throw new Exception('Song is already in this playlist');
            }
            
            // Add song to playlist
            $sql = "INSERT INTO playlist_songs (playlist_id, song_id, added_at) 
                    VALUES (:playlist_id, :song_id, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'playlist_id' => $playlistId,
                'song_id' => $songId
            ]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            throw $e;
        }
    }
    
    public function togglePrivacy($playlistId) {
        // Verify playlist ownership and check for local songs
        $sql = "SELECT is_public, has_local_songs FROM playlists 
                WHERE id = :playlist_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'playlist_id' => $playlistId,
            'user_id' => $this->userId
        ]);
        $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$playlist) {
            throw new Exception('Playlist not found or access denied');
        }
        
        if ($playlist['has_local_songs']) {
            throw new Exception('Playlists with local songs cannot be made public');
        }
        
        $sql = "UPDATE playlists 
                SET is_public = :is_public 
                WHERE id = :playlist_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'is_public' => !$playlist['is_public'],
            'playlist_id' => $playlistId,
            'user_id' => $this->userId
        ]);
    }
    
    public function clonePlaylist($playlistId) {
        $this->conn->beginTransaction();
        
        try {
            // Get original playlist details
            $sql = "SELECT name, description FROM playlists 
                    WHERE id = :playlist_id AND is_public = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['playlist_id' => $playlistId]);
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$playlist) {
                throw new Exception('Playlist not found or is not public');
            }
            
            // Create new playlist
            $sql = "INSERT INTO playlists (user_id, name, description, is_public, created_at) 
                    VALUES (:user_id, :name, :description, 0, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $this->userId,
                'name' => $playlist['name'] . ' (Cloned)',
                'description' => $playlist['description']
            ]);
            
            $newPlaylistId = $this->conn->lastInsertId();
            
            // Copy non-local songs
            $sql = "INSERT INTO playlist_songs (playlist_id, song_id, added_at)
                    SELECT :new_playlist_id, ps.song_id, NOW()
                    FROM playlist_songs ps
                    JOIN songs s ON ps.song_id = s.id
                    WHERE ps.playlist_id = :playlist_id
                    AND s.is_local = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'new_playlist_id' => $newPlaylistId,
                'playlist_id' => $playlistId
            ]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    public function deleteSong($playlistId, $songId) {
        // Verify playlist ownership
        $sql = "SELECT 1 FROM playlists 
                WHERE id = :playlist_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'playlist_id' => $playlistId,
            'user_id' => $this->userId
        ]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Playlist not found or access denied');
        }
        
        $sql = "DELETE FROM playlist_songs 
                WHERE playlist_id = :playlist_id AND song_id = :song_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'playlist_id' => $playlistId,
            'song_id' => $songId
        ]);
    }
    
    public function deletePlaylist($playlistId) {
        $this->conn->beginTransaction();
        
        try {
            // Get playlist info and verify ownership
            $sql = "SELECT has_local_songs FROM playlists 
                    WHERE id = :playlist_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'playlist_id' => $playlistId,
                'user_id' => $this->userId
            ]);
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$playlist) {
                throw new Exception('Playlist not found or access denied');
            }
            
            // If playlist has local songs, delete the files
            if ($playlist['has_local_songs']) {
                $sql = "SELECT s.file_path 
                        FROM playlist_songs ps
                        JOIN songs s ON ps.song_id = s.id
                        WHERE ps.playlist_id = :playlist_id
                        AND s.is_local = 1
                        AND s.uploaded_by = :user_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'playlist_id' => $playlistId,
                    'user_id' => $this->userId
                ]);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (file_exists($row['file_path'])) {
                        unlink($row['file_path']);
                    }
                }
                
                // Delete local songs that are only in this playlist
                $sql = "DELETE s FROM songs s
                        LEFT JOIN playlist_songs ps2 ON s.id = ps2.song_id AND ps2.playlist_id != :playlist_id
                        JOIN playlist_songs ps ON s.id = ps.song_id AND ps.playlist_id = :playlist_id
                        WHERE s.is_local = 1 
                        AND s.uploaded_by = :user_id
                        AND ps2.id IS NULL";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'playlist_id' => $playlistId,
                    'user_id' => $this->userId
                ]);
            }
            
            // Delete playlist songs
            $sql = "DELETE FROM playlist_songs WHERE playlist_id = :playlist_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['playlist_id' => $playlistId]);
            
            // Delete playlist
            $sql = "DELETE FROM playlists 
                    WHERE id = :playlist_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'playlist_id' => $playlistId,
                'user_id' => $this->userId
            ]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}


// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlistManager = new PlaylistManager($conn, $_SESSION['user_id']);
    $response = ['success' => false];
    
    try {
        switch ($_POST['action']) {
            case 'create_playlist':
                $response['success'] = $playlistManager->createPlaylist(
                    $_POST['name'],
                    $_POST['description'] ?? '',
                    isset($_POST['is_public']) && !isset($_FILES['song'])
                );
                break;
                
            case 'add_song':
                if (isset($_FILES['song'])) {
                    $response['success'] = $playlistManager->addSong(
                        $_POST['playlist_id'],
                        $_FILES['song'],
                        true
                    );
                } else {
                    $response['success'] = $playlistManager->addSong(
                        $_POST['playlist_id'],
                        $_POST['song_data']
                    );
                }
                break;
                
            case 'toggle_privacy':
                $response['success'] = $playlistManager->togglePrivacy(
                    $_POST['playlist_id']
                );
                break;
                
            case 'clone_playlist':
                $response['success'] = $playlistManager->clonePlaylist(
                    $_POST['playlist_id']
                );
                break;
                
            case 'delete_song':
                $response['success'] = $playlistManager->deleteSong(
                    $_POST['playlist_id'],
                    $_POST['song_id']
                );
                break;
                
            case 'delete_playlist':
                $response['success'] = $playlistManager->deletePlaylist(
                    $_POST['playlist_id']
                );
                break;
                
            case 'search_playlists':
                $query = $_POST['query'] ?? '';
                $type = $_POST['type'] ?? 'all'; // 'all', 'user', or 'public'
                
                if ($type === 'user' || $type === 'all') {
                    $response['user_playlists'] = $playlistManager->searchUserPlaylists($query);
                }
                if ($type === 'public' || $type === 'all') {
                    $response['public_playlists'] = $playlistManager->searchPublicPlaylists($query);
                }
                $response['success'] = true;
                break;
                
            case 'get_playlist_details':
                $response['playlist'] = $playlistManager->getPlaylistDetails(
                    $_POST['playlist_id']
                );
                $response['success'] = true;
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        http_response_code(400);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle GET requests for initial page load
$playlistManager = new PlaylistManager($conn, $_SESSION['user_id']);

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

// Get playlists based on view type
$view = $_GET['view'] ?? 'all'; // 'all', 'user', or 'public'
$search = $_GET['search'] ?? '';

$data = [];

if ($view === 'user' || $view === 'all') {
    $data['userPlaylists'] = $playlistManager->getUserPlaylists();
}

if ($view === 'public' || $view === 'all') {
    $data['publicPlaylists'] = $playlistManager->getPublicPlaylists($limit, $offset);
    
    // Get total count for pagination
    $sql = "SELECT COUNT(*) FROM playlists WHERE is_public = 1 AND has_local_songs = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data['totalPublicPlaylists'] = $stmt->fetchColumn();
    $data['totalPages'] = ceil($data['totalPublicPlaylists'] / $limit);
    $data['currentPage'] = $page;
}

// If it's an AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// Otherwise, continue to load the page with the data available in the $data variable
?>
>