<?php
require_once 'config.php';
require_once 'music_handlers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$songs = getAllSongs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matSFX - Music for everyone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
    <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="upload.php">Upload</a>
            <a href="user_settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="music-grid">
            <?php foreach ($songs as $song): ?>
                <div class="song-card">
                    <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist">
                        <a href="artist.php?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                            <?php echo htmlspecialchars($song['artist']); ?>
                        </a>
                    </div>
                    <div class="song-controls">
                        <button onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)" class="play-btn">
                            Play
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" class="search-input" placeholder="Search for artists..." id="artistSearch">
        <div class="search-results" id="searchResults" style="display: none;">
        </div>
    </div>

    <div class="artist-profile" id="artistProfile" style="display: none;">
        <div class="profile-header">
            <div class="profile-content">
                <img src="/api/placeholder/180/180" alt="Artist" class="profile-image">
                <div class="profile-info">
                    <h1 class="profile-name" id="artistName"></h1>
                    <div class="profile-stats">
                        <span id="songCount"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="artist-songs">
            <div class="songs-header">
                <h2 class="songs-title">Songs</h2>
            </div>
            <div class="music-grid" id="artistSongs">
             
            </div>
        </div>
    </div> -->


    <div class="player">
        <audio id="audio-player" controls>
            Your browser does not support the audio element.
        </audio>
    </div>

    <script>
        const audioPlayer = document.getElementById('audio-player');
        let currentButton = null;

        function playSong(songPath, button) {
            if (currentButton) {
                currentButton.textContent = 'Play';
            }

            if (audioPlayer.src.endsWith(songPath) && !audioPlayer.paused) {
                audioPlayer.pause();
                button.textContent = 'Play';
            } else {
                audioPlayer.src = songPath;
                audioPlayer.play().then(() => {
                    button.textContent = 'Pause';
                    currentButton = button;
                }).catch(error => {
                    console.error('Error playing song:', error);
                    alert('Error playing song. Please try again.');
                });
            }
        }

        audioPlayer.addEventListener('ended', () => {
            if (currentButton) {
                currentButton.textContent = 'Play';
            }
        });

        /* maybe add, maybe not
        const artistSearch = document.getElementById('artistSearch');
        const searchResults = document.getElementById('searchResults');
        const artistProfile = document.getElementById('artistProfile');
        const mainMusicGrid = document.querySelector('.music-grid:not(#artistSongs)');

        artistSearch.addEventListener('keyup', function() {
            if (this.value.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            fetch(`search_artist.php?search=${encodeURIComponent(this.value)}`)
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    data.forEach(result => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'search-result-item';
                        resultItem.innerHTML = `
                            <img src="/api/placeholder/50/50" alt="${result.artist}" class="result-image">
                            <div class="result-info">
                                <div class="result-name">${result.artist}</div>
                            </div>
                        `;
                        resultItem.onclick = () => showArtistProfile(result.artist);
                        searchResults.appendChild(resultItem);
                    });
                    searchResults.style.display = data.length ? 'block' : 'none';
                });
        });

        function showArtistProfile(artistName) {
            fetch(`get_artist_songs.php?artist=${encodeURIComponent(artistName)}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('artistName').textContent = artistName;
                    document.getElementById('songCount').textContent = `${data.length} Songs`;
                    
                    const artistSongs = document.getElementById('artistSongs');
                    artistSongs.innerHTML = data.map(song => `
                        <div class="song-card">
                            <img src="${song.cover_art || 'default-cover.jpg'}" alt="Cover Art" class="cover-art">
                            <div class="song-title">${song.title}</div>
                            <div class="song-artist">${song.artist}</div>
                            <div class="song-controls">
                                <button onclick="playSong('${song.file_path}', this)" class="play-btn">
                                    Play
                                </button>
                            </div>
                        </div>
                    `).join('');

                    searchResults.style.display = 'none';
                    artistProfile.style.display = 'block';
                    mainMusicGrid.style.display = 'none';
                });
        }
        */

        // Add to handle going back to main view
        document.querySelector('.logo').addEventListener('click', function(e) {
            e.preventDefault();
            artistProfile.style.display = 'none';
            mainMusicGrid.style.display = 'grid';
            artistSearch.value = '';
        });
    </script>
</body>
</html>