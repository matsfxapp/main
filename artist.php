<?php
require_once 'handlers/artist.php';

/**
 * Function to add version parameter to asset URLs
 * @param string $path Path to the asset file
 * @return string Path with version parameter
 */
function asset_url($path) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path)) {
        $version = filemtime($_SERVER['DOCUMENT_ROOT'] . '/' . $path);
        return $path . '?v=' . $version;
    }
    return $path;
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
    <meta property="og:url" content="https://alpha.matsfx.com/artist?name=<?php echo urlencode($artist); ?>">
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
	<div class="header-spacer"></div>
    
    <div class="artist-profile">
        <!-- Banner Image Section -->
        <div class="profile-banner-wrapper">
            <div class="profile-banner" style="background-image: url('<?php echo htmlspecialchars($bannerImage ?: 'defaults/default-banner.jpg'); ?>');">
                <div class="banner-overlay"></div>
            </div>
        </div>
        
        <div class="profile-header">
            <div class="profile-content">
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
                     alt="<?php echo htmlspecialchars($artist); ?>" 
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
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($artistData['bio'])); ?></p>
                    <?php endif; ?>
    
                    <div class="profile-stats">
                        <span><i class="fas fa-music"></i> <?php echo count($songsData['all_songs']); ?> Songs</span>
                        <span><i class="fas fa-users"></i> <?php echo getFollowerCount($profileUserId); ?> Followers</span>
                        <span><i class="fas fa-compact-disc"></i> <?php echo count($songsData['by_album']); ?> Albums</span>
                    </div>
    
                    <?php if ($currentUserId): ?>
                        <?php if ($currentUserId != $profileUserId): ?>
                            <form id="follow-form" method="POST">
                                <button type="button" 
                                        id="follow-btn" 
                                        class="follow-button <?php echo $isFollowing ? 'following' : ''; ?>">
                                    <i class="fas <?php echo $isFollowing ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                                    <span class="follow-text"><?php echo $isFollowing ? '<span>Following</span>' : 'Follow'; ?></span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="/settings" class="edit-profile-button">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="login-prompt">
                            <a href="/login" class="login-button">Login</a> or <a href="/register" class="signup-button">Sign up</a> to follow this artist!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="artist-content-wrapper">
        <?php if (!empty($popularSongs)): ?>
        <div class="popular-container">
            <h2 class="section-title"><i class="fas fa-fire"></i> Popular</h2>
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
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
            
        <?php
            if (empty($songsData['all_songs'])): ?>
                <div class="no-songs-message">
                    <i class="fas fa-music no-songs-icon"></i>
                    <p>No songs uploaded yet.</p>
                    <?php if ($currentUserId == $profileUserId): ?>
                        <a href="/upload" class="upload-button">Upload Your First Song</a>
                    <?php endif; ?>
                </div>
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
                        <h2 class="section-title"><i class="fas fa-compact-disc"></i> Albums</h2>
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
                                                $songId = $song['song_id'];
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
                        <h2 class="section-title"><i class="fas fa-music"></i> Singles</h2>
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
    <?php require_once 'includes/player.php'?>
    
    <script src="js/index.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // For follow/unfollow button
        const followBtn = document.getElementById('follow-btn');
    
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const action = followBtn.classList.contains('following') ? 'unfollow' : 'follow';
    
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
                            followBtn.classList.add('following');
                            followBtn.innerHTML = `
                                <i class="fas fa-user-check"></i>
                                <span class="follow-text"><span>Following</span></span>
                            `;
                        } else {
                            followBtn.classList.remove('following');
                            followBtn.innerHTML = `
                                <i class="fas fa-user-plus"></i>
                                <span class="follow-text">Follow</span>
                            `;
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

        // Album section toggle
        const albumHeaders = document.querySelectorAll('.album-header');
        albumHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const albumSection = this.closest('.album-section');
                const songsSection = albumSection.querySelector('.album-songs');
                this.classList.toggle('collapsed');
                
                if (songsSection.style.display === 'none') {
                    songsSection.style.display = 'block';
                } else {
                    songsSection.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>