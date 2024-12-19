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
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx-logo-squared.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app_logos/matsfx-logo-squared.png">
    <link rel="stylesheet" href="style.css">
	<style>
		:root {
			--primary-color: #2D7FF9;
			--primary-hover: #1E6AD4;
			--primary-light: rgba(45, 127, 249, 0.1);
			--accent-color: #18BFFF;
			--dark-bg: #0A1220;
			--darker-bg: #060912;
			--card-bg: #111827;
			--card-hover: #1F2937;
			--nav-bg: rgba(17, 24, 39, 0.95);
			--light-text: #FFFFFF;
			--gray-text: #94A3B8;
			--border-color: #1F2937;
			--border-radius: 12px;
			--border-radius-lg: 16px;
			--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			--shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
			--shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
			--shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
		}
		
		.form-group textarea {
			width: 98%;
			padding: 0.875rem 1.25rem;
			border: 2px solid var(--border-color);
			background-color: rgba(255, 255, 255, 0.05);
			color: var(--light-text);
			border-radius: var(--border-radius);
			font-size: 1rem;
			transition: var(--transition);
			min-height: 150px;
			height: 150px;
			resize: vertical;
			font-family: inherit;
		}

		.form-group textarea:focus {
			outline: none;
			border-color: var(--primary-color);
			background-color: rgba(255, 255, 255, 0.08);
			box-shadow: 0 0 0 4px var(--primary-light);
		}
		
		.christmas-toggle-btn {
			position: relative;
			padding: 12px 24px;
			font-size: 16px;
			font-weight: 600;
			color: #fff;
			background: linear-gradient(45deg, #D42426, #2E7D32);
			border: none;
			border-radius: 30px;
			cursor: pointer;
			overflow: hidden;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
		}

		.christmas-toggle-btn::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
			transition: 0.5s;
		}

		.christmas-toggle-btn:hover::before {
			left: 100%;
		}

		.christmas-toggle-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
		}

		/* Toggle Button Container */
		.theme-toggle {
			position: relative;
			display: inline-block;
		}

		.theme-toggle::after {
			content: '🎄';
			position: absolute;
			right: -30px;
			top: 50%;
			transform: translateY(-50%);
			font-size: 24px;
			animation: bounce 2s infinite;
		}

		@keyframes bounce {
			0%, 100% { transform: translateY(-50%); }
			50% { transform: translateY(-70%); }
		}
	</style>
	
	<?php outputChristmasThemeCSS(); ?>
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
					<textarea id="bio" name="bio" maxlength="300" rows="4"><?php 
						echo htmlspecialchars(isset($user['bio']) ? $user['bio'] : '');
					?></textarea>
				</div>

                <button type="submit" name="update_profile" class="button">Update Profile</button>
                <p>Try double clicking the button if its says their was an error updating your account</p>
            </form>
        </div>
		
		<div class="settings-section">
			<h2>Change Theme</h2>
				<form method="POST" class="theme-toggle">
					<button type="submit" name="toggle_christmas_theme" class="christmas-toggle-btn">
						<?php echo isChristmasThemeEnabled() ? '🎄 Disable Christmas Theme' : '🎄 Enable Christmas Theme'; ?>
					</button>
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
	
<script src='https://storage.ko-fi.com/cdn/scripts/overlay-widget.js'></script>
	<script>
	function adjustKoFiWidgetMobile() {
		if (window.innerWidth <= 768) {
			const style = document.createElement('style');
			style.innerHTML = `
				.kofi-widget-overlay {
					z-index: -1 !important; /* Place behind other elements */
					position: fixed !important;
					bottom: 20px !important;
					right: 20px !important;
				}
			`;
			document.head.appendChild(style);
		}
	}

	adjustKoFiWidgetMobile();

	window.addEventListener('resize', adjustKoFiWidgetMobile);

	kofiWidgetOverlay.draw('matsfx', {
		'type': 'floating-chat',
		'floating-chat.donateButton.text': 'Support Us',
		'floating-chat.donateButton.background-color': '#ffffff',
		'floating-chat.donateButton.text-color': '#323842'
	});
	</script>
</body>
</html>