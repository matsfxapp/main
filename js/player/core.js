/**
 * Initialize player on document load
 */
function initializePlayer() {
    const progressBar = document.getElementById('progress');
    const volumeSlider = document.getElementById('volumeSlider');

    initializePlayerControls(audioPlayer, progressBar);
    
    const savedVolume = localStorage.getItem('playerVolume');
    if (savedVolume !== null) {
        audioPlayer.volume = parseFloat(savedVolume);
        volumeSlider.value = parseFloat(savedVolume) * 100;
    } else {
        audioPlayer.volume = 0.7; // Default volume
        volumeSlider.value = 70;
    }
    
    // Load last played song from localStorage
    const lastSong = localStorage.getItem('lastPlayedSong');
    const lastPosition = localStorage.getItem('lastPlayedPosition');
    
    if (lastSong) {
        lastPlayedSong = JSON.parse(lastSong);
        lastPlayedPosition = parseFloat(lastPosition || 0);
        
        // Load last song info into player without playing it
        loadLastSongInfo(lastPlayedSong);
    }
    
    // Check if a song was playing before navigation
    const wasPlaying = localStorage.getItem('wasPlaying') === 'true';
    const currentSrc = localStorage.getItem('currentSrc');
    
    // If we have a current source and it was playing, restore playback
    if (wasPlaying && currentSrc) {
        audioPlayer.src = currentSrc;
        audioPlayer.currentTime = parseFloat(lastPosition || 0);
        
        // Play only if it was playing before
        audioPlayer.play()
            .then(() => {
                updatePlayPauseButton(true);
            })
            .catch(error => {
                console.error('Error resuming playback:', error);
            });
    }
    
    // Setup navigation state tracking
    setupNavigationStateTracking();
}

/**
 * Load last song info into the player UI without playing
 * @param {Object} songData - Last played song data
 */
function loadLastSongInfo(songData) {
    if (!songData) return;
    
    // Update player UI with last played song info
    const albumArt = document.getElementById('player-album-art');
    const songTitle = document.getElementById('songTitle');
    const artistName = document.getElementById('artistName');
    
    if (albumArt) albumArt.src = songData.coverArt || 'defaults/default-cover.jpg';
    if (songTitle) songTitle.textContent = songData.title || 'Unknown Title';
    if (artistName) artistName.textContent = songData.artist || 'Unknown Artist';
    
    // Set audio source but don't play
    if (songData.filePath && !audioPlayer.src) {
        audioPlayer.src = songData.filePath;
        audioPlayer.load(); // Load but don't play
        
        // Set the position if available
        if (lastPlayedPosition && !isNaN(lastPlayedPosition)) {
            audioPlayer.currentTime = lastPlayedPosition;
        }
    }
}

/**
 * Setup tracking of audio state during navigation
 */
function setupNavigationStateTracking() {
    window.addEventListener('beforeunload', function() {
        // Save whether the audio was playing
        localStorage.setItem('wasPlaying', !audioPlayer.paused);
        localStorage.setItem('currentSrc', audioPlayer.src);
        
        // Save position if playing
        if (!audioPlayer.paused && audioPlayer.currentTime > 0) {
            localStorage.setItem('lastPlayedPosition', audioPlayer.currentTime.toString());
        }
    });
    
    // Handle visibility changes (like tab switching)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            // Store state when tab becomes hidden
            localStorage.setItem('wasPlaying', !audioPlayer.paused);
            localStorage.setItem('currentSrc', audioPlayer.src);
            
            if (!audioPlayer.paused && audioPlayer.currentTime > 0) {
                localStorage.setItem('lastPlayedPosition', audioPlayer.currentTime.toString());
            }
        }
    });
}

/**
 * Save current song data to localStorage
 * @param {Object} songData - Song data to save
 */
function saveCurrentSong(songData) {
    lastPlayedSong = songData;
    localStorage.setItem('lastPlayedSong', JSON.stringify(songData));
}

/**
 * Continue playing a song after source is set
 * @param {HTMLElement} element - DOM element that triggered the play
 */
