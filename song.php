<?php
require_once 'config/config.php';
require_once 'config/terminated_account_middleware.php';

$song_found = false;

if (isset($_GET['share'])) {
    $shareCode = $_GET['share'];
    
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE share_code = :share_code LIMIT 1");
    $stmt->bindParam(':share_code', $shareCode, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $song_found = true;
    }
}
elseif (isset($_GET['song_id'])) {
    $song_id = $_GET['song_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE song_id = :song_id");
    $stmt->bindParam(':song_id', $song_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $song_found = true;
    }
}

if ($song_found) {
    $song_title = htmlspecialchars($result['title']);
    $artist = htmlspecialchars($result['artist']);
    $album = htmlspecialchars($result['album']);
    $file_path = htmlspecialchars($result['file_path']);
    $cover_art = htmlspecialchars($result['cover_art']);
    $uploaded_by = htmlspecialchars($result['artist']);
    $upload_date = htmlspecialchars($result['upload_date']);
    $song_id = $result['song_id'];
    
    require_once 'handlers/share_utils.php';
    $shareCode = getShareCode($pdo, $song_id);

    $shareable_link = "https://alpha.matsfx.com/song?share={$shareCode}";
    
    // Format the date nicely
    $formatted_date = date("Y-m-d", strtotime($upload_date));
} else {
    require_once 'includes/header.php';
    echo '<div class="container"><h1>Error</h1><p>Song not found.</p></div>';
    exit;
}
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
            --primary-color: #3E8BFF;
            --primary-hover: #2E7BEE;
            --accent-color: #18BFFF;
            --dark-bg: #0B0E16;
            --darker-bg: #0A0C14;
            --card-bg: #121620;
            --card-hover: #1B2032;
            --light-text: #FFFFFF;
            --gray-text: #9EACC7;
            --border-color: #1F2937;
            --share-bg: #12162F;
            --gradient-primary: linear-gradient(90deg, #3E8BFF, #2E7BEE);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--light-text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .song-page-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            padding-top: 80px; /* Space for navbar */
        }

        .song-detail-card {
            background-color: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .song-content {
            display: flex;
            padding: 30px;
            gap: 30px;
        }

        .song-cover {
            width: 280px;
            height: 280px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            flex-shrink: 0;
        }

        .song-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .song-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--light-text);
        }

        .song-meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px 20px;
            margin-bottom: 25px;
            align-items: center;
        }
        
        .meta-label {
            color: var(--gray-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .meta-label i {
            width: 16px;
            text-align: center;
            color: var(--accent-color);
        }
        
        .meta-value {
            color: var(--light-text);
            font-weight: 500;
        }
        
        .meta-value a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .meta-value a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        .play-button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: var(--light-text);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: auto;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }

        .play-button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 127, 249, 0.3);
        }

        .share-section {
            background-color: var(--share-bg);
            padding: 24px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
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
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light-text);
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
        }

        .copy-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: var(--light-text);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .player-spacer {
            height: 75px;
            width: 100%;
            display: block;
        }

        @media (max-width: 768px) {
            .song-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 24px;
            }

            .song-cover {
                width: 200px;
                height: 200px;
            }
            
            .song-title {
                font-size: 2rem;
            }

            .song-meta {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }
            
            .meta-label, .meta-value {
                justify-content: center;
            }

            .share-controls {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <div class="header-spacer"></div>
    
    <div class="song-page-container">
        <div class="song-detail-card">
            <div class="song-content">
                <img src="<?php echo $cover_art; ?>" alt="<?php echo $song_title; ?> Cover Art" class="song-cover">
                
                <div class="song-details">
                    <h1 class="song-title"><?php echo $song_title; ?></h1>
                    
                    <div class="song-meta">
                        <div class="meta-label"><i class="fas fa-user"></i> Artist</div>
                        <div class="meta-value">
                            <a href="artist?name=<?php echo urlencode($artist); ?>">
                                <?php echo $artist; ?>
                            </a>
                        </div>
                        
                        <div class="meta-label"><i class="fas fa-compact-disc"></i> Album</div>
                        <div class="meta-value"><?php echo $album ? $album : 'Single'; ?></div>
                        
                        <div class="meta-label"><i class="fas fa-calendar-alt"></i> Uploaded</div>
                        <div class="meta-value"><?php echo $formatted_date; ?></div>
                    </div>
                    
                    <button class="play-button" 
                        onclick="playSong('<?php echo $file_path; ?>', this)" 
                        data-song-title="<?php echo $song_title; ?>"
                        data-song-artist="<?php echo $artist; ?>"
                        data-song-id="<?php echo $song_id; ?>"
                        data-cover-art="<?php echo $cover_art; ?>">
                        <i class="fas fa-play"></i> Play Song
                    </button>
                </div>
            </div>
            
            <div class="share-section">
                <h2 class="share-header">Share this track</h2>
                <div class="share-controls">
                    <input type="text" id="shareable-link" class="share-link" value="<?php echo $shareable_link; ?>" readonly>
                    <button onclick="copyLink()" class="copy-btn">
                        <i class="fas fa-copy"></i>
                        Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="player-spacer"></div>
    <?php require_once 'includes/player.php'; ?>

    <script>
        function copyLink() {
            var link = document.getElementById("shareable-link");
            link.select();
            link.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(link.value)
                .then(() => {
                    const btn = document.querySelector('.copy-btn');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
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