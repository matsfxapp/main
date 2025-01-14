<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet"href="/../css/player-style.css">

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
	        
			<!--
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
	        </div> -->
	    </div>
	</div>
<script>
	let audioPlayer;

	document.addEventListener('DOMContentLoaded', function() {
		// Initialize audio player
		audioPlayer = new Audio();
		const progressBar = document.getElementById('progress');
		const volumeSlider = document.getElementById('volume');

		// Initialize player controls and event listeners
		initializePlayerControls(audioPlayer, progressBar, volumeSlider);
		initializeArtistSearch();
		initializeArtistSections();
	});

	function playSong(filePath, songCardElement) {
		console.log('Playing song:', filePath);

		if (!audioPlayer) {
			audioPlayer = new Audio();
		}

		const coverArt = songCardElement.querySelector('.cover-art').src;
		const songTitle = songCardElement.querySelector('.song-title').textContent;
		const artistLink = songCardElement.querySelector('.artist-link');
		const artistName = artistLink ? artistLink.textContent : 'Unknown Artist';

		document.getElementById('player-album-art').src = coverArt || 'defaults/default-cover.jpg';
		document.getElementById('songTitle').textContent = songTitle;
		document.getElementById('artistName').textContent = artistName;

		if (audioPlayer.src === filePath && !audioPlayer.paused) {
			audioPlayer.pause();
			songCardElement.classList.remove('active-song');
			updatePlayPauseButton(false);
		} else {
			audioPlayer.src = filePath;
			audioPlayer.play()
				.then(() => {
					document.querySelectorAll('.song-card').forEach(card => 
						card.classList.remove('active-song')
					);
					
					songCardElement.classList.add('active-song');
					updatePlayPauseButton(true);
				})
				.catch(error => {
					console.error('Error playing song:', error);
					alert('Could not play the song. Please try again.');
				});
		}
	}

	function updatePlayPauseButton(isPlaying) {
		const playPauseBtn = document.getElementById('playPauseBtn');
		if (playPauseBtn) {
			const icon = playPauseBtn.querySelector('i');
			if (icon) {
				icon.classList.remove('fa-play', 'fa-pause');
				icon.classList.add(isPlaying ? 'fa-pause' : 'fa-play');
			}
		}
	}

	function formatTime(seconds) {
		if (isNaN(seconds)) return "0:00";
		const minutes = Math.floor(seconds / 60);
		const remainingSeconds = Math.floor(seconds % 60);
		return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
	}
	
	function initializePlayerControls(audioPlayer, progressBar, volumeSlider) {
		// Play/Pause functionality
		window.playPause = function() {
			if (audioPlayer.paused) {
				audioPlayer.play()
					.then(() => updatePlayPauseButton(true))
					.catch(error => {
						console.error('Error playing audio:', error);
						alert('Could not play audio');
					});
			} else {
				audioPlayer.pause();
				updatePlayPauseButton(false);
			}
		};

		// Track navigation
		window.nextTrack = function() {
			const songCards = document.querySelectorAll('.song-card');
			const currentActive = document.querySelector('.song-card.active-song');
			
			if (currentActive) {
				const currentIndex = Array.from(songCards).indexOf(currentActive);
				const nextIndex = (currentIndex + 1) % songCards.length;
				const nextSongCard = songCards[nextIndex];
				
				const nextSongPath = extractFilePath(nextSongCard.getAttribute('onclick'));
				playSong(nextSongPath, nextSongCard);
			}
		};

		window.previousTrack = function() {
			const songCards = document.querySelectorAll('.song-card');
			const currentActive = document.querySelector('.song-card.active-song');
			
			if (currentActive) {
				const currentIndex = Array.from(songCards).indexOf(currentActive);
				const prevIndex = (currentIndex - 1 + songCards.length) % songCards.length;
				const prevSongCard = songCards[prevIndex];
				
				const prevSongPath = extractFilePath(prevSongCard.getAttribute('onclick'));
				playSong(prevSongPath, prevSongCard);
			}
		};

		// Progress bar and time update
		if (progressBar) {
			audioPlayer.addEventListener('timeupdate', function() {
				const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
				progressBar.value = progress;

				const currentTimeEl = document.getElementById('currentTime');
				const durationEl = document.getElementById('duration');

				if (currentTimeEl && durationEl) {
					currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
					durationEl.textContent = formatTime(audioPlayer.duration || 0);
				}
			});

			progressBar.addEventListener('input', (e) => {
				const time = (e.target.value / 100) * audioPlayer.duration;
				audioPlayer.currentTime = time;
			});
		}

		// Volume control
		if (volumeSlider) {
			volumeSlider.addEventListener('input', (e) => {
				audioPlayer.volume = parseFloat(e.target.value);
			});
		}

		// Loop control
		window.toggleLoop = function() {
			audioPlayer.loop = !audioPlayer.loop;
			const loopBtn = document.getElementById('loopBtn');
			if (loopBtn) {
				loopBtn.classList.toggle('looping');
				const loopSvg = loopBtn.querySelector('svg');
				if (loopSvg) {
					loopSvg.style.stroke = audioPlayer.loop ? 'var(--primary-color)' : 'white';
				}
			}
		};
	}
</script>