<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function isAdmin($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['is_admin'] == 1;
}


function getPlaylists($userId) {
    global $conn;
    $sql = "SELECT * FROM playlists WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getPublicPlaylists() {
    global $conn;
    $sql = "SELECT * FROM playlists WHERE is_public = 1";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

function renderPlaylists($playlists) {
    foreach ($playlists as $playlist) {
        echo "<div class='playlist' id='playlist-{$playlist['id']}'>";
        echo "<h3>{$playlist['name']}</h3>";
        echo "<div class='playlist-settings'>
                <button onclick='editPlaylist({$playlist['id']})'>Edit</button>
                <button onclick='deletePlaylist({$playlist['id']})'>Delete</button>
                <button onclick='togglePrivacy({$playlist['id']})'>" . ($playlist['is_public'] ? "Make Private" : "Make Public") . "</button>
              </div>";
        echo "</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new playlist
    if (isset($_POST['create_playlist'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $cover_image = $_POST['cover_image'];
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $user_id = $_SESSION['user_id'];

        $sql = "INSERT INTO playlists (user_id, name, description, cover_image, is_public, created_at) VALUES (:user_id, :name, :description, :cover_image, :is_public, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':cover_image', $cover_image, PDO::PARAM_STR);
        $stmt->bindParam(':is_public', $is_public, PDO::PARAM_BOOL);
        $stmt->execute();
    }

    // Edit playlist
    if (isset($_POST['edit_playlist'])) {
        $playlist_id = $_POST['playlist_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $cover_image = $_POST['cover_image'];
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        $sql = "UPDATE playlists SET name = :name, description = :description, cover_image = :cover_image, is_public = :is_public WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':cover_image', $cover_image, PDO::PARAM_STR);
        $stmt->bindParam(':is_public', $is_public, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $playlist_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Delete playlist
    if (isset($_POST['delete_playlist'])) {
        $playlist_id = $_POST['playlist_id'];

        $sql = "DELETE FROM playlists WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $playlist_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Copy playlist
    if (isset($_POST['copy_playlist'])) {
        $playlist_id = $_POST['playlist_id'];
        $user_id = $_SESSION['user_id'];

        // Copy playlist
        $sql = "INSERT INTO playlists (user_id, name, description, cover_image, is_public, created_at)
                SELECT :user_id, name, description, cover_image, 0, NOW() FROM playlists WHERE id = :playlist_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':playlist_id', $playlist_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

$userPlaylists = getPlaylists($_SESSION['user_id']);
$publicPlaylists = getPublicPlaylists();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matSFX</title>
    <link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="playlists.css">
</head>
<body>
    <header class="header">
        <h1>matSFX</h1>
        <input type="text" placeholder="Search Artist, Playlists and Songs" id="searchBar">
    </header>
    <nav class="navbar">
        <ul>
            <li>Home</li>
            <li>Upload</li>
            <li>Playlists</li>
        </ul>
    </nav>
    <main class="main-content">
        <section class="playlists">
            <h2>Your Playlists</h2>
            <?php renderPlaylists($userPlaylists); ?>
            <div class="playlist" id="new-playlist">
                <h3>Make a New Playlist</h3>
            </div>
        </section>
        <aside class="details">
            <h2>Public Playlists</h2>
            <?php renderPlaylists($publicPlaylists); ?>
        </aside>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const playlists = document.querySelectorAll('.playlist');

            playlists.forEach(playlist => {
                playlist.addEventListener('click', () => {
                    alert(`You clicked on ${playlist.querySelector('h3').innerText}!`);
                });

                playlist.addEventListener('mouseover', () => {
                    if (window.innerWidth > 768) {
                        playlist.querySelector('.playlist-settings').style.display = 'block';
                    }
                });

                playlist.addEventListener('mouseout', () => {
                    if (window.innerWidth > 768) {
                        playlist.querySelector('.playlist-settings').style.display = 'none';
                    }
                });

                playlist.addEventListener('contextmenu', (event) => {
                    event.preventDefault();
                    if (window.innerWidth <= 768) {
                        playlist.classList.add('long-press');
                        setTimeout(() => {
                            playlist.classList.remove('long-press');
                        }, 1000);
                    }
                });
            });
        });
    </script>
</body>
</html>
