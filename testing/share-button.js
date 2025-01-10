document.addEventListener('DOMContentLoaded', function() {
    function addShareButtons() {
        const songCards = document.querySelectorAll('.song-card');
        
        songCards.forEach(card => {
            const shareButton = document.createElement('button');
            shareButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                    <polyline points="16 6 12 2 8 6"/>
                    <line x1="12" x2="12" y1="2" y2="15"/>
                </svg>
                Share
            `;
            shareButton.classList.add('btn-share');

            shareButton.addEventListener('click', (e) => {
                e.stopPropagation();

                const title = card.dataset.songTitle;
                const artist = card.dataset.songArtist;

                copyShareableLink(title, artist);
            });

            const controlsContainer = card.querySelector('.song-controls');
            if (controlsContainer) {
                controlsContainer.appendChild(shareButton);
            }
        });
    }

    function copyShareableLink(title, artist) {
        const shareableLink = `https://alpha.matsfx.com/song?title=${encodeURIComponent(title)}&artist=${encodeURIComponent(artist)}`;
        
        navigator.clipboard.writeText(shareableLink)
            .then(() => {
                showNotification('Song link copied!');
            })
            .catch(err => {
                console.error('Failed to copy link:', err);
                showNotification('Failed to copy link');
            });
    }

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.classList.add('notification');
        document.body.appendChild(notification);

        setTimeout(() => {
            document.body.removeChild(notification);
        }, 3000);
    }

    addShareButtons();
});