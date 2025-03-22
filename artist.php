<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'themes/theme-handler.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$profileUserId = $artistData['user_id'] ?? null;

$artist = isset($_GET['name']) ? trim(urldecode($_GET['name'])) : null;

if (!$artist) {
    header("Location: index");
    exit();
}

if (!isset($pdo)) {
    error_log("Database connection is not initialized");
    die("Unable to connect to database. Please check the configuration.");
}

function getArtistBio($artistName) {
    global $pdo;
    try {
        $query = "SELECT bio FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['bio'] ? $result['bio'] : "Listen to $artistName on matSFX - The new way to listen with Joy!";
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'matSFX - The new way to listen with Joy!';
    }
}

function getArtistProfilePicture($artistName) {
    global $pdo;
    try {
        $query = "SELECT profile_picture FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['profile_picture'] ? $result['profile_picture'] : 'defaults/default-profile.jpg';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'defaults/default-profile.jpg';
    }
}

function checkIfFollowing($currentUserId, $profileUserId) {
    global $pdo;
    try {
        if ($currentUserId === $profileUserId) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
        $stmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() ? true : false;
    } catch (PDOException $e) {
        error_log("Database error in checkIfFollowing: " . $e->getMessage());
        return false;
    }
}

function followOrUnfollow($currentUserId, $profileUserId, $action) {
	global $pdo;
	try {
		if ($currentUserId === $profileUserId) {
			return false;
		}

		$pdo->beginTransaction();

		if ($action === 'follow') {
			$checkStmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
			$checkStmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
			$checkStmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
			$checkStmt->execute();
			
			if ($checkStmt->fetchColumn()) {
				$pdo->rollBack();
				return false;
			}

			// Insert follow relationship
			$stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, follow_date) VALUES (:follower_id, :followed_id, NOW())");
			$stmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
			$stmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
			$stmt->execute();

			// Calculate and update correct follower count
			$countStmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = :user_id");
			$countStmt->bindValue(':user_id', $profileUserId, PDO::PARAM_INT);
			$countStmt->execute();
			$followerCount = $countStmt->fetchColumn();

			$updateCount = $pdo->prepare("UPDATE users SET follower_count = :count WHERE user_id = :user_id");
			$updateCount->bindValue(':count', $followerCount, PDO::PARAM_INT);
			$updateCount->bindValue(':user_id', $profileUserId, PDO::PARAM_INT);
			$updateCount->execute();
        } else {
            // Remove follow relationship
            $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
            $stmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
            $stmt->execute();

            // Decrement follower count
            $updateCount = $pdo->prepare("UPDATE users SET follower_count = GREATEST(follower_count - 1, 0) WHERE user_id = :user_id");
            $updateCount->bindValue(':user_id', $profileUserId, PDO::PARAM_INT);
            $updateCount->execute();
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error in followOrUnfollow: " . $e->getMessage());
        return false;
    }
}

// follower count for the artist
function getFollowerCount($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT follower_count FROM users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Database error in getFollowerCount: " . $e->getMessage());
        return 0;
    }
}

