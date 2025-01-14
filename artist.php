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

function getArtistSongs($artistName) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs WHERE artist = :artist ORDER BY upload_date DESC");
        $stmt->bindValue(':artist', $artistName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
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
$songs = $artistData ? getArtistSongs($artist) : [];

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
	<link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title><?php echo htmlspecialchars($artist); ?> - matSFX</title>

	<!-- links -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
			
			.developer-badge, .helper-badge, .donator-badge, .designer-badge {
	            width: 20px;
	            height: 20px;
	            
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

			.profile-stats span {
				margin-right: 20px;
				color: var(--gray-text);
				font-size: 0.9em;
			}

			.profile-stats span:last-child {
				margin-right: 0;
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
	                        <img src="app_images/admin-badge.png" 
	                             alt="Admin" 
	                             class="verified-badge" 
	                             title="Admin">
	                    <?php elseif ($artistData['is_verified'] == 1): ?>
	                        <img src="app_images/verified-badge.png" 
	                             alt="Verified" 
	                             class="verified-badge" 
	                             title="Verified Artist">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_developer'] == 1): ?>
	                        <img src="app_images/developer-badge.png" 
	                             alt="Developer" 
	                             class="developer-badge" 
	                             title="Developer">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_designer'] == 1): ?>
	                        <img src="app_images/designer-badge.png" 
	                             alt="Designer" 
	                             class="designer-badge" 
	                             title="Designer">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_helper'] == 1): ?>
	                        <img src="app_images/helper-badge.png" 
	                             alt="Helper" 
	                             class="helper-badge" 
	                             title="Helper">
	                    <?php endif; ?>
	                    
	                    <?php if ($artistData['is_donator'] == 1): ?>
	                        <img src="app_images/donator-badge.png" 
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
						<span><?php echo getFollowerCount($profileUserId); ?> Followers</span>
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
	                    <?php
						require 'includes/like_button.php';
						?>
	                </div>
	            <?php endforeach; ?>
	        <?php endif; ?>
	    </div>
	</div>
	
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
	});
	</script>
</body>
</html>
