<?php
require_once 'config/config.php';
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

if (!isset($minioConfig) || 
    empty($minioConfig['endpoint']) || 
    empty($minioConfig['credentials']['key']) || 
    empty($minioConfig['credentials']['secret'])) {
    
    $configError = "MinIO storage is not properly configured. Please contact the administrator.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($configError)) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $artist = sanitizeInput($currentUser);
    $album = sanitizeInput($_POST['album'] ?? '');
    $genre = sanitizeInput($_POST['genre'] ?? '');

    $fileValid = true;

    $validSongTypes = $minioConfig['allowed_types']['songs'] ?? ['audio/mpeg', 'audio/wav', 'audio/x-wav'];
    $validImageTypes = $minioConfig['allowed_types']['images'] ?? ['image/jpeg', 'image/png', 'image/gif'];
    
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
            $error = "Invalid cover art file type ($coverFileType). Please upload JPG, PNG or GIF files only.";
            $fileValid = false;
        }
        
        $maxCoverSize = $minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024);
        if ($_FILES['cover_art']['size'] > $maxCoverSize) {
            $error = "Cover art file is too large. Maximum size is " . ($maxCoverSize / (1024 * 1024)) . "MB.";
            $fileValid = false;
        }
    }

    if ($fileValid) {
        try {
            $coverArt = null;
            if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_OK) {
                $coverArt = $_FILES['cover_art'];
            }

            $uploadResult = uploadSong($title, $artist, $album, $genre, $_FILES['song_file'], $coverArt);
            
            if ($uploadResult['success']) {
                $success = "Song uploaded successfully to cloud storage!";
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
    <link rel="stylesheet" href="css/style.css">
    
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
</head>
<body>
    <?php
    require_once 'includes/header.php';
    ?>

    <div class="container">
        <div class="upload-form">
            <h2>Upload Music to Cloud Storage</h2>
            
            <?php if (isset($configError)): ?>
                <div class="alert error"><?php echo $configError; ?></div>
            <?php elseif (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php elseif (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <input type="hidden" id="artist" name="artist" value="<?php echo htmlspecialchars($currentUser); ?>">
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
                    <div class="file-info">Max file size: <span id="max-song-size">
                        <?php echo ($minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024)) / (1024 * 1024); ?>MB
                    </span></div>
                </div>

                <div class="form-group">
                    <label for="cover_art">Cover Art (Optional)</label>
                    <input type="file" id="cover_art" name="cover_art" accept="image/*">
                    <div class="file-info">Recommended size: 500x500px</div>
                    <img id="cover_preview" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>

                <div class="form-group upload-info">
                    <p><small>Files will be stored securely in MinIO cloud storage.</small></p>
                </div>

                <button type="submit" class="btn">Upload Song</button>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('cover_art').addEventListener('change', function(e) {
            const preview = document.getElementById('cover_preview');
            const file = e.target.files[0];
            
            if (file) {
                const maxSize = <?php echo ($minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024)); ?>;
                if (file.size > maxSize) {
                    alert('Cover art file size should not exceed ' + (maxSize / (1024 * 1024)) + 'MB');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        document.getElementById('song_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const maxSize = <?php echo ($minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024)); ?>;
                if (file.size > maxSize) {
                    alert('Song file size should not exceed ' + (maxSize / (1024 * 1024)) + 'MB');
                    this.value = '';
                }
            }
        });

        document.getElementById('upload-form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const songFile = document.getElementById('song_file').files[0];
            
            if (!title || !songFile) {
                e.preventDefault();
                alert('Please fill in all required fields (Title and Song File)');
                return;
            }

            if (songFile) {
                const allowedTypes = ['.mp3', '.wav'];
                const fileExt = songFile.name.toLowerCase().substr(songFile.name.lastIndexOf('.'));
                if (!allowedTypes.includes(fileExt)) {
                    e.preventDefault();
                    alert('Please upload only MP3 or WAV files');
                    return;
                }

                const maxSongSize = <?php echo ($minioConfig['max_sizes']['song'] ?? (20 * 1024 * 1024)); ?>;
                if (songFile.size > maxSongSize) {
                    e.preventDefault();
                    alert('Song file size should not exceed ' + (maxSongSize / (1024 * 1024)) + 'MB');
                    return;
                }
            }

            const coverFile = document.getElementById('cover_art').files[0];
            if (coverFile) {
                const allowedTypes = ['.jpg', '.jpeg', '.png', '.gif'];
                const fileExt = coverFile.name.toLowerCase().substr(coverFile.name.lastIndexOf('.'));
                if (!allowedTypes.some(ext => fileExt.includes(ext))) {
                    e.preventDefault();
                    alert('Please upload only JPG, JPEG, PNG or GIF files for cover art');
                    return;
                }

                const maxCoverSize = <?php echo ($minioConfig['max_sizes']['cover'] ?? (5 * 1024 * 1024)); ?>;
                if (coverFile.size > maxCoverSize) {
                    e.preventDefault();
                    alert('Cover art file size should not exceed ' + (maxCoverSize / (1024 * 1024)) + 'MB');
                    return;
                }
            }
        });
    </script>
</body>
</html>