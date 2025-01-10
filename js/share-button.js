document.addEventListener('DOMContentLoaded', function() {
    function addShareButtons() {
        const songCards = document.querySelectorAll('.song-card');

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
		const shareableLink = `https://alpha.matsfx.com/song?song_id={$song_id}`;
        
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