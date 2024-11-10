<?php
require_once 'config.php';
require_once 'music_handlers.php';
require_once 'user_handlers.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$userSongs = getUserSongs($user_id);

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$userSongs = getUserSongs($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    if (isset($_POST['update_profile'])) {
        $result = updateProfile($user_id, $_POST, $_FILES['profile_picture'] ?? null);
        $message = $result['success'] ? "Profile updated successfully!" : "Error: " . $result['error'];
    }
    
    if (isset($_POST['update_password'])) {
        $result = updatePassword($user_id, $_POST['current_password'], $_POST['new_password'], $_POST['confirm_password']);
        $message = $result['success'] ? "Password updated successfully!" : "Error: " . $result['error'];
    }
    
    if (isset($_POST['delete_song'])) {
        $result = deleteSong($user_id, $_POST['song_id']);
        $message = $result['success'] ? "Song deleted successfully!" : "Error: " . $result['error'];
        if ($result['success']) {
            $userSongs = getUserSongs($user_id);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="upload.php">Upload</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="settings-container">
        <?php if (isset($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-section">
            <h2>Profile Settings</h2>
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'default-profile.jpg'); ?>" 
                 alt="Profile Picture" class="profile-picture">
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                </div>
                
                <button type="submit" name="update_profile" class="button">Update Profile</button>
            </form>
        </div>

        <div class="settings-section">
            <h2>Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="update_password" class="button">Update Password</button>
            </form>
        </div>

        <div class="settings-section">
            <h2>My Uploaded Songs</h2>
            <ul class="songs-list">
                <?php foreach ($userSongs as $song): ?>
                    <li class="song-item">
                        <div>
                            <strong><?php echo htmlspecialchars($song['title']); ?></strong> - 
                            <?php echo htmlspecialchars($song['artist']); ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="song_id" value="<?php echo $song['song_id']; ?>">
                            <button type="submit" name="delete_song" class="button button-delete"
                                    onclick="return confirm('Are you sure you want to delete this song?')">
                                Delete
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>