<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (isset($_GET['song_id'])) {
    $song_id = $_GET['song_id'];
} else {
    echo "Song not found.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM songs WHERE song_id = :song_id");
$stmt->bindParam(':song_id', $song_id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $song_title = htmlspecialchars($result['title']);
    $artist = htmlspecialchars($result['artist']);
    $album = htmlspecialchars($result['album']);
    $file_path = htmlspecialchars($result['file_path']);
    $cover_art = htmlspecialchars($result['cover_art']);
    $uploaded_by = htmlspecialchars($result['artist']);
    $upload_date = htmlspecialchars($result['upload_date']);
} else {
    echo "Song not found.";
    exit;
}

// generate link
$shareable_link = "https://alpha.matsfx.com/song?song_id={$song_id}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $song_title; ?> - matSFX</title>
    <link rel="stylesheet" href="css/song-link-style.css">
	
	<?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <div class="song-container">
        <img src="<?php echo $cover_art; ?>" alt="<?php echo $song_title; ?> Cover Art" class="song-cover">
        <div class="song-details">
            <h1><?php echo $song_title; ?></h1>
            <p><strong>Artist:</strong> <?php echo $artist; ?></p>
            <p><strong>Album:</strong> <?php echo $album; ?></p>
            <p><strong>Uploaded on:</strong> <?php echo $upload_date; ?></p>
        </div>
    </div>

    <div class="link-section">
        <input type="text" id="shareable-link" class="share-link" value="<?php echo $shareable_link; ?>" readonly>
        <button onclick="copyLink()" class="btn btn-copy">Copy Link</button>
    </div>

    <script>
        function copyLink() {
            var link = document.getElementById("shareable-link");
            link.select();
            link.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(link.value)
                .then(() => {
                    alert("Link copied to clipboard!");
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    alert("Failed to copy link. Please try again.");
                });
        }
    </script>
</body>
</html>