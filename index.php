<?php
require_once 'config.php';
require_once 'music_handlers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$songs = getAllSongs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matSFX - Music for everyone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
    <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="user_settings.php">Settings</a>
            <a href="upload.php">Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="music-grid">
            <?php foreach ($songs as $song): ?>
                <div class="song-card">
                    <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                    <div class="song-controls">
                        <button onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)" class="play-btn">
                            Play
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="player">
        <audio id="audio-player" controls>
            Your browser does not support the audio element.
        </audio>
    </div>

    <script>
        const audioPlayer = document.getElementById('audio-player');
        let currentButton = null;

        function playSong(songPath, button) {
            if (currentButton) {
                currentButton.textContent = 'Play';
            }

            if (audioPlayer.src.endsWith(songPath) && !audioPlayer.paused) {
                audioPlayer.pause();
                button.textContent = 'Play';
            } else {
                audioPlayer.src = songPath;
                audioPlayer.play().then(() => {
                    button.textContent = 'Pause';
                    currentButton = button;
                }).catch(error => {
                    console.error('Error playing song:', error);
                    alert('Error playing song. Please try again.');
                });
            }
        }

        audioPlayer.addEventListener('ended', () => {
            if (currentButton) {
                currentButton.textContent = 'Play';
            }
        });
    </script>
</body>
</html>