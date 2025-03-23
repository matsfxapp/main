document.addEventListener('DOMContentLoaded', function() {
    const genrePills = document.querySelectorAll('#genre-pills .category-pill');
    const genreContainer = document.getElementById('genre-songs-container');
    
    genrePills.forEach(pill => {
        pill.addEventListener('click', function() {
            genrePills.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            
            // Get genre name
            const genre = this.getAttribute('data-genre');

            genreContainer.innerHTML = '<div class="loading-indicator">Loading...</div>';
            
            // Fetch songs for this genre
            fetch(`handlers/get_genre_songs.php?genre=${encodeURIComponent(genre)}`)
                .then(response => response.json())
                .then(data => {
                    genreContainer.innerHTML = '';
                    
                    if (data.length === 0) {
                        genreContainer.innerHTML = '<div class="no-songs-message">No songs found for this genre</div>';
                        return;
                    }
                    
                    // Add songs to container
                    data.forEach(song => {
                        const songCard = document.createElement('div');
                        songCard.className = 'song-card';
                        songCard.setAttribute('onclick', `playSong('${song.file_path}', this)`);
                        songCard.setAttribute('data-song-title', song.title);
                        songCard.setAttribute('data-song-artist', song.artist);
                        songCard.setAttribute('data-song-id', song.song_id);
                        
                        songCard.innerHTML = `
                            <img src="${song.cover_art || 'defaults/default-cover.jpg'}" alt="Cover Art" class="cover-art">
                            <div class="song-title">${song.title}</div>
                            <div class="song-artist">
                                <a href="artist?name=${encodeURIComponent(song.artist)}" class="artist-link">
                                    ${song.artist}
                                </a>
                            </div>
                            <div class="song-stats">
                                <span class="play-count">
                                    <i class="fas fa-play-circle"></i> ${Number(song.play_count).toLocaleString()}
                                </span>
                            </div>
                        `;
                        
                        genreContainer.appendChild(songCard);
                    });
                })
                .catch(error => {
                    console.error('Error fetching genre songs:', error);
                    genreContainer.innerHTML = '<div class="error-message">Error loading songs</div>';
                });
        });
    });
});