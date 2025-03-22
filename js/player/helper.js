/**
 * Player Helper Functions
 * Contains common utility functions for the audio player
 */

// Set up global variables
window.audioPlayer = window.audioPlayer || new Audio();
window.isChangingTrack = false;
window.currentPlaylist = [];
window.currentPlaylistIndex = 0;
window.lastPlayedSong = null;
window.lastPlayedPosition = 0;
window.trackedSongs = new Set();

/**
 * Format seconds into mm:ss time format
 * @param {number} seconds - Time in seconds
 * @returns {string} - Formatted time string
 */
function formatTime(seconds) {
    if (isNaN(seconds)) return "0:00";
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

/**
 * Update play/pause button to reflect current state
 * @param {boolean} isPlaying - Whether audio is playing
 */
function updatePlayPauseButton(isPlaying) {
    const playPauseBtn = document.getElementById('playPauseBtn');
    if (!playPauseBtn) return;
    
    const icon = playPauseBtn.querySelector('i');
    if (icon) {
        icon.classList.remove('fa-play', 'fa-pause');
        icon.classList.add(isPlaying ? 'fa-pause' : 'fa-play');
    }
}

/**
 * Display an error message
 * @param {string} message - Error message to display
 */
function displayError(message) {
    const errorContainer = document.getElementById('errorContainer');
    if (!errorContainer) return;
    
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
    
    // Hide the error after 5 seconds
    setTimeout(() => {
        errorContainer.style.display = 'none';
    }, 5000);
}

/**
 * Track song play count
 * @param {string|number} songId - ID of the song being played
 */
function trackSongPlay(songId) {
    // Dont track if already tracked in this session
    if (!songId) {
        console.log("No song ID provided for tracking");
        return;
    }
    
    if (trackedSongs.has(songId)) {
        console.log(`Song ${songId} already tracked in this session`);
        return;
    }
    
    console.log(`Tracking play for song ID: ${songId}`);
    
    fetch('includes/track_play.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ song_id: songId })
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Add to tracked songs set
            trackedSongs.add(songId);
            console.log(`Play count for song ${songId} updated to: ${data.play_count}`);
        } else {
            console.error('Track play failed:', data.error);
        }
    })
    .catch(error => {
        console.error('Error tracking play:', error);
    });
}

/**
 * Play a song
 * @param {string} filePath - Path to the audio file
 * @param {HTMLElement} element - DOM element containing song data
 */
function playSong(filePath, element) {
    if (window.isChangingTrack) return;
    window.isChangingTrack = true;
    
    // Handle playlist creation based on element type
    if (element.classList.contains('song-row')) {
        const albumSection = element.closest('.album-section');
        if (albumSection) {
            const songRows = albumSection.querySelectorAll('.song-row');
            window.currentPlaylist = Array.from(songRows);
            window.currentPlaylistIndex = Array.from(songRows).indexOf(element);
        }
    } else if (element.classList.contains('song-card')) {
        // For song cards, create a playlist of sibling song cards if in a container
        const songCardContainer = element.closest('.songs-container, .playlist-container, .album-container, .music-grid');
        if (songCardContainer) {
            const songCards = songCardContainer.querySelectorAll('.song-card');
            window.currentPlaylist = Array.from(songCards);
            window.currentPlaylistIndex = Array.from(songCards).indexOf(element);
        } else {
            // Single song mode
            window.currentPlaylist = [element];
            window.currentPlaylistIndex = 0;
        }
    } else {
        // Default to single item
        window.currentPlaylist = [element];
        window.currentPlaylistIndex = 0;
    }

    // Check if this is a MinIO path and transform it if needed
    if (filePath.includes('minio_path') || filePath.includes('minio_cover_path')) {
        fetch('get_minio_url.php?path=' + encodeURIComponent(filePath))
            .then(response => response.text())
            .then(actualUrl => {
                window.audioPlayer.src = actualUrl;
                window.continuePlaySong(element);
            })
            .catch(error => {
                console.error('Error fetching MinIO URL:', error);
                displayError('Could not access the song file. Please try again.');
                window.isChangingTrack = false;
            });
    } else {
        window.audioPlayer.src = filePath;
        window.continuePlaySong(element);
    }
}

/**
 * Play next track in the playlist
 */
function nextTrack() {
    if (window.currentPlaylist.length > 0 && window.currentPlaylistIndex < window.currentPlaylist.length - 1) {
        window.currentPlaylistIndex++;
        const nextSong = window.currentPlaylist[window.currentPlaylistIndex];
        
        let filePath;
        if (nextSong.classList.contains('song-row')) {
            filePath = nextSong.getAttribute('data-song-file') || 
                      nextSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        } else if (nextSong.classList.contains('song-card')) {
            filePath = nextSong.getAttribute('data-song-file') || 
                      nextSong.querySelector('button[onclick]')?.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1] ||
                      nextSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        } else {
            filePath = nextSong.getAttribute('data-song-file') ||
                      nextSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        }
        
        if (filePath) {
            playSong(filePath, nextSong);
        } else {
            displayError('Could not find next song file path.');
        }
    }
}

/**
 * Play previous track in the playlist
 */
function previousTrack() {
    if (window.currentPlaylist.length > 0 && window.currentPlaylistIndex > 0) {
        window.currentPlaylistIndex--;
        const prevSong = window.currentPlaylist[window.currentPlaylistIndex];

        let filePath;
        if (prevSong.classList.contains('song-row')) {
            filePath = prevSong.getAttribute('data-song-file') || 
                      prevSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        } else if (prevSong.classList.contains('song-card')) {
            filePath = prevSong.getAttribute('data-song-file') || 
                      prevSong.querySelector('button[onclick]')?.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1] ||
                      prevSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        } else {
            filePath = prevSong.getAttribute('data-song-file') ||
                      prevSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        }
        
        if (filePath) {
            playSong(filePath, prevSong);
        } else {
            displayError('Could not find previous song file path.');
        }
    } else if (window.audioPlayer.currentTime > 3) {
        // If at the beginning of playlist but song has played for more than 3 seconds, restart current song
        window.audioPlayer.currentTime = 0;
    }
}

/**
 * Play an entire album
 * @param {HTMLElement} albumSection - Album section element
 */
function playAlbum(albumSection) {
    const songRows = albumSection.querySelectorAll('.song-row');
    if (songRows.length > 0) {
        window.currentPlaylist = Array.from(songRows);
        window.currentPlaylistIndex = 0;
        
        const firstSong = songRows[0];
        const filePath = firstSong.getAttribute('data-song-file') || 
                         firstSong.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1] ||
                         '';
        
        if (filePath) {
            playSong(filePath, firstSong);
        } else {
            displayError('Could not find song file path.');
        }
    }
}

// Export functions to window for global access
window.formatTime = formatTime;
window.updatePlayPauseButton = updatePlayPauseButton;
window.displayError = displayError;
window.trackSongPlay = trackSongPlay;
window.playSong = playSong;
window.nextTrack = nextTrack;
window.previousTrack = previousTrack;
window.playAlbum = playAlbum;