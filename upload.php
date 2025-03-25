<?php
require_once 'config/config.php';
require_once 'vendor/autoload.php';
require_once 'music_handlers.php';

try {
    ensureMinIOBuckets();
} catch (Exception $e) {
    $configError = "Storage initialization error: " . $e->getMessage();
}

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? null;

if (!isset($minioConfig) || 
    empty($minioConfig['endpoint']) || 
    empty($minioConfig['credentials']['key']) || 
    empty($minioConfig['credentials']['secret'])) {
    
    $configError = "MinIO storage is not properly configured. Please contact the administrator.";
}

// Fetch existing albums for this user to populate dropdown
$userAlbums = [];
if (isset($currentUserId)) {
    try {
        $albumStmt = $pdo->prepare("SELECT DISTINCT album FROM songs WHERE uploaded_by = :user_id AND album IS NOT NULL AND album != '' ORDER BY album ASC");
        $albumStmt->execute([':user_id' => $currentUserId]);
        $userAlbums = $albumStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Failed to fetch user albums: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($configError)) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $artist = sanitizeInput($currentUser);
    $album = sanitizeInput($_POST['album'] ?? '');
    $genre = sanitizeInput($_POST['genre'] ?? '');
    $useExistingAlbum = isset($_POST['use_existing_album']) && $_POST['use_existing_album'] == 1;
    
    if ($useExistingAlbum && isset($_POST['existing_album'])) {
        $album = sanitizeInput($_POST['existing_album']);
    }

    $fileValid = true;

    $validSongTypes = $minioConfig['allowed_types']['songs'] ?? ['audio/mpeg', 'audio/wav', 'audio/x-wav'];
    $validImageTypes = $minioConfig['allowed_types']['images'] ?? ['image/jpeg', 'image/png', 'image/webp'];
    
    if (!isset($_FILES['song_file']) || $_FILES['song_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a valid song file. Error code: " . ($_FILES['song_file']['error'] ?? 'none');
        $fileValid = false;
    } else {
        $songFileType = $_FILES['song_file']['type'];
        if (!in_array($songFileType, $validSongTypes)) {
            $error = "Invalid song file type ($songFileType). Please upload MP3 or WAV files only.";
            $fileValid = false;
        }

        $maxSongSize = $minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024);
        if ($_FILES['song_file']['size'] > $maxSongSize) {
            $error = "Song file is too large. Maximum size is " . ($maxSongSize / (1024 * 1024)) . "MB.";
            $fileValid = false;
        }
    }

    if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_OK) {
        $coverFileType = $_FILES['cover_art']['type'];
        if (!in_array($coverFileType, $validImageTypes)) {
            $error = "Invalid cover art file type ($coverFileType). Please upload JPG, PNG or WEBP files only.";
            $fileValid = false;
        }
        
        $maxCoverSize = $minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024);
        if ($_FILES['cover_art']['size'] > $maxCoverSize) {
            $error = "Cover art file is too large. Maximum size is " . ($maxCoverSize / (1024 * 1024)) . "MB.";
            $fileValid = false;
        }
    }

    // Get album cover art if using existing album and no cover provided
    $useExistingCover = false;
    if ($useExistingAlbum && $album && (!isset($_FILES['cover_art']) || $_FILES['cover_art']['error'] !== UPLOAD_ERR_OK)) {
        try {
            $coverStmt = $pdo->prepare("SELECT cover_art FROM songs WHERE album = :album AND uploaded_by = :user_id AND cover_art IS NOT NULL LIMIT 1");
            $coverStmt->execute([':album' => $album, ':user_id' => $currentUserId]);
            $existingCover = $coverStmt->fetchColumn();
            
            if ($existingCover) {
                $useExistingCover = true;
            }
        } catch (PDOException $e) {
            error_log("Failed to fetch existing album cover: " . $e->getMessage());
        }
    }

    if ($fileValid) {
        try {
            $coverArt = null;
            if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_OK) {
                $coverArt = $_FILES['cover_art'];
            }

            $uploadResult = uploadSong($title, $artist, $album, $genre, $_FILES['song_file'], $coverArt, $useExistingCover ? $existingCover : null);
            
            if ($uploadResult['success']) {
                $success = "Song uploaded successfully!";
            } else {
                $error = "Error uploading song: " . $uploadResult['message'];
            }
        } catch (Exception $e) {
            $error = "Upload failed: " . $e->getMessage();
            error_log("Upload error: " . $e->getMessage());
        }
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
    <title>Upload Music - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
