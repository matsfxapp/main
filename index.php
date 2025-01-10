<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'music_handlers.php';
require_once 'search_handler.php';

/*
// only activate for maintenance
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: maintenance');
    exit();
}

function isAdmin($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['is_admin'] == 1;
}

*/

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<meta property="og:image" content="app_logos/matsfx_logo.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
	<link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title>matSFX - Music for everyone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/player-style.css">
	<link rel="stylesheet" href="css/index-artistsection.css">
	<link rel="stylesheet" href="css/share-button.css">
	<link rel="stylesheet" href="css/navbar.css">
	<script src="js/share-button.js"></script>
	
	<?php outputChristmasThemeCSS(); ?>
</head>
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
            <div class="matsfx-error-text">We're aware of the current issue where songs aren't playing when clicked/tapped. Our development team is working on a fix.</div>
        </div>
    </div> -->


    <div class="container" style="padding-bottom: 10%;">
        <!-- Top Artists Sections -->
		<?php foreach ($topArtists as $artist): ?>
		<div class="artist-section" style="margin-top: 4rem;">
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
							<!-- <div class="song-controls">
								<button class="btn-share">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
										<polyline points="16 6 12 2 8 6"/>
										<line x1="12" x2="12" y1="2" y2="15"/>
									</svg>
									Share
								</button>
							</div> -->
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<!-- Remaining Songs in Grid -->
	<h2 class="section-title">More Songs</h2>
	<div class="music-grid" style="padding-bottom: 10%;" >
		<?php foreach ($remainingSongs as $song): ?>
			<div class="song-card" onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)">
                    <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist">
                        <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                            <?php echo htmlspecialchars($song['artist']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
	</div>
	
	<div id="errorContainer"></div>
    <div class="player">
        <div class="player-container">
            <div class="song-info">
                <img id="player-album-art" src="" alt="Album Art" class="album-art" onerror="this.src='defaults/default-cover.jpg'">
                <div class="track-info">
                    <h3 id="songTitle" class="track-name"></h3>
                    <div id="artistName" class="artist-name"></div>
                </div>
            </div>
			<div class="player-controls">
				<div class="control-buttons">
					<button onclick="previousTrack()" aria-label="Previous Track"><i class="fas fa-step-backward"></i></button>
					<button onclick="playPause()" id="playPauseBtn" aria-label="Play/Pause"><i class="fas fa-play"></i></button>
					<button onclick="nextTrack()" aria-label="Next Track"><i class="fas fa-step-forward"></i></button>
					<button onclick="toggleLoop()" id="loopBtn" aria-label="Loop Track">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 12c0-3.866 3.134-7 7-7h6.5"/>
						<polyline points="14 2 17 5 14 8"/>
						
						<path d="M21 12c0 3.866-3.134 7-7 7H7.5"/>
						<polyline points="10 22 7 19 10 16"/>
					  </svg>
					</button>
				</div>
                <div class="progress-container">
                    <span id="currentTime">0:00</span>
                    <input type="range" id="progress" value="0" max="100" class="slider" aria-label="Song Progress">
                    <span id="duration">0:00</span>
                </div>
            </div>
            <div class="volume-control">
                <i class="fas fa-volume-up volume-icon" id="volumeIcon"></i>
                <input type="range" id="volume" min="0" max="1" step="0.01" value="1" class="volume-slider" aria-label="Volume Control">
            </div>
        </div>
    </div>

    <script src="js/index.js"></script>
	<script src="js/search.js"></script>
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
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const profilePic = document.getElementById('profilePic');
			const profileMenu = document.getElementById('profileMenu');
			const profileContainer = document.getElementById('profileContainer');

			// Toggle menu when clicking profile picture
			if (profilePic) {
				profilePic.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					profileMenu.classList.toggle('active');
				});
			}

			// Close menu when clicking outside
			document.addEventListener('click', function(e) {
				if (profileMenu && profileMenu.classList.contains('active') && !profileContainer.contains(e.target)) {
					profileMenu.classList.remove('active');
				}
			});

			// Close menu on ESC key
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && profileMenu && profileMenu.classList.contains('active')) {
					profileMenu.classList.remove('active');
				}
			});

			// Initialize Ko-fi widget if it exists
			if (typeof kofiWidgetOverlay !== 'undefined') {
				kofiWidgetOverlay.draw('matsfx', {
					'type': 'floating-chat',
					'floating-chat.donateButton.text': 'Support Us',
					'floating-chat.donateButton.background-color': '#ffffff',
					'floating-chat.donateButton.text-color': '#323842'
				});

				// Move Ko-fi button into menu
				const kofiFrame = document.querySelector('#kofi-iframe-wrapper');
				const kofiContainer = document.querySelector('.kofi-container');
				if (kofiFrame && kofiContainer) {
					kofiContainer.appendChild(kofiFrame);
				}
			}
		});
	</script>
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6530871411657748"
     crossorigin="anonymous"></script>
</body>
</html>