<?php
require_once 'config.php';
require_once 'music_handlers.php';
require_once 'user_handlers.php';

if (!isLoggedIn()) {
    header("Location: login");
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
    
    if (isset($_POST['update_bio'])) {
        $result = updateBio($user_id, $_POST['bio']);
        $message = $result['success'] ? "Bio updated successfully!" : "Error: " . $result['error'];
    }
    
    if (isset($_POST['delete_song'])) {
        $result = deleteSong($user_id, $_POST['song_id']);
        $message = $result['success'] ? "Song deleted successfully!" : "Error: " . $result['error'];
        if ($result['success']) {
            $userSongs = getUserSongs($user_id);
        }
    }
    
    if (isset($_POST['update_song'])) {
        $result = updateSongDetails($user_id, $_POST['song_id'], [
            'title' => $_POST['title'],
            'album' => $_POST['album'],
            'genre' => $_POST['genre'],
        ]);
        $message = $result['success'] ? "Song details updated successfully!" : "Error: " . $result['error'];
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
    <link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app-images/matsfx-logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="../">Home</a>
            <a href="upload">Upload</a>
            <a href="settings">Settings</a>
            <a href="logout">Logout</a>
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
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" 
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

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" maxlength="300" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="button">Update Profile</button>
                <p>Try double clicking the button if its says their was an error updating your account</p>
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
                        <div class="song-details">
                            <div class="song-info">
                                <strong><?php echo htmlspecialchars($song['title']); ?></strong> - 
                                <?php echo htmlspecialchars($song['artist']); ?>
                            </div>
                            <button class="button" 
                                    onclick="toggleEditForm('<?php echo $song['song_id']; ?>')"
                                    style="margin-right: 1rem;">
                                Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="song_id" value="<?php echo $song['song_id']; ?>">
                                <button type="submit" name="delete_song" class="button button-delete"
                                        onclick="return confirm('Are you sure you want to delete this song?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                        
                        <div id="edit-form-<?php echo $song['song_id']; ?>" class="edit-form" style="display: none;">
                            <form method="POST" class="song-edit-form">
                                <input type="hidden" name="song_id" value="<?php echo $song['song_id']; ?>">
                                
                                <div class="form-group">
                                    <label for="title-<?php echo $song['song_id']; ?>">Title</label>
                                    <input type="text" id="title-<?php echo $song['song_id']; ?>" 
                                           name="title" value="<?php echo htmlspecialchars($song['title']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="album-<?php echo $song['song_id']; ?>">Album</label>
                                    <input type="text" id="album-<?php echo $song['song_id']; ?>" 
                                           name="album" value="<?php echo htmlspecialchars($song['album']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="genre-<?php echo $song['song_id']; ?>">Genre</label>
                                    <input type="text" id="genre-<?php echo $song['song_id']; ?>" 
                                           name="genre" value="<?php echo htmlspecialchars($song['genre']); ?>">
                                </div>
                                
                                <button type="submit" name="update_song" class="button">Save Changes</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
    function toggleEditForm(songId) {
        const form = document.getElementById(`edit-form-${songId}`);
        if (form.style.display === 'none') {
            // Hide all other forms first
            document.querySelectorAll('.edit-form').forEach(f => f.style.display = 'none');
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
    </script>
</body>
</html>
