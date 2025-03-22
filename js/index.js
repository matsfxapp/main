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

document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const songsGrid = document.getElementById('popularSongsGrid');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Get the selected period
            const period = this.getAttribute('data-period');
            
            // Add loading state to grid
            songsGrid.classList.add('loading');
            
            // Fetch new data based on period
            fetch(`get_popular_songs.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    // Clear the grid
                    songsGrid.innerHTML = '';
                    
                    // Add new songs
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
                        
                        songsGrid.appendChild(songCard);
                    });
                })
                .catch(error => {
                    console.error('Error fetching popular songs:', error);
                })
                .finally(() => {
                    songsGrid.classList.remove('loading');
                });
        });
    });
});