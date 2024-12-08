<?php
require_once 'config.php';
require_once 'music_handlers.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM songs");
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>test Player</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<link rel="stylesheet" href="css/player-style.css">
</head>
<body>
<body>
    <div id="errorContainer"></div>
    <div class="player">
        <div class="player-container">
            <div class="song-info">
                <img id="player-album-art" src="" alt="Album Art" class="album-art" onerror="this.src='defaults/default-cover.jpg'">
                <div class="track-info">
                    <h3 id="songTitle" class="track-name"></h3>
                    <div id="artistName" class="artist-name"></div>
                </div>
            </div>
			<div class="player-controls">
				<div class="control-buttons">
					<button onclick="previousTrack()" aria-label="Previous Track"><i class="fas fa-step-backward"></i></button>
					<button onclick="playPause()" id="playPauseBtn" aria-label="Play/Pause"><i class="fas fa-play"></i></button>
					<button onclick="nextTrack()" aria-label="Next Track"><i class="fas fa-step-forward"></i></button>
					<button onclick="toggleLoop()" id="loopBtn" aria-label="Loop Track">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 12c0-3.866 3.134-7 7-7h6.5"/>
						<polyline points="14 2 17 5 14 8"/>
						
						<path d="M21 12c0 3.866-3.134 7-7 7H7.5"/>
						<polyline points="10 22 7 19 10 16"/>
					  </svg>
					</button>
				</div>
                <div class="progress-container">
                    <span id="currentTime">0:00</span>
                    <input type="range" id="progress" value="0" max="100" class="slider" aria-label="Song Progress">
                    <span id="duration">0:00</span>
                </div>
            </div>
            <div class="volume-control">
                <i class="fas fa-volume-up volume-icon" id="volumeIcon"></i>
                <input type="range" id="volume" min="0" max="1" step="0.01" value="1" class="volume-slider" aria-label="Volume Control">
            </div>
        </div>
    </div>

    <script>
    const songs = <?= json_encode($songs); ?>;
    let currentSongIndex = 0;
    const audio = new Audio();
    let isPlaying = false;
    let isLooping = false;

    function showError(message) {
        const errorContainer = document.getElementById('errorContainer');
        errorContainer.innerHTML = `<div class="error-message">${message}</div>`;
        setTimeout(() => {
            errorContainer.innerHTML = '';
        }, 5000);
    }

    function handleImageError(img) {
        img.src = 'defaults/default-cover.jpg';
        img.onerror = null;
    }

    function loadSong(index) {
        try {
            if (index < 0 || index >= songs.length) {
                throw new Error('Invalid song index');
            }

            const song = songs[index];
            
            if (!song.title || !song.artist || !song.file_path) {
                throw new Error('Incomplete song metadata');
            }

            document.getElementById('player-album-art').src = song.cover_art || 'defaults/default-album-art.jpg';
            document.getElementById('songTitle').textContent = song.title;
            document.getElementById('artistName').innerHTML = `
                <a href="artist?name=${encodeURIComponent(song.artist)}" class="artist-link">
                    ${song.artist}
                </a>
            `;

            audio.src = song.file_path;
            audio.load();
            playPause(true);
        } catch (error) {
            showError('Unable to load song. Please try another track.');
            console.error('Song loading error:', error);
        }
    }

    function playPause(forcePlay = false) {
        const playPauseBtn = document.getElementById('playPauseBtn');
        const icon = playPauseBtn.querySelector('i');

        try {
            if (audio.src === '') {
                throw new Error('No audio source');
            }

            if (audio.paused || forcePlay) {
                audio.play()
                    .then(() => {
                        isPlaying = true;
                        icon.classList.remove('fa-play');
                        icon.classList.add('fa-pause');
                    })
                    .catch(error => {
                        showError('Unable to play audio. Check your browser permissions.');
                        console.error('Play error:', error);
                    });
            } else {
                audio.pause();
                isPlaying = false;
                icon.classList.remove('fa-pause');
                icon.classList.add('fa-play');
            }
        } catch (error) {
            showError('Playback error occurred.');
            console.error('Playback error:', error);
        }
    }

    function nextTrack() {
        if (!isLooping) {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
        }
        loadSong(currentSongIndex);
    }

    function previousTrack() {
        if (!isLooping) {
            currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
        }
        loadSong(currentSongIndex);
    }

	function toggleLoop() {
		isLooping = !isLooping;
		const loopBtn = document.getElementById('loopBtn');
		const loopSvg = loopBtn.querySelector('svg');

		if (isLooping) {
			loopBtn.classList.add('looping');
			loopSvg.style.stroke = 'var(--primary-color)';
		} else {
			loopBtn.classList.remove('looping');
			loopSvg.style.stroke = 'white';
		}
		audio.loop = isLooping;
	}

    audio.addEventListener('timeupdate', () => {
        const progress = (audio.currentTime / audio.duration) * 100;
        document.getElementById('progress').value = progress;
        document.getElementById('currentTime').textContent = formatTime(audio.currentTime);
        document.getElementById('duration').textContent = formatTime(audio.duration || 0);
    });

    audio.addEventListener('ended', nextTrack);

    document.getElementById('progress').addEventListener('input', (e) => {
        const time = (e.target.value / 100) * audio.duration;
        audio.currentTime = time;
    });

    document.getElementById('volume').addEventListener('input', (e) => {
        const volumeValue = parseFloat(e.target.value);
        audio.volume = volumeValue;
        updateVolumeIcon(volumeValue);
    });

    function updateVolumeIcon(volume) {
        const volumeIcon = document.getElementById('volumeIcon');
        if (volume === 0) {
            volumeIcon.classList.replace('fa-volume-up', 'fa-volume-mute');
        } else if (volume < 0.5) {
            volumeIcon.classList.replace('fa-volume-up', 'fa-volume-down');
        } else {
            volumeIcon.classList.replace('fa-volume-mute', 'fa-volume-up');
            volumeIcon.classList.replace('fa-volume-down', 'fa-volume-up');
        }
    }

    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }

    loadSong(0);
    </script>
</body>
</html>