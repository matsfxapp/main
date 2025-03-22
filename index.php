<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config/auth.php';
require_once 'handlers/search_handler.php';
require_once 'music_handlers.php';
require_once 'user_handlers.php';
require_once 'config/config.php';

$songsByArtist = [];
$songs = getAllSongs();
foreach ($songs as $song) {
    $artist = $song['artist'];
    if (!isset($songsByArtist[$artist])) {
        $songsByArtist[$artist] = [];
    }
    $songsByArtist[$artist][] = $song;
}

$artistSongCounts = array_map('count', $songsByArtist);
arsort($artistSongCounts);

$topArtists = array_slice(array_keys($artistSongCounts), 0, 2);

$newUsersQuery = "
    SELECT DISTINCT u.username, u.created_at, u.profile_picture, COUNT(s.song_id) as song_count 
    FROM users u 
    INNER JOIN songs s ON u.username = s.artist OR u.username = s.uploaded_by 
    GROUP BY u.username, u.created_at, u.profile_picture 
    ORDER BY u.created_at DESC 
    LIMIT 5";
$newUsersResult = $pdo->query($newUsersQuery);
$newUsers = $newUsersResult->fetchAll(PDO::FETCH_ASSOC);

$remainingSongs = [];
foreach ($songs as $song) {
    if (!in_array($song['artist'], $topArtists)) {
        $remainingSongs[] = $song;
    }
}

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $recentlyPlayed = getUserPlayHistory($userId, 5);
    
    if (!empty($recentlyPlayed)) {
    }
}

/**
 * Convert datetime to "time ago" format
 * 
 * @param string $datetime The datetime to convert
 * @return string Formatted time string
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j", $time);
    }
}

$popularSongs = getMostPlayedSongs(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <meta property="og:title" content="matSFX - Listen with Joy!" />
    <meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
    <meta property="og:image" content="app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">

    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="matSFX">
    <link rel="apple-touch-icon" href="/app_logos/matsfx_logo.png">
    <meta name="theme-color" content="#000000">
    
    <title>matSFX - Music for everyone</title>

    <!-- links -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/share-button.css">

    <link rel="stylesheet" href="css/index/artistSection.css">
    <link rel="stylesheet" href="css/index/newArtists.css">
    <link rel="stylesheet" href="css/index/popularSongs.css">
    <link rel="stylesheet" href="css/index/recentlyPlayed.css">

    <script src="js/share-button.js"></script>
    <?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <?php
    require_once 'includes/header.php';
    ?>
    <div class="header-spacer"></div>
    <div class="container">
        <!-- recently played (will only be shown if user is logged in and has history-->
        <?php if (isLoggedIn() && !empty($recentlyPlayed)): ?>
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Recently Played</h2>
            </div>
            
            <div class="recently-played-grid">
                <?php foreach ($recentlyPlayed as $song): ?>
                    <div class="song-card recent-play" 
                        onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
                        data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                        data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
                        <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                        <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                        <div class="song-artist">
                            <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                                <?php echo htmlspecialchars($song['artist']); ?>
                            </a>
                        </div>
                        <div class="play-time">
                            <i class="far fa-clock"></i> 
                            <?php echo getTimeAgo($song['play_date']); ?>
                        </div>
                        <?php
                        require 'includes/like_button.php';
                        require 'includes/share_button.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- popular songs section -->
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Popular Songs</h2>
                <div class="section-filters">
                    <button class="filter-btn active" data-period="all">All Time</button>
                    <button class="filter-btn" data-period="month">This Month</button>
                    <button class="filter-btn" data-period="week">This Week</button>
                    <button class="filter-btn" data-period="day">Today</button>
                </div>
            </div>
            
            <div class="popular-songs-grid" id="popularSongsGrid">
                <?php foreach ($popularSongs as $song): ?>
                    <div class="song-card" 
                        onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
                        data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                        data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
                        <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                        <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                        <div class="song-artist">
                            <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                                <?php echo htmlspecialchars($song['artist']); ?>
                            </a>
                        </div>
                        <div class="song-stats">
                            <span class="play-count">
                                <i class="fas fa-play-circle"></i> <?php echo number_format($song['play_count']); ?>
                            </span>
                        </div>
                        <?php
                        require 'includes/like_button.php';
                        require 'includes/share_button.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Artists Sections -->
        <?php foreach ($topArtists as $artist): ?>
        <div class="artist-section" style="margin-top: 6rem;">
            <div class="artist-section-header">
                <h2 class="section-title">Songs from <?php echo htmlspecialchars($artist); ?></h2>
                <div class="navigation-buttons">
                    <button class="navigation-button nav-prev">&larr;</button>
                    <button class="navigation-button nav-next">&rarr;</button>
                </div>
            </div>
            <div class="artist-songs-container">
                <div class="music-grid-artist">
                    <?php foreach ($songsByArtist[$artist] as $song): ?>
                    <div class="song-card"
                        onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
                        data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                        data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
                        <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                        <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                        <div class="song-artist">
                            <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                                <?php echo htmlspecialchars($song['artist']); ?>
                            </a>
                        </div>
                        <?php
                        require 'includes/like_button.php';
                        require 'includes/share_button.php';
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- New Artists Section -->
        <div class="new-users-section" style="margin: 4rem 0;">
            <h2 class="section-title">New Artists</h2>
            <div class="new-users-grid">
                <?php foreach ($newUsers as $user): ?>
                    <a href="artist?name=<?php echo urlencode($user['username']); ?>" class="user-card">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" 
                             alt="Profile Picture" 
                             class="user-profile-pic">
                        <div class="user-info">
                            <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="join-date">Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        </style>

        <!-- Remaining Songs in Grid -->
        <h2 class="section-title">More Songs</h2>
        <div class="music-grid">
            <?php foreach ($remainingSongs as $song): ?>
            <div class="song-card" 
                onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
                data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
                <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                <div class="song-artist">
                    <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                        <?php echo htmlspecialchars($song['artist']); ?>
                    </a>
                </div>
                <?php
                require 'includes/like_button.php';
                require 'includes/share_button.php';
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="player-spacer"></div>
    <?php
    require_once 'includes/player.php'
    ?>

    <script src="js/index.js"></script>
    <script src="js/search.js"></script>
</body>
</html>