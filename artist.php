<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'themes/theme-handler.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$profileUserId = $artistData['user_id'] ?? null;

$artist = isset($_GET['name']) ? trim(urldecode($_GET['name'])) : null;

if (!$artist) {
    header("Location: index");
    exit();
}

function getArtistBio($artistName) {
    global $conn;
    try {
        $query = "SELECT bio FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['bio'] ? $result['bio'] : 'Listen to ' . $artistName . ' on matSFX - The new way to listen with Joy!';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'matSFX - The new way to listen with Joy!';
    }
}

function getArtistProfilePicture($artistName) {
    global $conn;
    try {
        $query = "SELECT profile_picture FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['profile_picture'] ? $result['profile_picture'] : 'defaults/default-profile.jpg';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'defaults/default-profile.jpg';
    }
}

$profilePicture = getArtistProfilePicture($artist);
$artistBio = getArtistBio($artist);

$ogImage = $profilePicture !== 'defaults/default-profile.jpg' 
    ? $profilePicture 
    : 'app_logos/matsfx_logo.png';


function checkIfFollowing($currentUserId, $profileUserId) {
    global $conn;
    try {
        // Prevent self-following
        if ($currentUserId === $profileUserId) {
            return false;
        }

        $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
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
    global $conn;
    try {
        // prevent self-following
        if ($currentUserId === $profileUserId) {
            return false;
        }

        if ($action === 'follow') {
            // check if already following
            $checkStmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
            $checkStmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
            $checkStmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn()) {
                return false; // already following
            }

            // insert a new follow
            $stmt = $conn->prepare("INSERT INTO followers (follower_id, followed_id, follow_date) VALUES (:follower_id, :followed_id, NOW())");
            $stmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
        } else {
            // remove the follow
            $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
            $stmt->bindValue(':follower_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindValue(':followed_id', $profileUserId, PDO::PARAM_INT);
        }
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error in followOrUnfollow: " . $e->getMessage());
        return false;
    }
}

$currentUserId = $_SESSION['user_id'] ?? null;
$profileUserId = $artistData['user_id'] ?? null;

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


function getArtistSongs($artistName) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM songs WHERE artist = :artist ORDER BY upload_date DESC");
        $stmt->bindValue(':artist', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function checkArtistExists($artistName) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT *, is_verified, bio, is_developer FROM users WHERE username = :username");
        $stmt->bindValue(':username', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9-_.]/', '', $filename);
}

$artistData = checkArtistExists($artist);
$profileUserId = $artistData['user_id'] ?? null;
$songs = $artistData ? getArtistSongs($artist) : [];
$profilePicture = getArtistProfilePicture($artist);

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
        <link rel="stylesheet" href="css/style.css">
		<link rel="stylesheet" href="css/share-button.js">
		<script src="js/share-button.js" defer></script>
		<link rel="stylesheet" href="css/style.css">
		<link rel="stylesheet" href="css/player-style.css">
		<link rel="stylesheet" href="css/index-artistsection.css">
		<link rel="stylesheet" href="css/share-button.css">
		<link rel="stylesheet" href="css/navbar.css">
		<script src="js/share-button.js"></script>
        <style>
		.error-user-container {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			height: 91vh;
			background-color: var(--dark-bg);
			text-align: center;
			padding: 20px;
			box-sizing: border-box;
		}
		.error-user-heading {
			font-size: 48px;
			font-weight: bold;
			color: var(--primary-color);
			margin-bottom: 16px;
		}
		
		.error-user-text {
			font-size: 20px;
			color: var(--gray-text);
			margin-bottom: 32px;
		}
		
		.error-user-button {
			display: inline-block;
			text-decoration: none;
			padding: 12px 24px;
			font-size: 18px;
			font-weight: 600;
			color: var(--light-text);
			background-color: var(--primary-color);
			border-radius: var(--border-radius);
			box-shadow: var(--shadow-sm);
			transition: var(--transition);
		}
		
		.error-user-button:hover {
			background-color: var(--primary-hover);
			box-shadow: var(--shadow-md);
		}
		
		.follow-button {
			position: absolute;
			top: 22%;
			right: 400px;
			padding: 10px 20px;
			font-size: 16px;
			font-weight: bold;
			color: #fff;
			background-color: #007bff;
			border: none;
			border-radius: 5px;
			cursor: pointer;
			transition: background-color 0.3s;
		}
		
		.follow-button:hover {
			background-color: #0056b3;
		}
		
		.unfollow-button {
			background-color: #dc3545;
		}
		
		.unfollow-button:hover {
			background-color: #a71d2a;
		}
        </style>
		
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
	<link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app_logos/matsfx_logo.png">
    <title><?php echo htmlspecialchars($artist); ?> - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/player-style.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/player-style.css">
	<link rel="stylesheet" href="css/index-artistsection.css">
	<link rel="stylesheet" href="css/share-button.css">
	<link rel="stylesheet" href="css/navbar.css">
	<script src="js/share-button.js"></script>
	<style>
	        .verified-badge {
	            width: 20px;
	            height: 20px;
	            margin-left: 8px;
	            vertical-align: middle;
	        }
			
		.developer-badge, .helper-badge, .donator-badge {
	            width: 20px;
	            height: 20px;
	            
	            vertical-align: middle;
	        }		
	
	        .designer-badge {
	            width: 27px;
	            height: 27px;
	                
	            vertical-align: middle;
	        }
	    			
	    	.follow-button {
	    	    position: relative;
	    	    padding: 10px 20px;
	    	    font-size: 16px;
	    	    font-weight: bold;
	    	    color: #fff;
	    	    background-color: #007bff;
	    	    border: none;
	    	    border-radius: 8px;
	    	    cursor: pointer;
	    	    overflow: hidden;
	    	    transition: all 0.3s ease;
	    	    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
	    	    transform-style: preserve-3d;
	    	}
	    
	    	.follow-button:before {
	    	    content: '';
	    	    position: absolute;
	    	    top: 0;
	    	    left: -100%;
	    	    width: 100%;
	    	    height: 100%;
	    	    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
	    	    transition: all 0.6s ease;
	    	}
	    
	    	.follow-button:hover:before {
	    	    left: 100%;
	    	}
	    
	    	.follow-button:hover {
	    	    transform: scale(1.05) perspective(1px);
	    	    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
	    	}
	    
	    	.follow-button.unfollow-button {
	    	    background-color: #dc3545;
	    	}
	    
	    	.follow-button.following {
	    	    background-color: #28a745;
	    	}
	    
	    	.follow-button .follow-text {
	    	    position: relative;
	    	    z-index: 1;
	    	}
	    
	    	.follow-button .follow-icon {
	    	    margin-right: 8px;
	    	    position: relative;
	    	    z-index: 1;
	    	}
        </style>
	
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
	                    
	                    <?php if ($artistData['is_admin'] == 1): ?>
	                        <img src="app-images/admin-badge.png" 
	                             alt="Admin" 
	                             class="verified-badge" 
	                             title="Admin">
	                    <?php elseif ($artistData['is_verified'] == 1): ?>
	                        <img src="app-images/verified-badge.png" 
	                             alt="Verified" 
	                             class="verified-badge" 
	                             title="Verified Artist">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_developer'] == 1): ?>
	                        <img src="app-images/developer-badge.png" 
	                             alt="Developer" 
	                             class="developer-badge" 
	                             title="Developer">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_designer'] == 1): ?>
	                        <img src="app-images/designer-badge.png" 
	                             alt="Designer" 
	                             class="designer-badge" 
	                             title="Designer">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_helper'] == 1): ?>
	                        <img src="app-images/helper-badge.png" 
	                             alt="Helper" 
	                             class="helper-badge" 
	                             title="Helper">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_donator'] == 1): ?>
	                        <img src="app-images/donator-badge.png" 
	                             alt="Donator" 
	                             class="donator-badge" 
	                             title="Donator">
	                    <?php endif; ?>
	                </h1>
	
	                <?php if (!empty($artistData['bio'])): ?>
	                    <p><?php echo nl2br(htmlspecialchars($artistData['bio'])); ?></p>
	                <?php endif; ?>
	
	                <div class="profile-stats">
	                    <span><?php echo count($songs); ?> Songs</span>
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
        
	<div class="artist-songs" style="padding-bottom: 10%;">
	    <!-- <div class="songs-header">
	        <h2 class="section-title"></h2>
	    </div> -->
	    <div class="music-grid">
	        <?php if (empty($songs)): ?>
	            <p>No songs uploaded yet.</p>
	        <?php else: ?>
	            <?php foreach ($songs as $song): ?>
	                <div class="song-card" 
	                     onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)"
	                     data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
	                     data-song-artist="<?php echo htmlspecialchars($song['artist']); ?>">
	                    <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" 
	                         alt="Cover Art" 
	                         class="cover-art">
	                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
	                    <div class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
	                    <div class="song-controls"></div>
	                </div>
	            <?php endforeach; ?>
	        <?php endif; ?>
	    </div>
	</div>
	
	<div id="errorContainer"></div>
	
	<div class="player">
	    <div class="player-container">
	        <div class="song-info">
	            <img id="player-album-art" 
	                 src="" 
	                 alt="Album Art" 
	                 class="album-art" 
	                 onerror="this.src='defaults/default-cover.jpg'">
	            <div class="track-info">
	                <h3 id="songTitle" class="track-name"></h3>
	                <div id="artistName" class="artist-name"></div>
	            </div>
	        </div>
	        
	        <div class="player-controls">
	            <div class="control-buttons">
	                <button onclick="previousTrack()" aria-label="Previous Track">
	                    <i class="fas fa-step-backward"></i>
	                </button>
	                <button onclick="playPause()" id="playPauseBtn" aria-label="Play/Pause">
	                    <i class="fas fa-play"></i>
	                </button>
	                <button onclick="nextTrack()" aria-label="Next Track">
	                    <i class="fas fa-step-forward"></i>
	                </button>
	                <button onclick="toggleLoop()" id="loopBtn" aria-label="Loop Track">
	                    <svg xmlns="http://www.w3.org/2000/svg" 
	                         viewBox="0 0 24 24" 
	                         width="60" 
	                         height="60" 
	                         fill="none" 
	                         stroke="currentColor" 
	                         stroke-width="2" 
	                         stroke-linecap="round" 
	                         stroke-linejoin="round">
	                        <path d="M3 12c0-3.866 3.134-7 7-7h6.5"/>
	                        <polyline points="14 2 17 5 14 8"/>
	                        <path d="M21 12c0 3.866-3.134 7-7 7H7.5"/>
	                        <polyline points="10 22 7 19 10 16"/>
	                    </svg>
	                </button>
	            </div>
	            <div class="progress-container">
	                <span id="currentTime">0:00</span>
	                <input type="range" 
	                       id="progress" 
	                       value="0" 
	                       max="100" 
	                       class="slider" 
	                       aria-label="Song Progress">
	                <span id="duration">0:00</span>
	            </div>
	        </div>
	        
	        <div class="volume-control">
	            <i class="fas fa-volume-up volume-icon" id="volumeIcon"></i>
	            <input type="range" 
	                   id="volume" 
	                   min="0" 
	                   max="1" 
	                   step="0.01" 
	                   value="1" 
	                   class="volume-slider" 
	                   aria-label="Volume Control">
	        </div>
	    </div>
	</div>
	
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
	                            <span class="follow-icon">➕</span>
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
	});
	</script>
</body>
</html>
