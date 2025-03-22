/**
 * Player UI Module
 * Handles UI elements and controls for the audio player
 */

/**
 * Initialize volume control
 */
function initializeVolumeControl() {
    const volumeIcon = document.getElementById('volumeIcon');
    const volumePopup = document.getElementById('volumeControlPopup');
    
    if (!volumeIcon || !volumePopup) return;
    
    // Toggle volume popup
    volumeIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        volumePopup.classList.toggle('active');
    });
    
    // Update volume
    const volumeSlider = document.getElementById('volumeSlider');
    if (volumeSlider) {
        volumeSlider.addEventListener('input', function() {
            const volume = this.value / 100;
            window.audioPlayer.volume = volume;
            
            // Save to localStorage
            localStorage.setItem('playerVolume', volume.toString());
            
            // Update icon based on volume level
            if (volume === 0) {
                volumeIcon.innerHTML = '<i class="fas fa-volume-mute"></i>';
            } else if (volume < 0.5) {
                volumeIcon.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                volumeIcon.innerHTML = '<i class="fas fa-volume-up"></i>';
            }
        });
    }
    
    // Close volume popup when clicking outside
    document.addEventListener('click', function(e) {
        if (!volumeIcon.contains(e.target) && !volumePopup.contains(e.target)) {
            volumePopup.classList.remove('active');
        }
    });
}

/**
 * Initialize context menu for song elements
 */
function initializeContextMenu() {
    const contextMenu = document.getElementById('songContextMenu');
    if (!contextMenu) return;
    
    // Set up context menu on all song elements
    document.querySelectorAll('.song-card, .song-row').forEach(element => {
        element.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            // Display menu at click position
            contextMenu.style.display = 'block';
            contextMenu.style.left = e.pageX + 'px';
            contextMenu.style.top = e.pageY + 'px';
            
            // Extract file path from onclick attribute
            const onclickAttr = this.getAttribute('onclick') || '';
            const matchResult = onclickAttr.match(/'([^']+)'/);
            const filePath = matchResult ? matchResult[1] : '';
            
            // Store reference to clicked element and file path
            contextMenu.dataset.element = this.getAttribute('data-song-id');
            contextMenu.dataset.filepath = filePath;
            
            // Close menu when clicking elsewhere
            document.addEventListener('click', closeContextMenu);
        });
    });
}

/**
 * Close the context menu
 */
function closeContextMenu() {
    const menu = document.getElementById('songContextMenu');
    if (menu) {
        menu.style.display = 'none';
        document.removeEventListener('click', closeContextMenu);
    }
}

/**
 * Initialize all player UI components
 */
function initializePlayerUI() {
    initializeVolumeControl();
    initializeContextMenu();
    window.initializeQueueUI();
    window.setupSongElementObserver();
}

// Export functions to window for global access
window.initializePlayerUI = initializePlayerUI;
window.closeContextMenu = closeContextMenu;