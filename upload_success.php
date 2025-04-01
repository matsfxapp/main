<?php
require_once 'config/config.php';
require_once 'music_handlers.php';
require_once 'config/terminated_account_middleware.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'] ?? 'Unknown User';
$songId = $_GET['song_id'] ?? null;
$song = null;

if ($songId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs WHERE song_id = :song_id");
        $stmt->execute([':song_id' => $songId]);
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching song details: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <meta property="og:title" content="matSFX - Listen with Joy!" />
    <meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
    <meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>Upload Success - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
</head>
<body class="upload-page">
    <?php require_once 'includes/header.php'; ?>
    
    <div class="upload-container">
        <div class="upload-card success-card">
            <div class="upload-header">
                <img src="/app_logos/matsfx_logo.png" alt="matSFX Logo" class="upload-logo">
                <h1 class="upload-title">Upload Successful!</h1>
                <p class="upload-subtitle">Your song is now available on matSFX</p>
            </div>
            
            <div class="upload-body">
                <?php if ($song): ?>
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <div class="song-details">
                        <div class="song-preview">
                            <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="song-preview-image">
                            <div class="song-preview-play" onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)" data-song-title="<?php echo htmlspecialchars($song['title']); ?>" data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>" data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        
                        <div class="song-info">
                            <h3 class="song-title"><?php echo htmlspecialchars($song['title']); ?></h3>
                            <p class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></p>
                            <?php if (!empty($song['album'])): ?>
                            <p class="song-album">Album: <?php echo htmlspecialchars($song['album']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($song['genre'])): ?>
                            <p class="song-genre">Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="success-message">
                        <p>Your song has been successfully uploaded to matSFX and is now available for everyone to enjoy!</p>
                    </div>
                    
                    <div class="share-section">
                        <h3>Share your song</h3>
                        <?php 
                        require_once 'handlers/share_utils.php';
                        $shareCode = getShareCode($pdo, $song['song_id']);
                        $shareLink = "https://alpha.matsfx.com/song?share=" . $shareCode;
                        ?>
                        <div class="share-link-container">
                            <input type="text" id="shareLink" value="<?php echo htmlspecialchars($shareLink); ?>" readonly class="share-link-input">
                            <button onclick="copyShareLink()" class="share-copy-btn"><i class="fas fa-copy"></i> Copy</button>
                        </div>
                        
                        <div class="share-icons">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($shareLink); ?>&text=<?php echo urlencode('Check out my new song "' . $song['title'] . '" on matSFX!'); ?>" target="_blank" class="share-icon twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareLink); ?>" target="_blank" class="share-icon facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="whatsapp://send?text=<?php echo urlencode('Check out my new song "' . $song['title'] . '" on matSFX! ' . $shareLink); ?>" class="share-icon whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="upload-alert error">
                    <div class="upload-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="upload-alert-content">
                        <div class="upload-alert-title">Song Not Found</div>
                        <p class="upload-alert-message">We couldn't find details for the uploaded song.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="success-actions">
                    <a href="upload.php" class="upload-btn-secondary">Upload Another Song</a>
                    <a href="artist.php?name=<?php echo urlencode($currentUser); ?>" class="upload-btn">View My Profile</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyShareLink() {
            const shareLink = document.getElementById('shareLink');
            shareLink.select();
            document.execCommand('copy');
            
            // Show copy feedback
            const copyBtn = document.querySelector('.share-copy-btn');
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        }
    </script>
</body>
</html>