<?php
require_once 'config/config.php';
require_once 'music_handlers.php';
require_once 'user_handlers.php';
require_once 'config/terminated_account_middleware.php';

if (!isLoggedIn()) {
    header("Location: login");
    exit();
}

// Add user-banners bucket to MinIO configuration
if (!isset($minioConfig['buckets']['banners'])) {
    $minioConfig['buckets']['banners'] = 'user-banners';
}

// Ensure the new bucket exists
try {
    ensureMinIOBuckets();
} catch (Exception $e) {
    error_log("MinIO bucket initialization error: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$userSongs = getUserSongs($user_id);
$email_verified = isset($_SESSION['email_verified']) ? $_SESSION['email_verified'] : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    // Handle account deletion cancellation
    if (isset($_POST['cancel_deletion'])) {
        $cancelStmt = $pdo->prepare("
            UPDATE users 
            SET 
                marked_for_deletion = 0,
                deletion_requested_at = NULL
            WHERE user_id = ?
        ");
        $cancelResult = $cancelStmt->execute([$user_id]);
        
        if ($cancelResult) {
            $message = "Account deletion cancelled successfully! Your account and all your data will remain intact.";
        } else {
            $message = "Error: Unable to cancel account deletion. Please contact support if this problem persists.";
        }
    }
    
    if (isset($_POST['update_profile'])) {
        $result = updateProfile($user_id, $_POST, $_FILES['profile_picture'] ?? null, $_FILES['profile_banner'] ?? null);
        $message = $result['success'] ? "Profile updated successfully!" : "Error: " . $result['error'];
        
        // Reload user data after update
        if ($result['success']) {
            $user = getUserData($user_id);
        }
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
        // Verify that user has verified email before allowing song deletion
        if (!$email_verified) {
            $message = "Error: You need to verify your email address before managing songs. Please check your email for the verification link.";
        } else {
            $result = deleteSong($user_id, $_POST['song_id']);
            $message = $result['success'] ? "Song deleted successfully!" : "Error: " . $result['error'];
            if ($result['success']) {
                $userSongs = getUserSongs($user_id);
            }
        }
    }
    
    if (isset($_POST['update_song'])) {
        // Verify that user has verified email before allowing song updates
        if (!$email_verified) {
            $message = "Error: You need to verify your email address before managing songs. Please check your email for the verification link.";
        } else {
            $details = [
                'title' => $_POST['title'],
                'album' => $_POST['album'],
                'genre' => $_POST['genre'],
            ];
            
            $result = updateSongDetails($user_id, $_POST['song_id'], $details);
            $message = $result['success'] ? "Song details updated successfully!" : "Error: " . $result['error'];
            if ($result['success']) {
                $userSongs = getUserSongs($user_id);
            }
        }
    }
    
    if (isset($_POST['resend_verification'])) {
        // Call function to resend verification email
        require_once 'config/auth.php';
        $result = resendVerificationEmail($user_id);
        $message = $result['success'] ? "Verification email has been sent to " . $result['email'] : "Error: " . $result['error'];
    }
}

// Default banner if not set
$userBanner = $user['profile_banner'] ?? 'defaults/default-banner.jpg';

// Check if account is marked for deletion
$deletionCheck = $pdo->prepare("SELECT marked_for_deletion, deletion_requested_at FROM users WHERE user_id = ?");
$deletionCheck->execute([$user_id]);
$deletionStatus = $deletionCheck->fetch(PDO::FETCH_ASSOC);
$accountMarkedForDeletion = ($deletionStatus && isset($deletionStatus['marked_for_deletion']) && $deletionStatus['marked_for_deletion'] == 1);
$daysRemaining = 0;

if ($accountMarkedForDeletion && isset($deletionStatus['deletion_requested_at'])) {
    // Calculate days remaining
    $requestedDate = new DateTime($deletionStatus['deletion_requested_at']);
    $deleteDate = clone $requestedDate;
    $deleteDate->add(new DateInterval('P7D')); // 7 days later
    $now = new DateTime();
    $daysRemaining = $now->diff($deleteDate)->days;
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
    <meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>User Settings - matSFX</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/global/imageLoading.css">
    <link rel="stylesheet" href="css/global/customModal.css">
    <?php outputChristmasThemeCSS(); ?>
</head>
<body>

    <?php
    require_once 'includes/header.php';
    ?>

    <div class="settings-container">
        <?php if (isset($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo strpos($message, 'Error') === false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($accountMarkedForDeletion): ?>
        <div class="deletion-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Your account is scheduled for deletion in <?php echo $daysRemaining; ?> days. After this period, your account will be permanently deleted.</p>
            <form method="POST">
                <button type="submit" name="cancel_deletion">Cancel Account Deletion</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!$email_verified): ?>
        <div class="verification-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <p>Your email address is not verified. You need to verify your email to upload and manage songs.</p>
                <div class="verification-actions">
                    <form method="POST">
                        <button type="submit" name="resend_verification" class="verification-button">Resend Verification Email</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="settings-section">
            <h2>Profile Settings</h2>
            
            <div class="profile-banner-container">
                <img src="<?php echo htmlspecialchars($userBanner); ?>" alt="Profile Banner" class="profile-banner">
                <div class="profile-banner-overlay"></div>
                <label for="profile_banner" class="profile-banner-edit">
                    <i class="fas fa-camera"></i>
                </label>
                
                <div class="profile-picture-container">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" 
                         alt="Profile Picture" class="settings-profile-picture">
                    <label for="profile_picture" class="profile-picture-edit">
                        Change Photo
                    </label>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden-file-input">
                <input type="file" id="profile_banner" name="profile_banner" accept="image/*" class="hidden-file-input">
                
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
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" maxlength="300" rows="4"><?php 
                        echo htmlspecialchars($user['bio'] ?? '');
                    ?></textarea>
                    <div class="upload-hint">Tell others about yourself (max 300 characters)</div>
                </div>

                <div class="profile-upload-container">
                    <div>
                        <label for="profile_picture" class="upload-label">
                            <i class="fas fa-user"></i> Change Profile Picture
                        </label>
                        <div class="upload-hint">Recommended size: 200x200px (Square image works best)</div>
                    </div>
                    
                    <div>
                        <label for="profile_banner" class="upload-label">
                            <i class="fas fa-image"></i> Change Banner Image
                        </label>
                        <div class="upload-hint">Recommended size: 1200x300px (Will be cropped to fit)</div>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="button">Update Profile</button>
            </form>
        </div>
        
        <!--
        <div class="settings-section">
            <h2>Theme Settings</h2>
            <form method="POST" class="theme-toggle">
                <button type="submit" name="toggle_christmas_theme" class="christmas-toggle-btn">
                    <?php echo isChristmasThemeEnabled() ? '🎄 Disable Christmas Theme' : '🎄 Enable Christmas Theme'; ?>
                </button>
            </form>
        </div>
        -->
        
        <div class="settings-section">
            <h2>Security Settings</h2>
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
            <h2>Privacy & Data</h2>
            
            <div class="form-group">
                <label class="form-label">Your Rights</label>
                <div class="privacy-rights-list">
                    <div class="privacy-right-item">
                        <i class="fas fa-download"></i>
                        <div>
                            <h4>Access and Download</h4>
                            <p>You can access and download a copy of all personal data we hold about you.</p>
                            <button type="button" class="button button-secondary" id="download-data-btn">
                                Download My Data
                            </button>
                        </div>
                    </div>
                    
                    <div class="privacy-right-item">
                        <i class="fas fa-trash-alt"></i>
                        <div>
                            <h4>Deletion</h4>
                            <p>You can request the deletion of your account and associated personal data.</p>
                            <button type="button" class="button button-delete" id="delete-account-btn">
                                Delete Account
                            </button>
                        </div>
                    </div>
                    
                    <div class="privacy-right-item">
                        <i class="fas fa-file-export"></i>
                        <div>
                            <h4>Data Portability</h4>
                            <p>You can request your data in a structured, commonly used format.</p>
                            <div class="export-options">
                                <button type="button" class="button button-secondary" id="export-json-btn">
                                    Export as JSON
                                </button>
                                <button type="button" class="button button-secondary" id="export-csv-btn">
                                    Export as CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="privacy-right-item">
                        <div>
                            <h4>Opt-Out</h4>
                            <p>You can opt out of non-essential communications and data collection.</p>
                            <div class="form-check">
                                <input type="checkbox" id="opt_out_analytics" name="opt_out_analytics" class="form-check-input">
                                <label for="opt_out_analytics" class="form-check-label">Opt out of analytics collection</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="privacy-right-item">
                        <div>
                            <h4>Objection</h4>
                            <p>You have the right to object to processing of your personal data.</p>
                            <p class="small-text">Contact us at privacy@matsfx.com to exercise this right.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Cookies and Tracking</label>
                <div class="cookie-info">
                    <div class="cookie-item">
                        <h4><i class="fas fa-cookie"></i> Session Cookies</h4>
                        <p>matSFX only uses essential session cookies to maintain your login state and basic preferences. We do not use tracking or advertising cookies.</p>
                    </div>
                    
                    <div class="cookie-status">
                        <div class="status-item">
                            <span class="status-label">Session Cookies:</span>
                            <span class="status-value enabled">Enabled (Required)</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Analytics Cookies:</span>
                            <span class="status-value disabled">Not Used</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Advertising Cookies:</span>
                            <span class="status-value disabled">Not Used</span>
                        </div>
                    </div>
                    
                    <p class="cookie-note">You can clear your browser cookies at any time through your browser settings, but this will sign you out of matSFX.</p>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_privacy" class="button">
                    <i class="fas fa-save"></i> Save Privacy Preferences
                </button>
            </div>
        </div>


        <div class="settings-section">
            <h2>My Uploaded Songs</h2>
            
            <?php if (!$email_verified): ?>
            <div class="verification-banner">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <p>You need to verify your email address to manage your songs.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <ul class="songs-list">
                <?php foreach ($userSongs as $song): ?>
                    <li class="song-item">
                        <div class="song-details">
                            <div class="song-info">
                                <strong><?php echo htmlspecialchars($song['title']); ?></strong> - 
                                <?php echo htmlspecialchars($song['artist']); ?>
                            </div>
                            <div class="song-actions">
                                <button class="button button-secondary" 
                                        onclick="toggleEditForm('<?php echo $song['song_id']; ?>')"
                                        <?php echo !$email_verified ? 'disabled' : ''; ?>>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="song_id" value="<?php echo $song['song_id']; ?>">
                                    <button type="submit" name="delete_song" class="button button-delete"
                                            onclick="return confirm('Are you sure you want to delete this song?')"
                                            <?php echo !$email_verified ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div id="edit-form-<?php echo $song['song_id']; ?>" class="edit-form" style="display: none;">
                            <form method="POST" class="song-edit-form" enctype="multipart/form-data">
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
                                
                                <div class="form-group">
                                    <label for="song_cover-<?php echo $song['song_id']; ?>">Cover Art</label>
                                    <input type="file" id="song_cover-<?php echo $song['song_id']; ?>" 
                                        name="song_cover" accept="image/*">
                                    <div class="current-cover">
                                        <img src="<?php echo htmlspecialchars($song['cover_art'] ?? '/defaults/default-cover.jpg'); ?>" 
                                            alt="Cover Art" style="max-width: 100px; margin-top: 10px;">
                                        <small>Current cover</small>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_song" class="button">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" class="button button-secondary" onclick="toggleEditForm('<?php echo $song['song_id']; ?>')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
                
                <?php if (empty($userSongs)): ?>
                    <div class="empty-songs-message">
                        <p>You haven't uploaded any songs yet.</p>
                        <a href="upload" class="button">
                            <i class="fas fa-upload"></i> Upload Your First Song
                        </a>
                    </div>
                <?php endif; ?>
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
    
    // Handle file previews
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.settings-profile-picture').src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    document.getElementById('profile_banner').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.profile-banner').src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
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
    <script src="js/settings.js"></script>
</body>
</html>