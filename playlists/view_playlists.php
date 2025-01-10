<?php
session_start();
require_once '../config.php';
require_once '../playlist_func.php';

// Get playlist ID from URL
$playlistId = basename($_SERVER['REQUEST_URI']);

// Verify playlist exists and get its details
$playlist = getPlaylist($playlistId);
if (!$playlist) {
    header('HTTP/1.0 404 Not Found');
    echo "Playlist not found";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_song'])) {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $playlist['user_id']) {
            addSongToPlaylist($playlistId, $_POST['song_id']);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } elseif (isset($_POST['update_cover']) && isset($_FILES['cover_image'])) {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $playlist['user_id']) {
            $coverImage = handlePlaylistCover($_FILES['cover_image']);
            if ($coverImage) {
                updatePlaylistCover($playlistId, $coverImage);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}

$playlistSongs = getPlaylistSongs($playlistId);
$allSongs = getAllSongs();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($playlist['playlist_name']); ?> - Playlist</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($playlist['playlist_name']); ?></h1>
    
    <?php if ($playlist['cover_image']): ?>
        <img src="/<?php echo htmlspecialchars($playlist['cover_image']); ?>" 
             alt="Playlist cover" 
             width="300" 
             height="300">
    <?php endif; ?>

    <p>Created by: <?php echo htmlspecialchars($playlist['username']); ?></p>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $playlist['user_id']): ?>
        <div class="update-cover">
            <h3>Update Cover Image</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="cover_image" accept="image/jpeg,image/png" required>
                <input type="submit" name="update_cover" value="Update Cover">
            </form>
        </div>
    <?php endif; ?>

    <div class="playlist-songs">
        <h2>Songs in this Playlist</h2>
        <?php while ($song = $playlistSongs->fetch_assoc()): ?>
            <div class="song">
                <?php echo htmlspecialchars($song['Name']); ?>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $playlist['user_id']): ?>
        <div class="add-song">
            <h3>Add Song</h3>
            <form method="POST" class="add-song-form">
                <select name="song_id">
                    <?php while ($song = $allSongs->fetch_assoc()): ?>
                        <option value="<?php echo $song['song_id']; ?>">
                            <?php echo htmlspecialchars($song['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="submit" name="add_song" value="Add Song">
            </form>
        </div>
    <?php endif; ?>

    <p><a href="/playlists">Back to Playlists</a></p>
</body>
</html>