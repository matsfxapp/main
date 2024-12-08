<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'music_handlers.php';

//$auth = new Authentication($pdo);

//if (!$auth->isFeatureAllowed('like_song')) {
 //   echo json_encode([
  //      'status' => 'error',
   //     'message' => 'Hey there, to like a Song you are required to create or login to an existing Account.',
     //   'prompt_signup' => true
    //]);
    //exit();
//}

//if (!$auth->isFeatureAllowed('follow_user')) {
  //  echo json_encode([
   //     'status' => 'error',
   //     'message' => 'Hey there, and account is required to follow artists or users.',
     //   'prompt_signup' => true
    //]);
    //exit();
//}

$songs = getAllSongs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matSFX - Music for everyone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app-images/matsfx-logo.png">
    <link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="changelog.css">
</head>
<body>
    <nav class="navbar">
		
	<!--<div class="search-container">
		<span class="search-icon">🔍</span>
		<input type="text" class="search-input" placeholder="Search for artists..." id="artistSearch">
		<div class="search-results" id="searchResults" style="display: none;">
		</div> -->
	</div>
    <div class="logo">matSFX - Alpha 0.1</div>
        <div class="nav-links">
            <a href="../">Home</a>
            <a href="upload">Upload</a>
            <a href="settings">Settings</a>
            <a href="logout">Logout</a>
        </div>
    </nav>
	
	<!--<div class="changelog-overlay" id="changelogOverlay">
        <div class="changelog-container">
            <div class="changelog-header">
                <h2>Changelog <span class="changelog-new-badge">Alpha 0.3.3</span></h2>
                <button class="changelog-close" onclick="closeChangelog()">&times;</button>
            </div>
            <div class="changelog-content">
                <div class="changelog-version">Alpha 0.3.3</div>
                
                <div class="changelog-feature">
                    <div class="changelog-feature-title">VERIFICATION IS NOW A THING</div>
                    <div class="changelog-feature-description">
						<h2>Here’s how it works:</h2>
							<h3>You can get verified by being an artist with at least 2 songs (for now).<br>
							If you’re part of the matSFX Design or Development Team, you’ll automatically receive your verified badge within the next 24 hours!</h3>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <div class="container" style="padding-bottom: 10%;">
        <div class="music-grid">
            <?php foreach ($songs as $song): ?>
                <div class="song-card" onclick="playSong('<?php echo htmlspecialchars($song['file_path']); ?>', this)">
                    <img src="<?php echo htmlspecialchars($song['cover_art'] ?? 'defaults/default-cover.jpg'); ?>" alt="Cover Art" class="cover-art">
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist">
                        <a href="artist?name=<?php echo urlencode($song['artist']); ?>" class="artist-link">
                            <?php echo htmlspecialchars($song['artist']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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
    </div>

	<div class="player">
		<audio id="audio-player" controls controlsList="nodownload">
			Your browser does not support the audio element.
		</audio>
		<!--<button class="changelog-trigger" onclick="openChangelog()">
			<img class="changelog-trigger-image" alt="Changelog" />
		</button> -->
	</div>

    <script>
		const audioPlayer = document.getElementById('audio-player');

		function playSong(songPath, button) {
			if (audioPlayer.src.endsWith(songPath) && !audioPlayer.paused) {
				audioPlayer.pause();  
			} else {
				audioPlayer.src = songPath;  
				audioPlayer.play().catch(error => {
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
		
		const player = document.querySelector('.player');

		audioPlayer.addEventListener('play', () => {
			player.classList.add('active');
		});

		audioPlayer.addEventListener('pause', () => {
			player.classList.remove('active');
		});
		
		function openChangelog() {
            const overlay = document.getElementById('changelogOverlay');
            overlay.classList.add('active');
            overlay.style.display = 'flex';
        }

        function closeChangelog() {
            const overlay = document.getElementById('changelogOverlay');
            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        }

		const artistSearch = document.getElementById('artistSearch');
		const searchResults = document.getElementById('searchResults');
		const artistProfile = document.getElementById('artistProfile');
		const mainMusicGrid = document.querySelector('.music-grid:not(#artistSongs)');

		let searchTimeout;
		let searchedArtists = [];

		artistSearch.addEventListener('keyup', function () {
			clearTimeout(searchTimeout);

			// hide results if to long
			if (this.value.length < 2) {
				searchResults.style.display = 'none';
				return;
			}

			// delay the search
			searchTimeout = setTimeout(() => {
				fetch(`search_artist.php?search=${encodeURIComponent(this.value)}`)
					.then(response => response.json())
					.then(data => {
						searchResults.innerHTML = '';
						searchedArtists = [];

						// if no results found
						if (data.length === 0) {
							const noResultItem = document.createElement('div');
							noResultItem.className = 'search-result-item no-results';
							noResultItem.textContent = 'No results found';
							searchResults.appendChild(noResultItem);
						} else {
							data.forEach(result => {
								if (!searchedArtists.includes(result.name)) {
									const resultItem = document.createElement('div');
									resultItem.className = 'search-result-item';
									resultItem.innerHTML = `
										<img src="/api/placeholder/50/50" alt="${result.name}" class="result-image">
										<div class="result-info">
											<div class="result-name">${result.name}</div>
											<div class="result-type">${result.type}</div>
										</div>
									`;
									resultItem.addEventListener('click', () => showArtistProfile(result.name));
									searchResults.appendChild(resultItem);
									searchedArtists.push(result.name);
								}
							});
						}

						searchResults.style.display = 'block';
					})
					.catch(error => {
						console.error('Search error:', error);
					});
			}, 300);
		});

		function showArtistProfile(artistName) {
			fetch(`get_artist_songs.php?artist=${encodeURIComponent(artistName)}`)
				.then(response => response.json())
				.then(songData => {
					fetch(`get_artist_profile.php?artist=${encodeURIComponent(artistName)}`)
						.then(response => response.json())
						.then(profileData => {
							document.getElementById('artistName').textContent = artistName;
							document.getElementById('songCount').textContent = `${songData.length} Songs`;
							document.getElementById('artistImage').src = profileData.profile_picture || '/api/placeholder/180/180';

							const artistSongs = document.getElementById('artistSongs');
							artistSongs.innerHTML = songData.map(song => `
								<div class="song-card">
									<img src="${song.cover_art}" alt="Cover Art" class="cover-art">
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
						})
						.catch(error => {
							console.error('Error fetching artist profile:', error);
						});
				})
				.catch(error => {
					console.error('Error fetching artist songs:', error);
				});
		}

		document.addEventListener('click', function(event) {
			if (!searchResults.contains(event.target) && event.target !== artistSearch) {
				searchResults.style.display = 'none';
			}
		});

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