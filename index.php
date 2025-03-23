<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config/auth.php';
require_once 'handlers/search_handler.php';
require_once 'music_handlers.php';
require_once 'user_handlers.php';
require_once 'config/config.php';

// Get all songs and organize by artist
$songsByArtist = [];
$songs = getAllSongs();
foreach ($songs as $song) {
    $artist = $song['artist'];
    if (!isset($songsByArtist[$artist])) {
        $songsByArtist[$artist] = [];
    }
    $songsByArtist[$artist][] = $song;
}

// Get the most popular artists this week based on play counts
$topArtistsQuery = "
    SELECT s.artist, 
           u.profile_picture, 
           SUM(s.play_count) as total_plays,
           COUNT(DISTINCT s.song_id) as song_count
    FROM songs s
    LEFT JOIN users u ON s.artist = u.username
    GROUP BY s.artist, u.profile_picture
    ORDER BY total_plays DESC
    LIMIT 4";

try {
    $topArtistsResult = $pdo->query($topArtistsQuery);
    $topArtists = $topArtistsResult->fetchAll(PDO::FETCH_ASSOC);
    
    // If no results, try without the date filter
    if (empty($topArtists)) {
        $fallbackQuery = "
            SELECT s.artist, 
                   u.profile_picture, 
                   SUM(COALESCE(s.play_count, 0)) as total_plays,
                   COUNT(DISTINCT s.song_id) as song_count
            FROM songs s
            LEFT JOIN users u ON s.artist = u.username
            GROUP BY s.artist, u.profile_picture
            ORDER BY total_plays DESC
            LIMIT 4";
        $topArtistsResult = $pdo->query($fallbackQuery);
        $topArtists = $topArtistsResult->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Fallback to the original method if the query fails
    error_log("Error fetching top artists by play count: " . $e->getMessage());
    $artistSongCounts = array_map('count', $songsByArtist);
    arsort($artistSongCounts);
    $topArtistNames = array_slice(array_keys($artistSongCounts), 0, 4);
    
    // Convert to the same format as the query result
    $topArtists = [];
    foreach ($topArtistNames as $artistName) {
        $profilePicQuery = "SELECT profile_picture FROM users WHERE username = ?";
        $stmt = $pdo->prepare($profilePicQuery);
        $stmt->execute([$artistName]);
        $profilePic = $stmt->fetchColumn() ?: 'defaults/default-profile.jpg';
        
        $playCountQuery = "SELECT SUM(play_count) FROM songs WHERE artist = ?";
        $stmt = $pdo->prepare($playCountQuery);
        $stmt->execute([$artistName]);
        $playCount = $stmt->fetchColumn() ?: 0;
        
        // Get song count
        $songCountQuery = "SELECT COUNT(DISTINCT song_id) FROM songs WHERE artist = ?";
        $stmt = $pdo->prepare($songCountQuery);
        $stmt->execute([$artistName]);
        $songCount = $stmt->fetchColumn() ?: 0;
        
        $topArtists[] = [
            'artist' => $artistName,
            'profile_picture' => $profilePic,
            'total_plays' => $playCount,
            'song_count' => $songCount
        ];
    }
}

// Featured artist selection - choose one of the top artists with some extra criteria
if (!empty($topArtists)) {
    // Weighted selection based on plays and recency
    $weightedArtists = [];
    
    foreach ($topArtists as $index => $artist) {
        $artistName = $artist['artist'];
        
        // Get the most recent song from this artist
        $recentSongQuery = "SELECT upload_date FROM songs WHERE artist = ? ORDER BY upload_date DESC LIMIT 1";
        $stmt = $pdo->prepare($recentSongQuery);
        $stmt->execute([$artistName]);
        $latestUpload = $stmt->fetchColumn();
        
        // Calculate recency score (higher for more recent uploads)
        $recencyScore = 1;
        if ($latestUpload) {
            $daysSince = max(1, floor((time() - strtotime($latestUpload)) / 86400));
            $recencyScore = min(5, 30 / $daysSince); // Higher score for more recent uploads
        }
        
        // Calculate popularity score
        $popularityScore = log10(max(10, $artist['total_plays']));
        
        // Calculate variety score
        $varietyScore = min(5, $artist['song_count'] / 2);
        
        // Final weight combines all factors
        $weight = $recencyScore + $popularityScore + $varietyScore;
        
        // Store with weight
        $weightedArtists[] = [
            'artist' => $artist,
            'weight' => $weight
        ];
    }
    
    // Sort by weight
    usort($weightedArtists, function($a, $b) {
        return $b['weight'] <=> $a['weight'];
    });
    
    // Select the top weighted artist as featured
    $featuredArtist = $weightedArtists[0]['artist'];
    $featuredArtistName = $featuredArtist['artist'];
    
    // Get artist bio and additional info
    $bioQuery = "SELECT bio FROM users WHERE username = ?";
    $stmt = $pdo->prepare($bioQuery);
    $stmt->execute([$featuredArtistName]);
    $featuredArtistBio = $stmt->fetchColumn() ?: "Check out the latest tracks from $featuredArtistName!";
    
    // Get top song from this artist
    $topSongQuery = "SELECT * FROM songs WHERE artist = ? ORDER BY play_count DESC LIMIT 1";
    $stmt = $pdo->prepare($topSongQuery);
    $stmt->execute([$featuredArtistName]);
    $featuredArtistTopSong = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total plays for this artist (formatted nicely)
    $formattedPlays = number_format($featuredArtist['total_plays']);
}

// Get new releases (songs uploaded in the last 30 days)
$newReleasesQuery = "
    SELECT s.*, u.profile_picture
    FROM songs s
    LEFT JOIN users u ON s.artist = u.username
    WHERE s.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY s.upload_date DESC
    LIMIT 6";
    
try {
    $newReleasesStmt = $pdo->query($newReleasesQuery);
    $newReleases = $newReleasesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching new releases: " . $e->getMessage());
    $newReleases = [];
}

// Get genres from songs
$genresQuery = "
    SELECT DISTINCT genre 
    FROM songs 
    WHERE genre IS NOT NULL AND genre != '' 
    ORDER BY genre";
    
try {
    $genresStmt = $pdo->query($genresQuery);
    $genres = $genresStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching genres: " . $e->getMessage());
    $genres = [];
}

// Get songs by genre for a featured genre
if (!empty($genres)) {
    // Pick a random genre for variety each time
    $featuredGenreIndex = array_rand($genres);
    $featuredGenre = $genres[$featuredGenreIndex];
    
    $genreSongsQuery = "
        SELECT * FROM songs 
        WHERE genre = ? 
        ORDER BY play_count DESC 
        LIMIT 6";
        
    try {
        $genreSongsStmt = $pdo->prepare($genreSongsQuery);
        $genreSongsStmt->execute([$featuredGenre]);
        $genreSongs = $genreSongsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching genre songs: " . $e->getMessage());
        $genreSongs = [];
    }
}

$artistSongCounts = array_map('count', $songsByArtist);
arsort($artistSongCounts);

// Get original top artists for the artist sections
$originalTopArtists = array_slice(array_keys($artistSongCounts), 0, 2);

// Get new users
$newUsersQuery = "
    SELECT DISTINCT u.username, u.created_at, u.profile_picture, COUNT(s.song_id) as song_count 
    FROM users u 
    INNER JOIN songs s ON u.username = s.artist OR u.username = s.uploaded_by 
    GROUP BY u.username, u.created_at, u.profile_picture 
    ORDER BY u.created_at DESC 
    LIMIT 5";
$newUsersResult = $pdo->query($newUsersQuery);
$newUsers = $newUsersResult->fetchAll(PDO::FETCH_ASSOC);

// Get songs that arent from the top artists
$remainingSongs = [];
foreach ($songs as $song) {
    if (!in_array($song['artist'], $originalTopArtists)) {
        $remainingSongs[] = $song;
    }
}

// Get recently played for logged-in users
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $recentlyPlayed = getUserPlayHistory($userId, 6);
    
    // Get personalized recommendations based on user listening history
    if (!empty($recentlyPlayed)) {
        // Extract artists and genres from recently played
        $recentArtists = [];
        $recentGenres = [];
        
        foreach ($recentlyPlayed as $song) {
            if (!empty($song['artist'])) {
                $recentArtists[] = $song['artist'];
            }
            if (!empty($song['genre'])) {
                $recentGenres[] = $song['genre'];
            }
        }
        
        // Unique artists and genres
        $recentArtists = array_unique($recentArtists);
        $recentGenres = array_unique($recentGenres);
        
        // Build recommendation query based on similar artists and genres
        $recommendationsQuery = "
            SELECT s.*, 
                   (CASE WHEN s.artist IN (" . implode(',', array_fill(0, count($recentArtists), '?')) . ") THEN 3 ELSE 0 END) +
                   (CASE WHEN s.genre IN (" . implode(',', array_fill(0, count($recentGenres), '?')) . ") THEN 2 ELSE 0 END) +
                   (s.play_count / 10) as match_score
            FROM songs s
            WHERE s.song_id NOT IN (
                SELECT song_id FROM song_plays WHERE user_id = ? ORDER BY play_date DESC LIMIT 10
            )
            ORDER BY match_score DESC, RAND()
            LIMIT 5";
            
        try {
            $params = array_merge($recentArtists, $recentGenres);
            $params[] = $userId;
            
            $recommendationsStmt = $pdo->prepare($recommendationsQuery);
            $recommendationsStmt->execute($params);
            $recommendations = $recommendationsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching recommendations: " . $e->getMessage());
            
            // Fallback recommendation strategy
            $recommendationsQuery = "
                SELECT * FROM songs 
                WHERE song_id NOT IN (
                    SELECT song_id FROM song_plays WHERE user_id = ?
                )
                ORDER BY play_count DESC
                LIMIT 5";
                
            try {
                $recommendationsStmt = $pdo->prepare($recommendationsQuery);
                $recommendationsStmt->execute([$userId]);
                $recommendations = $recommendationsStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error fetching fallback recommendations: " . $e->getMessage());
                $recommendations = [];
            }
        }
    }
}

