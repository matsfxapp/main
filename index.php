<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once 'config/auth.php';
require_once 'music_handlers.php';
require_once 'handlers/search_handler.php';

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
    <link rel="stylesheet" href="css/index-artistsection.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/share-button.css">
    <script src="js/share-button.js"></script>
    <?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <?php
    require_once 'includes/header.php';
    ?>

    <div class="container">
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
                                data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>">
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
            .new-users-section {
                position: relative;
                padding: 0 1rem;
            }

            .new-users-grid {
                display: flex;
                gap: 2rem;
                margin-top: 2rem;
                overflow-x: auto;
                overflow-y: hidden;
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                padding: 1rem 0.5rem;
                -webkit-overflow-scrolling: touch;
            }

            .new-users-grid::-webkit-scrollbar {
                height: 8px;
            }

            .new-users-grid::-webkit-scrollbar-track {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 4px;
            }

            .new-users-grid::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.3);
                border-radius: 4px;
            }

            .new-users-grid::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.4);
            }

            .user-card {
                flex: 0 0 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-decoration: none;
                color: inherit;
                transition: all 0.2s ease;
                scroll-snap-align: start;
                width: 120px;
                opacity: 0.7;
            }

            .user-card:hover {
                transform: translateY(-5px);
                opacity: 1;
            }

            .user-profile-pic {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                object-fit: cover;
                margin-bottom: 1rem;
                transition: transform 0.2s ease;
            }

            .user-info {
                text-align: center;
                width: 100%;
            }

            .username {
                font-weight: 600;
                margin-bottom: 0.5rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .join-date {
                font-size: 0.9rem;
                color: #666;
            }

            /* Mobile Styles */
            @media (max-width: 768px) {
                .new-users-grid {
                    gap: 1rem;
                    padding: 0.5rem;
                }

                .user-card {
                    width: 80px;
                }

                .user-profile-pic {
                    width: 80px;
                    height: 80px;
                }

                .username {
                    font-size: 0.9rem;
                }

                .join-date {
                    font-size: 0.75rem;
                }
            }
        </style>

        <!-- Remaining Songs in Grid -->
        <h2 class="section-title">More Songs</h2>
        <div class="music-grid">
            <?php foreach ($remainingSongs as $song): ?>
                <div class="song-card" onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)">
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
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
            });
        }
    </script>
</body>
</html>