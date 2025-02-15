<?php
require_once 'config/config.php';
require_once 'config/auth.php';
require_once 'music_handlers.php';
require_once 'handlers/search_handler.php';

// Group songs by artist and count
$songsByArtist = [];
$songs = getAllSongs();
foreach ($songs as $song) {
    $artist = $song['artist'];
    if (!isset($songsByArtist[$artist])) {
        $songsByArtist[$artist] = [];
    }
    $songsByArtist[$artist][] = $song;
}

// sort artists by number of songs
$artistSongCounts = array_map('count', $songsByArtist);
arsort($artistSongCounts);

// get top 2 artists
$topArtists = array_slice(array_keys($artistSongCounts), 0, 2);

// get new users by creation date 
// only add users who have uploaded songs
// they will be ordered by creation date
$newUsersQuery = "
    SELECT DISTINCT u.username, u.created_at, u.profile_picture, COUNT(s.song_id) as song_count 
    FROM users u 
    INNER JOIN songs s ON u.username = s.artist OR u.username = s.uploaded_by 
    GROUP BY u.username, u.created_at, u.profile_picture 
    ORDER BY u.created_at DESC 
    LIMIT 5";
$newUsersResult = $conn->query($newUsersQuery);
$newUsers = $newUsersResult->fetchAll(PDO::FETCH_ASSOC);

// remaining songs
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
    <meta name="monetag" content="5b5da452bb7f578199b5f1d963c7b3bf">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <meta property="og:title" content="matSFX - Listen with Joy!" />
    <meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
    <meta property="og:image" content="app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>matSFX - Music for everyone</title>

    <!-- links -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index-artistsection.css">
    <link rel="stylesheet" href="css/share-button.css">
    <link rel="stylesheet" href="css/navbar.css">
    <script src="js/share-button.js"></script>
    <script>
        window.addEventListener('scroll', function() {
            const banner = document.querySelector('.sticky-banner');
            if (window.pageYOffset > -1) {
                banner.style.display = 'block';
            } else {
                banner.style.display = 'none';
            }
        });

        function closeStickyBanner() {
            const banner = document.getElementById('stickyBanner');
            banner.classList.add('banner-hidden');
        }
    </script>
    <style>
        .sticky-banner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000; 
        }

        :root {
            --error-background: rgba(255, 71, 87, 0.6);
            --error-backdrop-filter: blur(15px);
            --error-border: rgba(255, 255, 255, 0.2);
            --error-text-primary: #FFFFFF;
            --error-text-secondary: rgba(255, 255, 255, 0.85);
        }

        .matsfx-error-notice {
            position: relative;
            background-color: var(--error-background);
            backdrop-filter: var(--error-backdrop-filter);
            -webkit-backdrop-filter: var(--error-backdrop-filter);
            color: var(--error-text-primary);
            text-align: center;
            left: 30%;
            padding: 2.5rem;
            border-radius: 0px 0px 20px 20px;
            border: 1px solid var(--error-border);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            width: 90%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .matsfx-error-notice:hover {
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .matsfx-error-heading {
            font-size: 3rem;
            font-weight: 800;
            color: var(--error-text-primary);
            margin-bottom: 1rem;
            letter-spacing: -1px;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .matsfx-error-text {
            font-size: 1.25rem;
            color: var(--error-text-secondary);
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .close-banner {
            position: absolute;
            top: 10px;
            right: 10px;
            color: var(--error-text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            background: none;
            border: none;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .close-banner:hover {
            opacity: 1;
        }

        .banner-hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .container, .artist-songs {
                padding-bottom: 50%;
                min-height: 200px;
            }

            .matsfx-error-notice {
                position: absolute;
                left: 0%;
                top: -15px;
                padding: 1.5rem;
                margin: 1rem;
                width: calc(100% - 2rem);
            }

            .matsfx-error-heading {
                font-size: 1.35rem;
            }

            .matsfx-error-text {
                font-size: 0.90rem;
            }
        }
    </style>

    <?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <?php
    require_once 'includes/header.php';
    ?>
   <!--
    <div class="sticky-banner" id="stickyBanner">
        <div class="matsfx-error-notice">
            <button class="close-banner" onclick="closeStickyBanner()" aria-label="Close Banner">
                &#10005;
            </button>
            <div class="matsfx-error-heading">Important Notice</div>
            <div class="matsfx-error-text">We're aware of the current issue that you can't update your profile picture or upload songs. We're currently working to fix this issue.</div>
        </div>
    </div>  -->

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
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    require_once 'includes/player.php'
    ?>

    <script src="js/index.js"></script>
    <script src="js/search.js"></script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6530871411657748"
        crossorigin="anonymous"></script>
</body>
</html>