function continuePlaySong(element) {
    let songTitle, artistName, coverArt, songId;

    // Get song ID attribute from the element
    if (element.getAttribute('data-song-id')) {
        songId = element.getAttribute('data-song-id');
    }

    if (element.classList.contains('song-row')) {
        // Handle song rows (from album view)
        songTitle = element.getAttribute('data-song-title') || element.querySelector('.song-row-title')?.textContent || 'Unknown Title';
        artistName = element.getAttribute('data-song-artist') || element.querySelector('.song-row-artist')?.textContent || 'Unknown Artist';
        songId = songId || element.getAttribute('data-song-id');
        
        const albumSection = element.closest('.album-section');
        coverArt = albumSection ? albumSection.querySelector('.album-cover')?.src : 'defaults/default-cover.jpg';
        
        // Update UI to show current playing song
        document.querySelectorAll('.song-row').forEach(row => row.classList.remove('playing'));
        element.classList.add('playing');
    } else if (element.classList.contains('song-card')) {
        // Handle song cards (from discover/search view)
        coverArt = element.querySelector('.song-card-image, .cover-art')?.src || 'defaults/default-cover.jpg';
        songTitle = element.querySelector('.song-card-title, .song-title')?.textContent || 'Unknown Title';
        artistName = element.querySelector('.song-card-artist, .artist-link')?.textContent || 'Unknown Artist';
        songId = songId || element.getAttribute('data-song-id');
        
        // Update UI to show current playing song
        document.querySelectorAll('.song-card').forEach(card => card.classList.remove('active-song'));
        element.classList.add('active-song');
    } else {
        // Handle other element types or direct invocation
        songTitle = element.getAttribute('data-song-title') || 'Unknown Title';
        artistName = element.getAttribute('data-song-artist') || 'Unknown Artist';
        coverArt = element.getAttribute('data-cover-art') || 'defaults/default-cover.jpg';
        songId = songId || element.getAttribute('data-song-id');
    }

    // Update player UI
    document.getElementById('player-album-art').src = coverArt;
    document.getElementById('songTitle').textContent = songTitle;
    document.getElementById('artistName').textContent = artistName;
    
    // Update document title
    document.title = `${songTitle} - by ${artistName}`;
    
    // Save current song information for resume functionality
    saveCurrentSong({
        title: songTitle,
        artist: artistName,
        songId: songId,
        filePath: audioPlayer.src,
        coverArt: coverArt
    });

    // Track song play if we have a song ID
    if (songId) {
        trackSongPlay(songId);
    }

    // Play the audio
    audioPlayer.play()
        .then(() => {
            updatePlayPauseButton(true);
            // Mark as currently playing for page transitions
            localStorage.setItem('wasPlaying', 'true');
            localStorage.setItem('currentSrc', audioPlayer.src);
        })
        .catch(error => {
            console.error('Error playing song:', error);
            displayError('Could not play the song. Please try again.');
        })
        .finally(() => {
            isChangingTrack = false;
        });
}

/**
 * Initialize player controls and event listeners
 * @param {HTMLAudioElement} audioPlayer - Audio element
 * @param {HTMLInputElement} progressBar - Progress slider element
 */
function initializePlayerControls(audioPlayer, progressBar) {
    window.playPause = function() {
        if (audioPlayer.paused) {
            audioPlayer.play()
                .then(() => {
                    updatePlayPauseButton(true);
                    localStorage.setItem('wasPlaying', 'true');
                })
                .catch(error => {
                    console.error('Error playing audio:', error);
                    displayError('Could not play audio');
                });
        } else {
            audioPlayer.pause();
            updatePlayPauseButton(false);
            localStorage.setItem('wasPlaying', 'false');
        }
    };

    audioPlayer.addEventListener('timeupdate', function() {
        const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
        progressBar.value = progress;

        document.getElementById('currentTime').textContent = formatTime(audioPlayer.currentTime);
        document.getElementById('duration').textContent = formatTime(audioPlayer.duration || 0);
        
        // Periodically save current position for resume capability
        if (!audioPlayer.paused && audioPlayer.currentTime > 0) {
            localStorage.setItem('lastPlayedPosition', audioPlayer.currentTime.toString());
        }
    });

    progressBar.addEventListener('input', (e) => {
        const time = (e.target.value / 100) * audioPlayer.duration;
        audioPlayer.currentTime = time;
        localStorage.setItem('lastPlayedPosition', time.toString());
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

    audioPlayer.addEventListener('ended', function() {
        // First try to play from queue
        if (!audioPlayer.loop && !playNextFromQueue()) {
            // If no queue items, continue with regular playlist logic
            if (currentPlaylist.length > 0 && currentPlaylistIndex < currentPlaylist.length - 1) {
                nextTrack();
            } else {
                updatePlayPauseButton(false);
                localStorage.setItem('wasPlaying', 'false');
                // Reset the document title to the default
                document.title = 'matSFX - Music for everyone';
            }
        }
    });
}