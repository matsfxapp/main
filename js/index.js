// Global audio player instance
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

// Main playSong function - moved outside DOMContentLoaded to be globally accessible
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

function initializeArtistSections() {
    const artistSections = document.querySelectorAll('.artist-section');

    artistSections.forEach(section => {
        const container = section.querySelector('.artist-songs-container');
        const prevButton = section.querySelector('.nav-prev');
        const nextButton = section.querySelector('.nav-next');

        if (prevButton && container) {
            prevButton.addEventListener('click', () => {
                container.scrollBy({
                    left: -container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (nextButton && container) {
            nextButton.addEventListener('click', () => {
                container.scrollBy({
                    left: container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (container) {
            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                container.scrollBy({
                    left: e.deltaY < 0 ? -container.clientWidth : container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }
    });
}

function initializeArtistSearch() {
    const artistSearch = document.getElementById('artistSearch');
    const searchResults = document.getElementById('searchResults');
    const artistProfile = document.getElementById('artistProfile');
    const mainMusicGrid = document.querySelector('.music-grid:not(#artistSongs)');
    let searchTimeout;
    let searchedArtists = [];

    if (artistSearch && searchResults) {
        artistSearch.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);

            if (this.value.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`search_artist.php?search=${encodeURIComponent(this.value)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        searchedArtists = [];

                        if (data.length === 0) {
                            searchResults.innerHTML = '<div class="search-result-item no-results">No results found</div>';
                        } else {
                            data.forEach(result => {
                                if (!searchedArtists.includes(result.name)) {
                                    const resultItem = createSearchResultItem(result);
                                    resultItem.addEventListener('click', () => showArtistProfile(result.name));
                                    searchResults.appendChild(resultItem);
                                    searchedArtists.push(result.name);
                                }
                            });
                        }

                        searchResults.style.display = 'block';
                    })
                    .catch(error => console.error('Search error:', error));
            }, 300);
        });
    }

    // Close search results on outside click
    document.addEventListener('click', function(event) {
        if (searchResults && !searchResults.contains(event.target) && event.target !== artistSearch) {
            searchResults.style.display = 'none';
        }
    });

    // Logo click handler
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.addEventListener('click', function(e) {
            e.preventDefault();
            artistProfile.style.display = 'none';
            mainMusicGrid.style.display = 'grid';
            if (artistSearch) artistSearch.value = '';
        });
    }
}

// Utility functions
function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

function extractFilePath(onclickAttribute) {
    const match = onclickAttribute.match(/'([^']*)'/);
    return match ? match[1] : null;
}

function createSearchResultItem(result) {
    const resultItem = document.createElement('div');
    resultItem.className = 'search-result-item';
    resultItem.innerHTML = `
        <img src="implement-artist-profile-php-thingy-here" alt="${result.name}" class="result-image">
        <div class="result-info">
            <div class="result-name">${result.name}</div>
            <div class="result-type">${result.type}</div>
        </div>
    `;
    return resultItem;
}

function showArtistProfile(artistName) {
    const artistProfile = document.getElementById('artistProfile');
    const mainMusicGrid = document.querySelector('.music-grid:not(#artistSongs)');
    const searchResults = document.getElementById('searchResults');

    Promise.all([
        fetch(`get_artist_songs.php?artist=${encodeURIComponent(artistName)}`).then(res => res.json()),
        fetch(`get_artist_profile.php?artist=${encodeURIComponent(artistName)}`).then(res => res.json())
    ])
        .then(([songData, profileData]) => {
            document.getElementById('artistName').textContent = artistName;
            document.getElementById('songCount').textContent = `${songData.length} Songs`;
            document.getElementById('artistImage').src = profileData.profile_picture || '/api/placeholder/180/180';

            const artistSongs = document.getElementById('artistSongs');
            artistSongs.innerHTML = songData.map(song => `
                <div class="song-card" onclick="playSong('${song.file_path}', this)">
                    <img src="${song.cover_art}" alt="Cover Art" class="cover-art">
                    <div class="song-title">${song.title}</div>
                    <div class="song-artist">${song.artist}</div>
                </div>
            `).join('');

            searchResults.style.display = 'none';
            artistProfile.style.display = 'block';
            mainMusicGrid.style.display = 'none';
        })
        .catch(error => console.error('Error loading artist profile:', error));
}