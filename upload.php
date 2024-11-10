<?php
require_once 'config.php';
require_once 'music_handlers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $artist = sanitizeInput($_POST['artist']);
    $album = sanitizeInput($_POST['album']);
    $genre = sanitizeInput($_POST['genre']);
    
    if (uploadSong($title, $artist, $album, $genre, $_FILES['song_file'], $_FILES['cover_art'])) {
        $success = "Song uploaded successfully!";
    } else {
        $error = "Error uploading song. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Music - masSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
    <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="upload.php">Upload</a>
            <a href="playlists.php">Playlists</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="upload-form">
            <h2>Upload Music</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="artist">Artist *</label>
                    <input type="text" id="artist" name="artist" required>
                </div>

                <div class="form-group">
                    <label for="album">Album</label>
                    <input type="text" id="album" name="album">
                </div>

                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre">
                </div>

                <div class="form-group">
                    <label for="song_file">Song File (MP3/WAV) *</label>
                    <input type="file" id="song_file" name="song_file" accept=".mp3,.wav" required>
                </div>

                <div class="form-group">
                    <label for="cover_art">Cover Art (Optional)</label>
                    <input type="file" id="cover_art" name="cover_art" accept="image/*">
                    <img id="cover_preview" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>

                <button type="submit" class="btn">Upload Song</button>
            </form>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>