function getArtistMostPopularSongs($artistName, $limit = 5) {
    global $pdo;
    try {
        // Get most liked songs from this artist
        $stmt = $pdo->prepare("
            SELECT s.*, COUNT(l.song_id) as like_count 
            FROM songs s
            LEFT JOIN likes l ON s.song_id = l.song_id
            WHERE s.artist = :artist
            GROUP BY s.song_id
            ORDER BY like_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':artist', $artistName, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getArtistMostPopularSongs: " . $e->getMessage());
        return [];
    }
}

// Get the most popular songs for the current artist
$popularSongs = getArtistMostPopularSongs($artist);

function getArtistSongs($artistName) {
    global $pdo;
    try {
        // Get songs and order by album, then upload_date
        $stmt = $pdo->prepare("SELECT * FROM songs WHERE artist = :artist ORDER BY album, upload_date DESC");
        $stmt->bindValue(':artist', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $allSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize songs by album
        $songsByAlbum = [];
        $singleTracks = [];
        
        foreach ($allSongs as $song) {
            if (!empty($song['album'])) {
                $albumName = $song['album'];
                if (!isset($songsByAlbum[$albumName])) {
                    $songsByAlbum[$albumName] = [
                        'album_name' => $albumName,
                        'cover_art' => $song['cover_art'] ?? 'defaults/default-cover.jpg',
                        'songs' => []
                    ];
                }
                $songsByAlbum[$albumName]['songs'][] = $song;
            } else {
                // Group songs without album as singles
                $singleTracks[] = $song;
            }
        }
        
        // If there are singles, add them as a special group
        if (!empty($singleTracks)) {
            $songsByAlbum['Singles'] = [
                'album_name' => 'Singles',
                'cover_art' => !empty($singleTracks[0]['cover_art']) ? $singleTracks[0]['cover_art'] : 'defaults/default-cover.jpg',
                'songs' => $singleTracks
            ];
        }
        
        return [
            'by_album' => $songsByAlbum,
            'all_songs' => $allSongs
        ];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'by_album' => [],
            'all_songs' => []
        ];
    }
}

function checkArtistExists($artistName) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT *, is_verified, bio, is_developer FROM users WHERE username = :username");
        $stmt->bindValue(':username', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function getUserBadges($userId) {
	global $pdo;
	try {
		$userCheck = $pdo->prepare("SELECT is_admin, is_verified FROM users WHERE user_id = :user_id");
		$userCheck->execute(['user_id' => $userId]);
		$userData = $userCheck->fetch(PDO::FETCH_ASSOC);

		$whereClause = "";
		if ($userData && $userData['is_admin'] == 1 && $userData['is_verified'] == 1) {
			$whereClause = "AND b.badge_id != '2'";
		}

		$query = "SELECT b.* 
				  FROM badges b
				  INNER JOIN user_badges ub ON b.badge_id = ub.badge_id
				  WHERE ub.user_id = :user_id
				  $whereClause
				  ORDER BY 
					CASE 
						WHEN b.badge_name LIKE '%Admin%' THEN 1
						WHEN b.badge_name LIKE '%Verified%' THEN 2
						ELSE 3
					END,
					b.badge_name";
				  
		$stmt = $pdo->prepare($query);
		$stmt->execute(['user_id' => $userId]);
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		error_log("Database error in getUserBadges: " . $e->getMessage());
		return [];
	}
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9-_.]/', '', $filename);
}

$profilePicture = getArtistProfilePicture($artist);
$artistBio = getArtistBio($artist);

$ogImage = $profilePicture !== 'defaults/default-profile.jpg' 
    ? $profilePicture 
    : 'app_logos/matsfx_logo.png';

$currentUserId = $_SESSION['user_id'] ?? null;
$artistData = checkArtistExists($artist);
$profileUserId = $artistData['user_id'] ?? null;
$songsData = $artistData ? getArtistSongs($artist) : ['by_album' => [], 'all_songs' => []];

$isFollowing = false;
if ($currentUserId && $profileUserId) {
    $isFollowing = checkIfFollowing($currentUserId, $profileUserId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_action'], $currentUserId, $profileUserId)) {
    $action = $_POST['follow_action'] === 'follow' ? 'follow' : 'unfollow';
    
    $result = followOrUnfollow($currentUserId, $profileUserId, $action);
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        if ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update follow status']);
        }
        exit();
    }

    header("Location: ?name=" . urlencode($artist));
    exit();
}

if (!$artistData) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Not Found - matSFX</title>
		<link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    	<link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">

		<!-- links -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
		<link rel="stylesheet" href="css/style.css">
		<script src="js/share-button.js"></script>
		<?php outputChristmasThemeCSS(); ?>
    </head>
    <body>

	<?php require_once 'includes/header.php'; ?>
		
        <div class="error-user-container">
            <h1 class="error-user-heading">User Not Found</h1>
            <p class="error-user-text">The requested user does not exist.</p>
            <a href="../" class="error-user-button">Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?">
    <meta name="og:title" content="<?php echo htmlspecialchars($artist); ?> on matSFX">
	<meta property="og:description" content="<?php echo htmlspecialchars($artistBio); ?>">
    <meta property="og:image" content="https://alpha.matsfx.com/<?php echo htmlspecialchars($profilePicture ?: '/defaults/default-profile.jpg', ENT_QUOTES, 'UTF-8'); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="https://alpha.matsfx.com/song?song_id=3">
	<link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title><?php echo htmlspecialchars($artist); ?> - matSFX</title>

	<!-- links -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/share-button.css">
	<link rel="stylesheet" href="css/artist/albumSection.css">
	<link rel="stylesheet" href="css/artist/badges.css">
	<link rel="stylesheet" href="css/artist/basic.css">
	<link rel="stylesheet" href="css/artist/followButton.css">
	<link rel="stylesheet" href="css/artist/popularSongs.css">
	<link rel="stylesheet" href="css/artist/profileStats.css">
	<link rel="stylesheet" href="css/artist/songCard.css">
	<link rel="stylesheet" href="css/artist/userError.css">

	<script src="js/share-button.js"></script>
	<?php outputChristmasThemeCSS(); ?>
