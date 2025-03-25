/**
 * Upload Success Page Animations and Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    animateSuccessElements();
    
    setupShareFeatures();
    
    setupSongPreview();
});

/**
 * Animate success elements with staggered animation
 */
function animateSuccessElements() {
    const elements = [
        document.querySelector('.success-icon'),
        document.querySelector('.song-details'),
        document.querySelector('.success-message'),
        document.querySelector('.share-section'),
        document.querySelector('.success-actions')
    ];
    
    // Filter out null elements
    const validElements = elements.filter(el => el !== null);
    
    // Apply staggered animation
    validElements.forEach((el, index) => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            // Force reflow
            void el.offsetWidth;
            
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 150);
    });
}

/**
 * Set up share features
 */
function setupShareFeatures() {
    // Copy share link button
    const copyBtn = document.querySelector('.share-copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const linkInput = document.getElementById('shareLink');
            if (!linkInput) return;
            
            linkInput.select();
            document.execCommand('copy');
            
            // Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Add share click tracking for analytics
    const shareButtons = document.querySelectorAll('.share-icon');
    shareButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const platform = this.classList.contains('twitter') ? 'Twitter' : 
                             this.classList.contains('facebook') ? 'Facebook' : 
                             this.classList.contains('whatsapp') ? 'WhatsApp' : 'Unknown';
            
            console.log(`Song shared on ${platform}`);
            
            if (platform === 'WhatsApp' && !navigator.share) {
                return true;
            }
            
            if (navigator.share && platform !== 'WhatsApp') {
                e.preventDefault();
                
                const shareData = {
                    title: document.querySelector('.song-title')?.textContent || 'My song on matSFX',
                    text: `Check out my song "${document.querySelector('.song-title')?.textContent}" on matSFX!`,
                    url: document.getElementById('shareLink')?.value || window.location.href
                };
                
                navigator.share(shareData)
                    .catch(error => console.log('Error sharing:', error));
            }
        });
    });
}

/**
 * Set up song preview play functionality
 */
function setupSongPreview() {
    const previewBtn = document.querySelector('.song-preview-play');
    if (!previewBtn) return;
    
    previewBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const filePath = this.getAttribute('data-song-file') || 
                        this.getAttribute('onclick')?.toString().match(/'([^']+)'/)?.[1];
        
        if (filePath && typeof window.playSong === 'function') {
            window.playSong(filePath, this);
        }
    });
    
    // Add hover effect for preview button
    const previewImg = document.querySelector('.song-preview-image');
    if (previewImg) {
        previewImg.addEventListener('mouseenter', function() {
            const playBtn = this.parentElement.querySelector('.song-preview-play');
            if (playBtn) {
                playBtn.style.opacity = '1';
            }
        });
        
        previewImg.addEventListener('mouseleave', function() {
            const playBtn = this.parentElement.querySelector('.song-preview-play');
            if (playBtn) {
                playBtn.style.opacity = '0';
            }
        });
    }
}