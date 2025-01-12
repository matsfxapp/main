<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/config.php';

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
	$og_cover_art = htmlspecialchars($result['cover_art']);
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
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <meta property="og:title" content="<?php echo $song_title; ?> - Listen with Joy!" />
    <meta property="og:description" content="This Song <?php echo $song_title; ?> was uploaded to matSFX by <?php echo $artist; ?>. Go listen to it now!" />
    <meta property="og:image" content="https://alpha.matsfx.com/<?php echo htmlspecialchars($cover_art, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo $shareable_link; ?>" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title><?php echo $song_title; ?> - matSFX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --darker-bg: #060912;
            --card-bg: #111827;
            --card-hover: #1F2937;
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-color: #1F2937;
            --gradient-start: #2D7FF9;
            --gradient-end: #18BFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--darker-bg), var(--dark-bg));
            color: var(--light-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .song-container {
            display: flex;
            gap: 40px;
            margin-bottom: 40px;
        }

        .song-cover {
            width: 300px;
            height: 300px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .song-cover:hover {
            transform: scale(1.02);
        }

        .song-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .song-details h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .song-info {
            display: grid;
            gap: 16px;
            margin-bottom: 32px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-item i {
            color: var(--accent-color);
            width: 24px;
        }

        .info-label {
            color: var(--gray-text);
            font-size: 0.9rem;
            min-width: 100px;
        }

        .info-value {
            color: var(--light-text);
            font-weight: 500;
        }

        .share-section {
            background: rgba(31, 41, 55, 0.5);
            border-radius: 16px;
            padding: 24px;
            margin-top: 20px;
        }

        .share-header {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--accent-color);
        }

        .share-controls {
            display: flex;
            gap: 12px;
        }

        .share-link {
            flex-grow: 1;
            padding: 12px 16px;
            background: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--light-text);
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: var(--light-text);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 127, 249, 0.3);
        }

        .btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .song-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 24px;
            }

            .song-cover {
                width: 240px;
                height: 240px;
            }

            .info-item {
                justify-content: center;
            }

            .share-controls {
                flex-direction: column;
            }

            .container {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
	<?php
    require_once 'includes/header.php';
    ?>
    <div class="container">
        <div class="song-container">
            <img src="<?php echo $cover_art; ?>" alt="<?php echo $song_title; ?> Cover Art" class="song-cover">
            <div class="song-details">
                <h1><?php echo $song_title; ?></h1>
                <div class="song-info">
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <span class="info-label">Artist</span>
                        <span class="info-value"><?php echo $artist; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-compact-disc"></i>
                        <span class="info-label">Album</span>
                        <span class="info-value"><?php echo $album; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="info-label">Uploaded</span>
                        <span class="info-value"><?php echo $upload_date; ?></span>
                    </div>
                </div>
                <div class="share-section">
                    <h2 class="share-header">Share this track</h2>
                    <div class="share-controls">
                        <input type="text" id="shareable-link" class="share-link" value="<?php echo $shareable_link; ?>" readonly>
                        <button onclick="copyLink()" class="btn">
                            <i class="fas fa-copy"></i>
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            var link = document.getElementById("shareable-link");
            link.select();
            link.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(link.value)
                .then(() => {
                    const btn = document.querySelector('.btn');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i>Copied!';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    alert("Failed to copy link. Please try again.");
                });
        }
    </script>
</body>
</html>