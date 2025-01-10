<?php
session_start();
require_once 'config.php';
require_once 'playlist_func.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_playlist'])) {
        $coverImage = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverImage = handlePlaylistCover($_FILES['cover_image']);
        }
        $playlistId = createPlaylist($userId, $_POST['playlist_name'], $coverImage);
        header("Location: playlists/$playlistId");
        exit();
    }
}

$playlists = getPlaylists($userId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Playlists</title>
</head>
<body>
    <h2>Create New Playlist</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="playlist_name" required placeholder="Enter playlist name">
        <input type="file" name="cover_image" accept="image/jpeg,image/png">
        <input type="submit" name="create_playlist" value="Create Playlist">
    </form>

    <h2>Your Playlists</h2>
    <div id="playlists">
        <?php while ($playlist = $playlists->fetch_assoc()): ?>
            <div class="playlist">
                <?php if ($playlist['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($playlist['cover_image']); ?>" 
                         alt="Playlist cover" 
                         width="150" 
                         height="150">
                <?php endif; ?>
                <h3>
                    <a href="playlists/<?php echo $playlist['playlist_id']; ?>">
                        <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                    </a>
                </h3>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>