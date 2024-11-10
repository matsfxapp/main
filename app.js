document.addEventListener('DOMContentLoaded', function() {
    const audioPlayer = document.getElementById('audio-player');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const stopBtn = document.querySelector('.stop-btn');
    let currentSongCard = null;

    // Function to handle play/pause
    function togglePlay(songCard) {
        const songPath = songCard.dataset.songPath;
        
        if (currentSongCard === songCard && !audioPlayer.paused) {
            audioPlayer.pause();
            playPauseBtn.textContent = 'Play';
            songCard.querySelector('.play-btn').textContent = 'Play';
        } else {
            if (currentSongCard) {
                currentSongCard.querySelector('.play-btn').textContent = 'Play';
            }
            
            if (currentSongCard !== songCard) {
                audioPlayer.src = songPath;
            }
            
            audioPlayer.play()
                .then(() => {
                    playPauseBtn.textContent = 'Pause';
                    songCard.querySelector('.play-btn').textContent = 'Pause';
                    currentSongCard = songCard;
                })
                .catch(error => {
                    console.error('Error playing audio:', error);
                    alert('Error playing audio. Please try again.');
                });
        }
    }

    // Handle play buttons in song cards
    document.querySelectorAll('.song-card .play-btn').forEach(button => {
        button.addEventListener('click', function() {
            const songCard = this.closest('.song-card');
            togglePlay(songCard);
        });
    });

    // Handle main player controls
    playPauseBtn.addEventListener('click', function() {
        if (currentSongCard) {
            togglePlay(currentSongCard);
        }
    });

    stopBtn.addEventListener('click', function() {
        if (audioPlayer.src) {
            audioPlayer.pause();
            audioPlayer.currentTime = 0;
            playPauseBtn.textContent = 'Play';
            if (currentSongCard) {
                currentSongCard.querySelector('.play-btn').textContent = 'Play';
            }
        }
    });

    // Handle song end
    audioPlayer.addEventListener('ended', function() {
        playPauseBtn.textContent = 'Play';
        if (currentSongCard) {
            currentSongCard.querySelector('.play-btn').textContent = 'Play';
        }
    });

    // Handle likes
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            const songId = this.dataset.songId;
            const likesCount = this.querySelector('.likes-count');
            
            fetch('like_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `song_id=${songId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    likesCount.textContent = data.likes_count;
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Handle playlist additions
    document.querySelectorAll('.add-to-playlist').forEach(button => {
        button.addEventListener('click', function() {
            const songId = this.dataset.songId;
            const playlistSelect = this.previousElementSibling;
            const playlistId = playlistSelect.value;
            
            fetch('playlist_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `song_id=${songId}&playlist_id=${playlistId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Song added to playlist!');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.song-card').forEach(card => {
                const title = card.querySelector('.song-title').textContent.toLowerCase();
                const artist = card.querySelector('.song-artist').textContent.toLowerCase();
                if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});