</head>
<body>
	
	<?php require_once 'includes/header.php'; ?>
	
	<div class="artist-profile">
	    <div class="profile-header">
	        <div class="profile-content">
	            <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
	                 alt="Artist" 
	                 class="profile-image">
	            
	            <div class="profile-info">
					<h1 class="profile-name">
						<?php echo htmlspecialchars($artist); ?>
						<?php 
						$userBadges = getUserBadges($artistData['user_id']);
						foreach ($userBadges as $badge): ?>
							<img src="<?php echo htmlspecialchars($badge['image_path']); ?>" 
								alt="<?php echo htmlspecialchars($badge['alt_text']); ?>" 
								class="<?php echo htmlspecialchars($badge['css_class']); ?>" 
								title="<?php echo htmlspecialchars($badge['title_text']); ?>">
						<?php endforeach; ?>
					</h1>
	
	                <?php if (!empty($artistData['bio'])): ?>
	                    <p><?php echo nl2br(htmlspecialchars($artistData['bio'])); ?></p>
	                <?php endif; ?>
	
	                <div class="profile-stats">
						<span><?php echo count($songsData['all_songs']); ?> Songs</span>
						<span><?php echo getFollowerCount($profileUserId); ?> Followers</span>
                        <span><?php echo count($songsData['by_album']); ?> Albums</span>
					</div>
	
	                <?php if ($currentUserId): ?>
	                    <form id="follow-form" method="POST">
	                        <button type="button" 
	                                id="follow-btn" 
	                                class="follow-button <?php echo $isFollowing ? 'unfollow-button' : ''; ?>">
	                            <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
	                        </button>
	                    </form>
	                <?php else: ?>
	                    <p>Login or Signup to follow this artist!</p>
	                <?php endif; ?>
	            </div>
	        </div>
	    </div>
	</div>

	<?php if (!empty($popularSongs)): ?>
	<div class="popular-container">
		<h2 class="section-title">Popular</h2>
		<div class="popular-songs">
			<?php foreach ($popularSongs as $index => $song): ?>
				<div class="song-row popular-song-row" 
					onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
					data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
					data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
					data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
					<div class="song-number"><?php echo $index + 1; ?></div>
					<div class="song-image">
						<img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" 
							alt="Song Cover" class="popular-song-image">
					</div>
					<div class="song-info">
						<div class="song-row-title"><?php echo htmlspecialchars($song['title']); ?></div>
					</div>
					<div class="song-action-buttons">
						<?php 
						$songId = $song['song_id'];
						require 'includes/like_button.php';
						require 'includes/share_button.php';
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
        
	<?php
		if (empty($songsData['all_songs'])): ?>
			<p>No songs uploaded yet.</p>
		<?php else: ?>
			<?php 
			$albums = [];
			$singles = [];
			
			$songsByAlbum = [];
			foreach ($songsData['all_songs'] as $song) {
				if (!empty($song['album'])) {
					$albumName = $song['album'];
					if (!isset($songsByAlbum[$albumName])) {
						$songsByAlbum[$albumName] = [];
					}
					$songsByAlbum[$albumName][] = $song;
				} else {
					$singles[] = $song;
				}
			}
			
			foreach ($songsByAlbum as $albumName => $albumSongs) {
				if (count($albumSongs) >= 2) {
					$albums[$albumName] = [
						'album_name' => $albumName,
						'cover_art' => $albumSongs[0]['cover_art'] ?? 'defaults/default-cover.jpg',
						'songs' => $albumSongs
					];
				} else {
					$singles = array_merge($singles, $albumSongs);
				}
			}
			?>
			
			<!-- Display Albums -->
			<?php if (!empty($albums)): ?>
				<div class="albums-container">
					<h2 class="section-title">Albums</h2>
					<?php foreach ($albums as $albumName => $albumData): ?>
						<div class="album-section">
							<div class="album-header">
								<img src="<?php echo htmlspecialchars($albumData['cover_art']); ?>" 
									alt="Album Cover" 
									class="album-cover">
								<div class="album-info">
									<div class="album-title"><?php echo htmlspecialchars($albumData['album_name']); ?></div>
									<div class="album-details"><?php echo count($albumData['songs']); ?> songs</div>
								</div>
							</div>
							
							<div class="album-songs">
								<?php foreach ($albumData['songs'] as $index => $song): ?>
									<div class="song-row" 
										onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
										data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
										data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
										data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
										<div class="song-number"><?php echo $index + 1; ?></div>
										<div class="song-info">
											<div class="song-row-title"><?php echo htmlspecialchars($song['title']); ?></div>
											<div class="song-row-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
										</div>
										<div class="song-action-buttons">
											<?php 
											require 'includes/like_button.php';
											?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			
			<!-- Display Singles -->
			<?php if (!empty($singles)): ?>
				<div class="singles-container">
					<h2 class="section-title">Singles</h2>
					<div class="song-cards">
						<?php foreach ($singles as $song): ?>
							<div class="song-card" 
								onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
								data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
								data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>"
								data-song-id="<?php echo htmlspecialchars($song['song_id']); ?>">
								<img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" 
									alt="Song Cover" 
									class="song-card-image">
								<div class="song-card-info">
									<div class="song-card-title"><?php echo htmlspecialchars($song['title']); ?></div>
									<div class="song-card-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
									<div class="song-card-actions">
										<?php 
										$songId = $song['song_id'];
										require 'includes/like_button.php';
										require 'includes/share_button.php';
										?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<div class="player-spacer"></div>
	<?php require_once 'includes/player.php'; ?>
	
	<script src="js/index.js"></script>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
	    const followBtn = document.getElementById('follow-btn');
	
	    if (followBtn) {
	        followBtn.addEventListener('click', function() {
	            const action = followBtn.textContent.toLowerCase() === 'follow' ? 'follow' : 'unfollow';
	
	            fetch('', {
	                method: 'POST',
	                headers: {
	                    'Content-Type': 'application/x-www-form-urlencoded',
	                    'X-Requested-With': 'XMLHttpRequest'
	                },
	                body: `follow_action=${action}`
	            })
	            .then(response => {
	                if (!response.ok) {
	                    throw new Error(`HTTP error! status: ${response.status}`);
	                }
	                return response.json();
	            })
	            .then(data => {
	                if (data.status === 'success') {
	                    if (action === 'follow') {
	                        followBtn.innerHTML = `
	                            <span class="follow-text">Following</span>
	                        `;
	                        followBtn.classList.add('following');
	                        followBtn.classList.remove('unfollow-button');
	                    } else {
	                        followBtn.innerHTML = `<span class="follow-text">Follow</span>`;
	                        followBtn.classList.remove('following');
	                        followBtn.classList.add('unfollow-button');
	                    }
	                } else {
	                    throw new Error(data.message || 'Failed to update follow status');
	                }
	            })
	            .catch(error => {
	                console.error('Error:', error);
	                alert('Failed to update follow status');
	            });
	        });
	    }

        // Add functionality to expand/collapse album sections
        const albumHeaders = document.querySelectorAll('.album-header');
        albumHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const songsSection = this.nextElementSibling;
                if (songsSection.style.display === 'none') {
                    songsSection.style.display = 'block';
                } else {
                    songsSection.style.display = 'none';
                }
            });
        });
	});

	const albumCovers = document.querySelectorAll('.album-cover');
	albumCovers.forEach(cover => {
		cover.addEventListener('dblclick', function() {
			const albumSection = this.closest('.album-section');
			if (albumSection) {
				// Prepare the album's songs for playback
				const songs = albumSection.querySelectorAll('.song-row');
				if (songs.length > 0) {
					// Get the first song and play it
					const firstSong = songs[0];
					const filePath = firstSong.getAttribute('data-song-file') || 
									firstSong.getAttribute('onclick').toString().match(/'([^']+)'/)[1];
					
					playSong(filePath, firstSong);
				}
			}
		});
	});

	// Add a Play All button for each album
	const albumHeaders = document.querySelectorAll('.album-header');
	albumHeaders.forEach(header => {
		// Create a play button if it doesn't exist
		if (!header.querySelector('.album-play-button')) {
			const playButton = document.createElement('button');
			playButton.className = 'album-play-button';
			playButton.innerHTML = '<i class="fas fa-play"></i> Play All';
			playButton.style.marginLeft = '10px';
			playButton.style.padding = '5px 10px';
			playButton.style.borderRadius = '4px';
			playButton.style.backgroundColor = 'var(--primary-color)';
			playButton.style.color = 'white';
			playButton.style.border = 'none';
			playButton.style.cursor = 'pointer';
			
			// Add hover effect
			playButton.addEventListener('mouseenter', function() {
				this.style.backgroundColor = 'var(--primary-hover)';
			});
			
			playButton.addEventListener('mouseleave', function() {
				this.style.backgroundColor = 'var(--primary-color)';
			});
			
			// Add click handler
			playButton.addEventListener('click', function(e) {
				e.stopPropagation(); // Don't trigger album collapse/expand
				const albumSection = this.closest('.album-section');
				if (albumSection) {
					const songs = albumSection.querySelectorAll('.song-row');
					if (songs.length > 0) {
						const firstSong = songs[0];
						const filePath = firstSong.getAttribute('data-song-file') || 
										firstSong.getAttribute('onclick').toString().match(/'([^']+)'/)[1];
						
						playSong(filePath, firstSong);
					}
				}
			});
			
			header.querySelector('.album-info').appendChild(playButton);
		}
	});
	</script>
</body>
</html>