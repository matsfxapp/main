function shareSong(shareCode, title, artist, coverArt) {
    const shareUrl = `${window.location.origin}/song?share=${shareCode}`;
    
    if (navigator.share) {
    if (coverArt && navigator.canShare) {
        fetch(coverArt)
        .then(res => res.blob())
        .then(blob => {
            const file = new File([blob], 'cover.jpg', { type: 'image/jpeg' });
            const shareData = {
            title: `${title} by ${artist} - matSFX`,
            text: `Listen to ${title} by ${artist} on matSFX!`,
            url: shareUrl,
            files: [file]
            };
            
            if (navigator.canShare(shareData)) {
            navigator.share(shareData)
                .catch(error => {
                console.error('Error sharing with file:', error);
                navigator.share({
                    title: `${title} by ${artist} - matSFX`,
                    text: `Listen to ${title} by ${artist} on matSFX!`,
                    url: shareUrl
                }).catch(error => {
                    console.error('Error sharing without file:', error);
                    fallbackShare(shareUrl, title, artist, coverArt);
                });
                });
            } else {
            navigator.share({
                title: `${title} by ${artist} - matSFX`,
                text: `Listen to ${title} by ${artist} on matSFX!`,
                url: shareUrl
            }).catch(error => {
                console.error('Error sharing:', error);
                fallbackShare(shareUrl, title, artist, coverArt);
            });
            }
        })
        .catch(error => {
            console.error('Error fetching cover art:', error);
            navigator.share({
            title: `${title} by ${artist} - matSFX`,
            text: `Listen to ${title} by ${artist} on matSFX!`,
            url: shareUrl
            }).catch(error => {
            console.error('Error sharing:', error);
            fallbackShare(shareUrl, title, artist, coverArt);
            });
        });
    } else {
        navigator.share({
        title: `${title} by ${artist} - matSFX`,
        text: `Listen to ${title} by ${artist} on matSFX!`,
        url: shareUrl
        }).catch(error => {
        console.error('Error sharing:', error);
        fallbackShare(shareUrl, title, artist, coverArt);
        });
    }
    } else {
    fallbackShare(shareUrl, title, artist, coverArt);
    }
}

function fallbackShare(shareUrl, title, artist, coverArt) {
    const modal = document.createElement('div');
    modal.className = 'share-modal';
    
    const coverArtHtml = coverArt ? 
    `<div class="share-cover-art">
        <img src="${coverArt}" alt="${title} cover" />
    </div>` : '';
    
    modal.innerHTML = `
    <div class="share-modal-content">
        <div class="share-modal-header">
        <h3>Share "${title}" by ${artist}</h3>
        <button class="close-modal">&times;</button>
        </div>
        ${coverArtHtml}
        <div class="share-modal-body">
        <input type="text" class="share-link-input" value="${shareUrl}" readonly>
        <button class="copy-link-btn">
            <i class="fas fa-copy"></i> Copy Link
        </button>
        <div class="social-share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}" target="_blank" class="social-share facebook">
            <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(`Listen to ${title} by ${artist} on matSFX!`)}" target="_blank" class="social-share twitter">
            <i class="fab fa-twitter"></i>
            </a>
            <a href="https://wa.me/?text=${encodeURIComponent(`Check out ${title} by ${artist} on matSFX: ${shareUrl}`)}" target="_blank" class="social-share whatsapp">
            <i class="fab fa-whatsapp"></i>
            </a>
        </div>
        </div>
    </div>
    `;
    
    document.body.appendChild(modal);
    
    const copyBtn = modal.querySelector('.copy-link-btn');
    const linkInput = modal.querySelector('.share-link-input');
    copyBtn.addEventListener('click', () => {
    linkInput.select();
    document.execCommand('copy');
    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(() => {
        copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy Link';
    }, 2000);
    });
    
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.addEventListener('click', () => {
    document.body.removeChild(modal);
    });
    
    modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        document.body.removeChild(modal);
    }
    });
}