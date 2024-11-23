<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$artist = isset($_GET['name']) ? trim(urldecode($_GET['name'])) : null;

if (!$artist) {
    header("Location: index.php");
    exit();
}

function getArtistProfilePicture($artistName) {
    global $conn;
    try {
        $query = "SELECT profile_picture FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['profile_picture']) {
            $filename = basename($result['profile_picture']);
            return 'uploads/profiles/' . $filename;
        }
        return 'defaults/default-profile.jpg';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'defaults/default-profile.jpg';
    }
}

function getArtistSongs($artistName) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM songs WHERE artist = :artist ORDER BY upload_date DESC");
        $stmt->bindValue(':artist', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function checkArtistExists($artistName) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9-_.]/', '', $filename);
}

$artistData = checkArtistExists($artist);
$songs = $artistData ? getArtistSongs($artist) : [];
$profilePicture = getArtistProfilePicture($artist);

if (!$artistData) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Not Found - matSFX</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <nav class="navbar">
            <div class="logo">matSFX - Alpha 0.1</div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="upload.php">Upload</a>
                <a href="user_settings.php">Settings</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
        <div class="error-container">
            <h1>User Not Found</h1>
            <p>The requested user does not exist.</p>
            <a href="index.php" class="back-button">Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artist); ?> - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="upload.php">Upload</a>
            <a href="user_settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="artist-profile">
        <div class="profile-header">
            <div class="profile-content">
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Artist" class="profile-image">
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($artist); ?></h1>
                    <div class="profile-stats">
                        <span><?php echo count($songs); ?> Songs</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="artist-songs">
            <div class="songs-header">
                <h2 class="songs-title">Songs</h2>
            </div>
            <div class="music-grid">
                <?php if (empty($songs)): ?>
                    <p>No songs uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($songs as $song): ?>
                        <div class="song-card">
                            <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                            <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                            <div class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                            <div class="song-controls">
                                <button onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)" class="play-btn">
                                    Play
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