/**
 * Convert datetime to "time ago" format
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

// Get popular songs for the main trending section
$popularSongs = getMostPlayedSongs(5);

// Check if the user is new (registered within the last 7 days)
$isNewUser = false;
if (isLoggedIn()) {
    $userQuery = "SELECT created_at FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($userQuery);
    $stmt->execute([$_SESSION['user_id']]);
    $userCreatedAt = $stmt->fetchColumn();
    
    if ($userCreatedAt) {
        $daysSinceRegistration = (time() - strtotime($userCreatedAt)) / 86400;
        $isNewUser = $daysSinceRegistration <= 7;
    }
}

// Get site statistics
try {
    $statsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM songs) as song_count,
            (SELECT COUNT(DISTINCT artist) FROM songs) as artist_count,
            (SELECT SUM(play_count) FROM songs) as total_plays
    ";
    $statsStmt = $pdo->query($statsQuery);
    $siteStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching site stats: " . $e->getMessage());
    $siteStats = [
        'song_count' => count($songs),
        'artist_count' => count(array_keys($songsByArtist)),
        'total_plays' => array_sum(array_column($songs, 'play_count'))
    ];
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
    <link rel="stylesheet" href="css/index/topArtists.css">
    <link rel="stylesheet" href="css/index/featuredArtist.css">
    <link rel="stylesheet" href="css/index/forYou.css">
    <link rel="stylesheet" href="css/index/genreSection.css">
    <link rel="stylesheet" href="css/index/newReleases.css">
    <link rel="stylesheet" href="css/index/siteStats.css">
    <link rel="stylesheet" href="css/index/topArtists.css">
    <link rel="stylesheet" href="css/index/welcomeBanner.css">

    <script src="js/share-button.js"></script>
    <?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <?php
    require_once 'includes/header.php';
    ?>
    <div class="header-spacer"></div>
    <div class="container">
        
        <?php if (!isLoggedIn()): ?>
        <!-- Welcome banner for logged out users -->
        <div class="welcome-banner">
            <div class="welcome-backdrop"></div>
            <div class="welcome-content">
                <h2 class="welcome-title">Welcome to matSFX</h2>
                <p class="welcome-desc">Experience music like never before with our ad-free, open-source platform. Join our community to upload your own music or discover new artists!</p>
                <div class="welcome-cta">
                    <a href="/register" class="welcome-button">
                        <i class="fas fa-user-plus"></i>
                        Sign Up Free
                    </a>
                    <a href="/login" class="welcome-button" style="background-color: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-sign-in-alt"></i>
                        Log In
                    </a>
                </div>
            </div>
        </div>
        <?php elseif ($isNewUser): ?>
        <!-- Welcome banner for new users -->
        <div class="welcome-banner">
            <div class="welcome-backdrop"></div>
            <div class="welcome-content">
                <h2 class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p class="welcome-desc">Thanks for joining our community! Start by exploring trending songs, or upload your own music to share with the world.</p>
                <div class="welcome-cta">
                    <a href="/upload" class="welcome-button">
                        <i class="fas fa-upload"></i>
                        Upload Your Music
                    </a>
                    <a href="#recommendations" class="welcome-button" style="background-color: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-headphones"></i>
                        Discover Music
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recently played -->
        <?php if (isLoggedIn() && !empty($recentlyPlayed)): ?>
        <div class="section-container recently-played-container">
            <div class="section-header">
                <h2 class="section-title">Recently Played</h2>
            </div>
            
            <div class="recently-played-list">
                <?php foreach ($recentlyPlayed as $song): ?>
                    <div class="recently-played-item" 
                        onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
                        data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                        data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>"
                        data-cover-art="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>">

                        <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" 
                            alt="Cover Art" 
                            class="recently-played-cover">
                        <div class="recently-played-info">
                            <div class="recently-played-title"><?php echo htmlspecialchars($song['title']); ?></div>
                            <div class="recently-played-artist">
                                <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link"
                                onclick="event.stopPropagation()">
                                    <?php echo htmlspecialchars($song['artist']); ?>
                                </a>
                            </div>
                        </div>
                        <div class="recently-played-time">
                            <?php echo getTimeAgo($song['play_date']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Personalized recommendations -->
        <?php if (isLoggedIn() && isset($recommendations) && !empty($recommendations)): ?>
        <div class="for-you-section" id="recommendations">
            <div class="section-header">
                <h2 class="section-title">For You</h2>
            </div>
            
            <div class="popular-songs-grid">
                <?php foreach ($recommendations as $song): ?>
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
        <?php endif; ?>
        
        <!-- Top Artists -->
        <div class="top-artists-section">
            <div class="section-header">
                <h2 class="section-title">Trending Artists</h2>
                <div class="fire-animation">🔥</div>
            </div>
            
            <div class="top-artists-container">
                <?php foreach ($topArtists as $index => $artist): ?>
                    <?php 
                    $artistName = is_array($artist['artist']) ? 'Unknown Artist' : $artist['artist']; 
                    ?>
                    <a href="artist?name=<?php echo urlencode($artistName); ?>" class="top-artist-card">
                        <div class="top-artist-image-container">
                            <img src="<?php echo htmlspecialchars($artist['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($artistName); ?>" 
                                 class="top-artist-image"
                                 onerror="this.src='defaults/default-profile.jpg'">
                            
                            <div class="top-artist-rank"><?php echo $index + 1; ?></div>
                            
                            <div class="top-artist-plays">
                                <i class="fas fa-play"></i>
                                <?php echo number_format($artist['total_plays']); ?>
                            </div>
                        </div>
                        
                        <div class="top-artist-info">
                            <div class="top-artist-name"><?php echo htmlspecialchars($artistName); ?></div>
                            <div class="top-artist-label">Hot this week</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- New Releases -->
        <?php if (!empty($newReleases)): ?>
        <div class="new-releases-section">
            <div class="new-releases-header">
                <h2 class="section-title">New Releases</h2>
            </div>
            
            <div class="new-releases-grid">
                <?php foreach ($newReleases as $release): ?>
                    <div class="new-release-card" 
                         onclick="playSong('<?php echo htmlspecialchars($release['file_path']); ?>', this)"
                         data-song-title="<?php echo htmlspecialchars($release['title']); ?>"
                         data-song-artist="<?php echo htmlspecialchars($release['artist']); ?>"
                         data-song-id="<?php echo htmlspecialchars($release['song_id']); ?>"
                         data-cover-art="<?php echo htmlspecialchars($release['cover_art'] ?? 'defaults/default-cover.jpg'); ?>">
                        
                        <img src="<?php echo htmlspecialchars($release['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($release['title']); ?>" 
                             class="new-release-cover">
                             
                        <div class="new-badge">New</div>
                        
                        <div class="new-release-info">
                            <div class="new-release-title"><?php echo htmlspecialchars($release['title']); ?></div>
                            <div class="new-release-artist">
                                <a href="artist?name=<?php echo urlencode($release['artist']); ?>" class="artist-link" 
                                   onclick="event.stopPropagation()">
                                    <?php echo htmlspecialchars($release['artist']); ?>
                                </a>
                            </div>
                            <div class="new-release-date">
                                Released <?php echo date('M j', strtotime($release['upload_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular songs -->
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
        
        <!-- Genre section -->
        <?php if (!empty($genres) && !empty($genreSongs)): ?>
            <div class="genre-section">
                <div class="genre-header">
                    <div class="genre-title-row">
                        <h2 class="section-title">Discover by Genre</h2>
                        <div class="genre-badge"><?php echo htmlspecialchars($featuredGenre); ?></div>
                    </div>
                </div>
                
                <div class="category-pills" id="genre-pills">
                    <?php foreach ($genres as $genre): ?>
                        <div class="category-pill <?php echo ($genre === $featuredGenre) ? 'active' : ''; ?>" 
                            data-genre="<?php echo htmlspecialchars($genre); ?>">
                            <?php echo htmlspecialchars($genre); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="genre-grid" id="genre-songs-container">
                    <?php foreach ($genreSongs as $song): ?>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- New Artists -->
        <div class="new-users-section">
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

        <!-- Top Artists Sections (old system) -->
        <?php foreach ($originalTopArtists as $artist): ?>
        <div class="artist-section">
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

        <!-- Featured artist -->
        <?php if (isset($featuredArtist) && !empty($featuredArtist)): ?>
        <div class="featured-artist-banner">
            <div class="featured-artist-content">
                <div class="featured-artist-label">Featured Artist</div>
                <h2 class="featured-artist-name"><?php echo htmlspecialchars($featuredArtistName); ?></h2>
                <p class="featured-artist-desc"><?php echo htmlspecialchars(mb_substr($featuredArtistBio, 0, 150)) . (strlen($featuredArtistBio) > 150 ? '...' : ''); ?></p>
                <div class="featured-artist-cta">
                    <a href="artist?name=<?php echo urlencode($featuredArtistName); ?>" class="featured-artist-button primary">
                        <i class="fas fa-user"></i>
                        View Artist
                    </a>
                    <?php if (isset($featuredArtistTopSong) && !empty($featuredArtistTopSong)): ?>
                    <a href="javascript:void(0)" onclick="playSong('<?php echo htmlspecialchars($featuredArtistTopSong['file_path']); ?>', this)" 
                       data-song-title="<?php echo htmlspecialchars($featuredArtistTopSong['title']); ?>"
                       data-song-artist="<?php echo htmlspecialchars($featuredArtistTopSong['artist']); ?>"
                       data-song-id="<?php echo htmlspecialchars($featuredArtistTopSong['song_id']); ?>"
                       data-cover-art="<?php echo htmlspecialchars($featuredArtistTopSong['cover_art'] ?? 'defaults/default-cover.jpg'); ?>"
                       class="featured-artist-button secondary">
                        <i class="fas fa-play"></i>
                        Play Top Track
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="featured-artist-image">
                <img src="<?php echo htmlspecialchars($featuredArtist['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" alt="Featured Artist">
            </div>
            <div class="featured-bg-pattern"></div>
        </div>
        <?php endif; ?>

        <!-- Remaining Songs -->
        <?php if (!empty($remainingSongs)): ?>
        <div class="more-music-section">
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
        <?php endif; ?>

        <!-- Site stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-music"></i>
                </div>
                <div class="stat-number"><?php echo number_format($siteStats['song_count']); ?></div>
                <div class="stat-label">Songs</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class='fas fa-record-vinyl'></i>
                </div>
                <div class="stat-number"><?php echo number_format($siteStats['artist_count']); ?></div>
                <div class="stat-label">Artists</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($siteStats['total_plays']); ?></div>
                <div class="stat-label">Total Plays</div>
            </div>
            
    </div>


    <div class="player-spacer"></div>
    <?php require_once 'includes/player.php'?>

    <script src="js/index.js"></script>
    <script src="js/search.js"></script>
</body>
</html>