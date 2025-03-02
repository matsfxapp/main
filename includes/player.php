<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="/../css/player-style.css">

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
    </div>
</div>


<script>
    let audioPlayer = new Audio();
    let isChangingTrack = false;
    let currentPlaylist = [];
    let currentPlaylistIndex = 0;
    
    document.addEventListener('DOMContentLoaded', function() {
        const progressBar = document.getElementById('progress');
        initializePlayerControls(audioPlayer, progressBar);
    });
    
    function playSong(filePath, element) {
        if (isChangingTrack) return;
        isChangingTrack = true;
        
        // Check if we're playing a song from an album
        const albumSection = element.closest('.album-section');
        if (albumSection) {
            // Create a playlist from all songs in this album
            const songRows = albumSection.querySelectorAll('.song-row');
            currentPlaylist = Array.from(songRows);
            currentPlaylistIndex = Array.from(songRows).indexOf(element);
        } else {
            // Single track playing
            currentPlaylist = [];
            currentPlaylistIndex = 0;
        }

        // Check if this is a MinIO path and transform it if needed
        if (filePath.includes('minio_path') || filePath.includes('minio_cover_path')) {
            // Make an AJAX call to get the actual URL
            fetch('get_minio_url.php?path=' + encodeURIComponent(filePath))
                .then(response => response.text())
                .then(actualUrl => {
                    audioPlayer.src = actualUrl;
                    continuePlaySong(element);
                })
                .catch(error => {
                    console.error('Error fetching MinIO URL:', error);
                    alert('Could not access the song file. Please try again.');
                    isChangingTrack = false;
                });
        } else {
            audioPlayer.src = filePath;
            continuePlaySong(element);
        }
    }
    
    // Function to play all songs in an album
    function playAlbum(albumSection) {
        const songRows = albumSection.querySelectorAll('.song-row');
        if (songRows.length > 0) {
            currentPlaylist = Array.from(songRows);
            currentPlaylistIndex = 0;
            
            // Get the file path from the first song's onclick attribute
            const firstSong = songRows[0];
            const songId = firstSong.getAttribute('data-song-id');
            const filePath = firstSong.getAttribute('data-song-file') || 
                             firstSong.getAttribute('onclick').toString().match(/'([^']+)'/)[1];
            
            playSong(filePath, firstSong);
        }
    }

    function continuePlaySong(element) {
        // Get song info from the clicked element
        let songTitle, artistName, coverArt;
        
        // Check if element is a song row from album or a song card
        if (element.classList.contains('song-row')) {
            songTitle = element.getAttribute('data-song-title') || element.querySelector('.song-row-title').textContent;
            artistName = element.getAttribute('data-song-artist') || element.querySelector('.song-row-artist').textContent;
            
            // Get cover art from the album section
            const albumSection = element.closest('.album-section');
            coverArt = albumSection ? albumSection.querySelector('.album-cover').src : 'defaults/default-cover.jpg';
            
            // Mark this song as playing
            document.querySelectorAll('.song-row').forEach(row => row.classList.remove('playing'));
            element.classList.add('playing');
        } else if (element.classList.contains('song-card')) {
            // For single song cards
            coverArt = element.querySelector('.song-card-image').src;
            songTitle = element.querySelector('.song-card-title').textContent;
            artistName = element.querySelector('.song-card-artist').textContent;
            
            // Mark this song as active
            document.querySelectorAll('.song-card').forEach(card => card.classList.remove('active-song'));
            element.classList.add('active-song');
        } else {
            // Fallback for other elements
            songTitle = element.getAttribute('data-song-title') || 'Unknown Title';
            artistName = element.getAttribute('data-song-artist') || 'Unknown Artist';
            coverArt = 'defaults/default-cover.jpg';
        }

        // Update player display
        document.getElementById('player-album-art').src = coverArt;
        document.getElementById('songTitle').textContent = songTitle;
        document.getElementById('artistName').textContent = artistName;

        audioPlayer.play()
            .then(() => {
                updatePlayPauseButton(true);
            })
            .catch(error => {
                console.error('Error playing song:', error);
                alert('Could not play the song. Please try again.');
            })
            .finally(() => {
                isChangingTrack = false;
            });
    }
    
    function updatePlayPauseButton(isPlaying) {
        const playPauseBtn = document.getElementById('playPauseBtn');
        const icon = playPauseBtn.querySelector('i');
        icon.classList.remove('fa-play', 'fa-pause');
        icon.classList.add(isPlaying ? 'fa-pause' : 'fa-play');
    }
    
    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    function nextTrack() {
        if (currentPlaylist.length > 0 && currentPlaylistIndex < currentPlaylist.length - 1) {
            currentPlaylistIndex++;
            const nextSong = currentPlaylist[currentPlaylistIndex];
            
            // Extract the file path
            const filePath = nextSong.getAttribute('data-song-file') || 
                            nextSong.getAttribute('onclick').toString().match(/'([^']+)'/)[1];
            
            playSong(filePath, nextSong);
        }
    }
    
    function previousTrack() {
        if (currentPlaylist.length > 0 && currentPlaylistIndex > 0) {
            currentPlaylistIndex--;
            const prevSong = currentPlaylist[currentPlaylistIndex];
            
            // Extract the file path
            const filePath = prevSong.getAttribute('data-song-file') || 
                            prevSong.getAttribute('onclick').toString().match(/'([^']+)'/)[1];
            
            playSong(filePath, prevSong);
        } else if (audioPlayer.currentTime > 3) {
            // If current time is more than 3 seconds, restart the current song
            audioPlayer.currentTime = 0;
        }
    }
    
    function initializePlayerControls(audioPlayer, progressBar) {
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
    
        audioPlayer.addEventListener('timeupdate', function() {
            const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            progressBar.value = progress;
    
            document.getElementById('currentTime').textContent = formatTime(audioPlayer.currentTime);
            document.getElementById('duration').textContent = formatTime(audioPlayer.duration || 0);
        });
    
        progressBar.addEventListener('input', (e) => {
            const time = (e.target.value / 100) * audioPlayer.duration;
            audioPlayer.currentTime = time;
        });
    
        window.toggleLoop = function() {
            audioPlayer.loop = !audioPlayer.loop;
            const loopBtn = document.getElementById('loopBtn');
            const svgIcon = loopBtn.querySelector('svg');
        
            if (audioPlayer.loop) {
                svgIcon.style.stroke = 'rgba(45, 127, 249, 0.8)';
            } else {
                svgIcon.style.stroke = 'currentColor';
            }
        };
        
        // Add event listener for when a song ends
        audioPlayer.addEventListener('ended', function() {
            if (!audioPlayer.loop && currentPlaylist.length > 0 && currentPlaylistIndex < currentPlaylist.length - 1) {
                // If we have more songs in the playlist, play the next one
                nextTrack();
            } else {
                // Reset the play button
                updatePlayPauseButton(false);
            }
        });
    }
    
    // Make functions available globally
    window.playSong = playSong;
    window.playAlbum = playAlbum;
    window.nextTrack = nextTrack;
    window.previousTrack = previousTrack;
</script>