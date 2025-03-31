<?php
require_once 'config/config.php';
require_once 'themes/theme-handler.php';

$currentUserId = $_SESSION['user_id'] ?? null;
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

function getArtistProfileData($artistName) {
    global $pdo;
    try {
        $query = "SELECT profile_picture, profile_banner FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":username", $artistName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'profile_picture' => $result && $result['profile_picture'] ? $result['profile_picture'] : 'defaults/default-profile.jpg',
            'banner_image' => $result && $result['profile_banner'] ? $result['profile_banner'] : 'defaults/default-banner.jpg'
        ];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'profile_picture' => 'defaults/default-profile.jpg',
            'banner_image' => 'defaults/default-banner.jpg'
        ];
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
            
            // Add notification for new follow
            require_once __DIR__ . '/../notifications.php';
            
            // Get follower username
            $usernameStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
            $usernameStmt->execute([':user_id' => $currentUserId]);
            $username = $usernameStmt->fetchColumn();
            
            $message = $username . " started following you";
            createNotification($profileUserId, NOTIFICATION_FOLLOW, $message, $currentUserId);
            
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

// Fetch artist data
$artistData = checkArtistExists($artist);
$profileUserId = $artistData['user_id'] ?? null;

// Get artist profile information
$artistImages = getArtistProfileData($artist);
$profilePicture = $artistImages['profile_picture'];
$bannerImage = $artistImages['banner_image'];
$artistBio = getArtistBio($artist);

// Get OG image
$ogImage = $profilePicture !== 'defaults/default-profile.jpg' 
    ? $profilePicture 
    : 'app_logos/matsfx_logo.png';

// Get songs
$songsData = $artistData ? getArtistSongs($artist) : ['by_album' => [], 'all_songs' => []];

// Get popular songs
$popularSongs = getArtistMostPopularSongs($artist);

// Check if user is following the artist
$isFollowing = false;
if ($currentUserId && $profileUserId) {
    $isFollowing = checkIfFollowing($currentUserId, $profileUserId);
}

// Handle follow/unfollow actions
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

// Display 404 page if artist doesn't exist
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