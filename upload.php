<?php
require_once 'config/config.php';
require_once 'music_handlers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $artist = sanitizeInput($currentUser);
    $album = sanitizeInput($_POST['album']);
    $genre = sanitizeInput($_POST['genre']);
    
    if (empty($artist)) {
        $artist = $currentUser;
    }
    if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_NO_FILE) {
        $coverPath = 'defaults/default-cover.jpg';
    } else {
        $coverPath = $_FILES['cover_art'];
    }
    
    if (uploadSong($title, $artist, $album, $genre, $_FILES['song_file'], $coverPath)) {
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
	
	<?php outputChristmasThemeCSS(); ?>
</head>
<body>
	<?php
    require_once 'includes/header.php';
    ?>

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

    <script>
        // Preview cover art when selected
        document.getElementById('cover_art').addEventListener('change', function(e) {
            const preview = document.getElementById('cover_preview');
            const file = e.target.files[0];
            
            if (file) {
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

        // Handle artist field
        const artistField = document.getElementById('artist');
        const originalArtist = '<?php echo htmlspecialchars($currentUser); ?>';

        artistField.addEventListener('focus', function(e) {
            if (this.value === originalArtist) {
                this.dataset.originalValue = this.value;
            }
        });

        artistField.addEventListener('blur', function(e) {
            if (this.value.trim() === '') {
                this.value = this.dataset.originalValue || originalArtist;
            }
        });

        // Form validation
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const artist = document.getElementById('artist').value.trim();
            const songFile = document.getElementById('song_file').files[0];
            
            if (!title || !artist || !songFile) {
                e.preventDefault();
                alert('Please fill in all required fields (Title, Artist, and Song File)');
            }

            // Validate file type
            if (songFile) {
                const allowedTypes = ['.mp3', '.wav'];
                const fileExt = songFile.name.toLowerCase().substr(songFile.name.lastIndexOf('.'));
                if (!allowedTypes.includes(fileExt)) {
                    e.preventDefault();
                    alert('Please upload only MP3 or WAV files');
                }
            }
        });
    </script>
</body>
</html>