</head>
<body class="upload-page">
    <?php require_once 'includes/header.php'; ?>
    
    <div class="upload-container">
        <div class="upload-card">
            <div class="upload-header">
                <img src="/app_logos/matsfx_logo.png" alt="matSFX Logo" class="upload-logo">
                <h1 class="upload-title">Upload Music</h1>
                <p class="upload-subtitle">Share your sounds with the world</p>
            </div>
            
            <div class="upload-body">
                <?php if (isset($configError)): ?>
                    <div class="upload-alert error">
                        <div class="upload-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="upload-alert-content">
                            <div class="upload-alert-title">Configuration Error</div>
                            <p class="upload-alert-message"><?php echo $configError; ?></p>
                        </div>
                    </div>
                <?php elseif (isset($success)): ?>
                    <div class="upload-alert success">
                        <div class="upload-alert-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="upload-alert-content">
                            <div class="upload-alert-title">Success!</div>
                            <p class="upload-alert-message"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php elseif (isset($error)): ?>
                    <div class="upload-alert error">
                        <div class="upload-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="upload-alert-content">
                            <div class="upload-alert-title">Upload Failed</div>
                            <p class="upload-alert-message"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="upload-form" method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-step active" id="step-1">
                        <div class="upload-progress-steps">
                            <div class="upload-step active">
                                <div class="upload-step-number">1</div>
                                <div class="upload-step-label">Song Info</div>
                            </div>
                            <div class="upload-step">
                                <div class="upload-step-number">2</div>
                                <div class="upload-step-label">Album & Cover</div>
                            </div>
                            <div class="upload-step">
                                <div class="upload-step-number">3</div>
                                <div class="upload-step-label">Finish</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Song Title *</label>
                            <input type="text" id="title" name="title" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="artist" class="form-label">Artist</label>
                            <small class="form-help">Your username is used as the artist name</small>
                        </div>

                        <div class="form-group">
                            <label for="genre" class="form-label">Genre</label>
                            <input type="text" id="genre" name="genre" class="form-input">
                        </div>

                        <button type="button" class="upload-btn next-step">Continue to Album Info</button>
                    </div>

                    <div class="form-step" id="step-2">
                        <div class="upload-progress-steps">
                            <div class="upload-step completed">
                                <div class="upload-step-number"><i class="fas fa-check"></i></div>
                                <div class="upload-step-label">Song Info</div>
                            </div>
                            <div class="upload-step active">
                                <div class="upload-step-number">2</div>
                                <div class="upload-step-label">Album & Cover</div>
                            </div>
                            <div class="upload-step">
                                <div class="upload-step-number">3</div>
                                <div class="upload-step-label">Finish</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Album Options</label>
                            <div class="album-options">
                                <div class="option-card" id="new-album-option">
                                    <input type="radio" name="use_existing_album" value="0" id="new_album" checked>
                                    <label for="new_album">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Create New Album</span>
                                    </label>
                                </div>
                                
                                <?php if (!empty($userAlbums)): ?>
                                <div class="option-card" id="existing-album-option">
                                    <input type="radio" name="use_existing_album" value="1" id="existing_album">
                                    <label for="existing_album">
                                        <i class="fas fa-music"></i>
                                        <span>Add to Existing Album</span>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <div class="option-card" id="no-album-option">
                                    <input type="radio" name="use_existing_album" value="2" id="no_album">
                                    <label for="no_album">
                                        <i class="fas fa-file-audio"></i>
                                        <span>Single Track (No Album)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="album-form" id="new-album-form">
                            <div class="form-group">
                                <label for="album" class="form-label">Album Name</label>
                                <input type="text" id="album" name="album" class="form-input">
                            </div>
                        </div>
                        
                        <?php if (!empty($userAlbums)): ?>
                        <div class="album-form" id="existing-album-form" style="display: none;">
                            <div class="form-group">
                                <label for="existing_album_select" class="form-label">Select Album</label>
                                <select id="existing_album_select" name="existing_album" class="form-input">
                                    <?php foreach ($userAlbums as $userAlbum): ?>
                                        <option value="<?php echo htmlspecialchars($userAlbum); ?>"><?php echo htmlspecialchars($userAlbum); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-help">Using an existing album will match its cover art if you don't upload a new one</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group" id="cover-art-group">
                            <label for="cover_art" class="form-label">Cover Art (Optional)</label>
                            <div class="cover-upload">
                                <img id="cover_preview" src="defaults/default-cover.jpg" class="cover-preview">
                                <label for="cover_art" class="cover-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Image
                                </label>
                                <input type="file" id="cover_art" name="cover_art" accept="image/*" class="cover-upload-input">
                                <div class="cover-upload-info">Recommended size: 500x500px</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="upload-btn-secondary prev-step">Back</button>
                            <button type="button" class="upload-btn next-step">Continue to Upload</button>
                        </div>
                    </div>

                    <div class="form-step" id="step-3">
                        <div class="upload-progress-steps">
                            <div class="upload-step completed">
                                <div class="upload-step-number"><i class="fas fa-check"></i></div>
                                <div class="upload-step-label">Song Info</div>
                            </div>
                            <div class="upload-step completed">
                                <div class="upload-step-number"><i class="fas fa-check"></i></div>
                                <div class="upload-step-label">Album & Cover</div>
                            </div>
                            <div class="upload-step active">
                                <div class="upload-step-number">3</div>
                                <div class="upload-step-label">Finish</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="song_file" class="form-label">Song File (MP3/WAV) *</label>
                            <div class="song-upload">
                                <div id="song-upload-drop" class="song-upload-dropzone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Drag and drop your song file here, or click to browse</span>
                                </div>
                                <input type="file" id="song_file" name="song_file" accept=".mp3,.wav" required class="song-upload-input">
                                <div class="song-file-info">
                                    <div id="song-file-name">No file selected</div>
                                    <div id="song-file-size"></div>
                                </div>
                                <div class="file-info">Max file size: <span id="max-song-size">
                                    <?php echo ($minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024)) / (1024 * 1024); ?>MB
                                </span></div>
                            </div>
                        </div>
                        
                        <div class="upload-progress-container" style="display: none;">
                            <label class="form-label">Upload Progress</label>
                            <div class="upload-progress-bar-container">
                                <div class="upload-progress-bar" id="upload-progress-bar"></div>
                            </div>
                            <div class="upload-progress-text" id="upload-progress-text">0%</div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="upload-btn-secondary prev-step">Back</button>
                            <button type="submit" class="upload-btn" id="submit-button">Upload Song</button>
                        </div>
                    </div>
                </form>
                
                <div class="upload-summary" id="upload-summary" style="display: none;">
                    <h3>Ready to Upload</h3>
                    <div class="summary-item">
                        <strong>Song:</strong> <span id="summary-title"></span>
                    </div>
                    <div class="summary-item">
                        <strong>Artist:</strong> <span id="summary-artist"><?php echo htmlspecialchars($currentUser); ?></span>
                    </div>
                    <div class="summary-item" id="summary-album-container">
                        <strong>Album:</strong> <span id="summary-album"></span>
                    </div>
                    <div class="summary-item">
                        <strong>Genre:</strong> <span id="summary-genre"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/upload/formValidation.js"></script>
    <script src="js/upload/stepNavigation.js"></script>
    <script src="js/upload/fileHandling.js"></script>
    <script>
        const uploadConfig = {
            handlerUrl: 'handlers/upload_handler.php',
            maxSongSize: <?php echo ($minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024)); ?>,
            maxCoverSize: <?php echo ($minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024)); ?>,
            validSongTypes: <?php echo json_encode($validSongTypes ?? ['audio/mpeg', 'audio/wav', 'audio/x-wav']); ?>,
            validImageTypes: <?php echo json_encode($validImageTypes ?? ['image/jpeg', 'image/png', 'image/webp']); ?>,
            currentUser: '<?php echo htmlspecialchars($currentUser); ?>'
        };
    </script>
    <script src="js/upload/main.js"></script>
</body>
</html>