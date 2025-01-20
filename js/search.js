document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('artistSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 1) {
            searchResults.style.display = 'none';
            return;
        }

        // Show loading state
        searchResults.style.display = 'block';
        searchResults.innerHTML = `
            <div class="search-loading">
                <div class="shimmer-item"></div>
                <div class="shimmer-item"></div>
                <div class="shimmer-item"></div>
            </div>
        `;

        // Add debounce to prevent too many requests
        searchTimeout = setTimeout(() => {
            fetch(`/../handlers/search_handler.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data, query);
                })
                .catch(error => console.error('Error:', error));
        }, 300);
    });

    function displayResults(data, query) {
        searchResults.innerHTML = '';
        let hasResults = false;

        // Check if there are any results
        const totalResults = data.artists.length + data.songs.length + data.users.length;
        
        if (totalResults === 0) {
            searchResults.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">😕</div>
                    <div class="no-results-text">
                        <p>There is nothing we could find for "${query}"</p>
                        <span>Try searching for something else</span>
                    </div>
                </div>
            `;
            return;
        }

        // Display Artists
        if (data.artists.length > 0) {
            hasResults = true;
            const artistSection = createSection('Artists');
            data.artists.forEach(artist => {
                const item = createResultItem(
                    artist.artist,
                    'artist',
                    `artist?name=${encodeURIComponent(artist.artist)}`,
                    user.profile_picture || 'defaults/default-profile.jpg'
                );
                artistSection.appendChild(item);
            });
            searchResults.appendChild(artistSection);
        }

        // Display Songs
        if (data.songs.length > 0) {
            hasResults = true;
            const songSection = createSection('Songs');
            data.songs.forEach(song => {
                const item = createResultItem(
                    `${song.title}`,
                    'song',
                    `https://alpha.matsfx.com/song?song_id=${song.song_id}`,
                    song.cover_art || 'defaults/default-cover.jpg',
                    song.artist
                );
                songSection.appendChild(item);
            });
            searchResults.appendChild(songSection);
        }

        // Display Users
        if (data.users.length > 0) {
            hasResults = true;
            const userSection = createSection('Users');
            data.users.forEach(user => {
                const item = createResultItem(
                    user.username,
                    'user',
                    `artist?name=${encodeURIComponent(user.username)}`,
                    user.profile_picture || 'defaults/default-profile.jpg'
                );
                userSection.appendChild(item);
            });
            searchResults.appendChild(userSection);
        }

        searchResults.style.display = hasResults ? 'block' : 'none';
    }

    function createSection(title) {
        const section = document.createElement('div');
        section.className = 'search-section';
        const heading = document.createElement('h3');
        heading.textContent = title;
        section.appendChild(heading);
        return section;
    }

    function createResultItem(text, type, link, image, subtitle = '') {
        const item = document.createElement('a');
        item.href = link;
        item.className = 'search-result-item';
        item.innerHTML = `
            <img src="${image}" alt="${text}" class="result-image" onerror="this.src='defaults/default-cover.jpg'">
            <div class="result-info">
                <div class="result-name">${text}</div>
                ${subtitle ? `<div class="result-subtitle">${subtitle}</div>` : ''}
                <div class="result-type">${type}</div>
            </div>
        `;
        return item;
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.style.display = 'none';
        }
    });
});
