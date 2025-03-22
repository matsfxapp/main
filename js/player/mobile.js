/**
 * Mobile Player Module
 * Handles mobile-specific UI and interactions
 */

// Initialize variables
let currentSongData = null;

/**
 * Create bottom sheet for mobile context menu
 */
function createMobileBottomSheet() {
    // Only create if on mobile and doesn't already exist
    if (window.innerWidth > 768 || document.getElementById('songBottomSheet')) {
        return;
    }
    
    // Create backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'sheet-backdrop';
    backdrop.id = 'sheetBackdrop';
    
    // Create bottom sheet
    const sheet = document.createElement('div');
    sheet.className = 'song-bottom-sheet';
    sheet.id = 'songBottomSheet';
    
    sheet.innerHTML = `
        <div class="sheet-handle"></div>
        
        <div class="sheet-song-info">
            <img id="sheetCover" src="/defaults/default-cover.jpg" class="sheet-song-cover" alt="Cover Art">
            <div>
                <div id="sheetTitle" class="sheet-song-title">Song Title</div>
                <div id="sheetArtist" class="sheet-song-artist">Artist Name</div>
            </div>
        </div>
        
        <div class="sheet-actions">
            <button id="sheetPlay" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-play"></i></div>
                <div class="sheet-action-text">Play</div>
            </button>
            
            <button id="sheetAddQueue" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-list"></i></div>
                <div class="sheet-action-text">Add to queue</div>
            </button>
            
            <button id="sheetViewQueue" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-stream"></i></div>
                <div class="sheet-action-text">View queue</div>
            </button>
            
            <button id="sheetLike" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-heart"></i></div>
                <div class="sheet-action-text">Like</div>
            </button>
            
            <button id="sheetShare" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-share-alt"></i></div>
                <div class="sheet-action-text">Share</div>
            </button>
            
            <button id="sheetGoArtist" class="sheet-action">
                <div class="sheet-action-icon"><i class="fas fa-user"></i></div>
                <div class="sheet-action-text">Go to artist</div>
            </button>
        </div>
        
        <div id="sheetCancel" class="sheet-cancel">Cancel</div>
    `;
    
    // Add to DOM
    document.body.appendChild(backdrop);
    document.body.appendChild(sheet);
    
    // Set up event handlers
    setupBottomSheetEvents();
}

/**
 * Set up event handlers for bottom sheet
 */
function setupBottomSheetEvents() {
    const sheet = document.getElementById('songBottomSheet');
    const backdrop = document.getElementById('sheetBackdrop');
    
    // Close when clicking backdrop
    backdrop.addEventListener('click', closeBottomSheet);
    
    // Close when clicking cancel
    document.getElementById('sheetCancel').addEventListener('click', closeBottomSheet);
    
    // Handle swipe down gesture
    const handle = document.querySelector('.sheet-handle');
    handle.addEventListener('touchstart', handleSheetSwipe);
    
    // Set up action buttons
    
    // Play button
    document.getElementById('sheetPlay').addEventListener('click', function() {
        if (currentSongData && currentSongData.filePath && currentSongData.element) {
            window.playSong(currentSongData.filePath, currentSongData.element);
            closeBottomSheet();
        }
    });
    
    // Add to queue button
    document.getElementById('sheetAddQueue').addEventListener('click', function() {
        if (currentSongData && currentSongData.filePath && currentSongData.element) {
            window.addToQueue(currentSongData.filePath, currentSongData.element);
            showToastMessage('Added to queue');
            closeBottomSheet();
        }
    });
    
    // View queue button
    document.getElementById('sheetViewQueue').addEventListener('click', function() {
        // Show the queue view
        const queueView = document.getElementById('queueView');
        if (queueView) {
            queueView.classList.add('active');
            window.updateQueueDisplay();
        }
        
        // Close the bottom sheet
        closeBottomSheet();
    });
    
    // Like button
    document.getElementById('sheetLike').addEventListener('click', function() {
        if (currentSongData && currentSongData.element) {
            // Find like button in the song element
            const likeBtn = currentSongData.element.querySelector('.like-button');
            if (likeBtn && typeof likeBtn.click === 'function') {
                // Use the existing like buttons click handler
                likeBtn.click();
                
                // Update active state in the sheet
                const isLiked = likeBtn.classList.contains('liked');
                this.classList.toggle('active', isLiked);
            }
        }
    });
    
    // Share button
    document.getElementById('sheetShare').addEventListener('click', function() {
        if (currentSongData && currentSongData.element) {
            // Find share button in the song element
            const shareBtn = currentSongData.element.querySelector('.share-btn');
            if (shareBtn && typeof shareBtn.click === 'function') {
                // Use the existing share button's click handler
                shareBtn.click();
            }
            closeBottomSheet();
        }
    });
    
    // Go to artist button
    document.getElementById('sheetGoArtist').addEventListener('click', function() {
        if (currentSongData && currentSongData.artist) {
            window.location.href = `artist?name=${encodeURIComponent(currentSongData.artist)}`;
        }
    });
}

/**
 * Handle swipe gestures on the sheet
 * @param {TouchEvent} e - Touch event
 */
function handleSheetSwipe(e) {
    const sheet = document.getElementById('songBottomSheet');
    const startY = e.touches[0].clientY;
    let currentY;
    
    const touchMove = function(e) {
        currentY = e.touches[0].clientY;
        const diff = currentY - startY;
        
        if (diff > 0) { // Only allow swiping down
            sheet.style.bottom = `-${diff}px`;
        }
    };
    
    const touchEnd = function() {
        if (currentY - startY > 80) { // If swiped down more than 80px
            closeBottomSheet();
        } else {
            sheet.style.bottom = '0'; // Reset position
        }
        
        document.removeEventListener('touchmove', touchMove);
        document.removeEventListener('touchend', touchEnd);
    };
    
    document.addEventListener('touchmove', touchMove);
    document.addEventListener('touchend', touchEnd);
}

/**
 * Open the bottom sheet for a song
 * @param {HTMLElement} songElement - Song element to display in sheet
 */
function openBottomSheet(songElement) {
    // Extract song info
    const songInfo = getSongInfo(songElement);
    currentSongData = songInfo;
    
    // Update sheet content
    document.getElementById('sheetTitle').textContent = songInfo.title;
    document.getElementById('sheetArtist').textContent = songInfo.artist;
    document.getElementById('sheetCover').src = songInfo.cover;
    
    // Check if song is liked
    const likeButton = songElement.querySelector('.like-button');
    if (likeButton) {
        const isLiked = likeButton.classList.contains('liked');
        document.getElementById('sheetLike').classList.toggle('active', isLiked);
    } else {
        document.getElementById('sheetLike').classList.remove('active');
    }
    
    // Show the sheet
    document.getElementById('sheetBackdrop').classList.add('active');
    document.getElementById('songBottomSheet').classList.add('active');
}

/**
 * Close the bottom sheet
 */
function closeBottomSheet() {
    const backdrop = document.getElementById('sheetBackdrop');
    const sheet = document.getElementById('songBottomSheet');
    
    if (backdrop && sheet) {
        backdrop.classList.remove('active');
        sheet.classList.remove('active');
        
        // Reset sheet position
        sheet.style.bottom = null;
    }
}

/**
 * Extract song info from element
 * @param {HTMLElement} element - Song element
 * @returns {Object} - Song data
 */
function getSongInfo(element) {
    // Get title
    const title = element.getAttribute('data-song-title') || 
                  element.querySelector('.song-title, .song-card-title, .song-row-title')?.textContent || 
                  'Unknown Title';
    
    // Get artist
    const artist = element.getAttribute('data-song-artist') || 
                   element.querySelector('.song-artist, .song-card-artist, .song-row-artist, .artist-link')?.textContent || 
                   'Unknown Artist';
    
    // Get song ID
    const songId = element.getAttribute('data-song-id');
    
    // Get cover art
    let cover = '/defaults/default-cover.jpg';
    if (element.classList.contains('song-card')) {
        cover = element.querySelector('.cover-art, .song-card-image')?.src || cover;
    } else if (element.classList.contains('song-row')) {
        // For song rows, check if in album section
        const albumSection = element.closest('.album-section');
        if (albumSection) {
            cover = albumSection.querySelector('.album-cover')?.src || cover;
        } else {
            cover = element.querySelector('img')?.src || cover;
        }
    }
    
    // Get file path from onclick attribute
    const onclickAttr = element.getAttribute('onclick') || '';
    const matchResult = onclickAttr.match(/'([^']+)'/);
    const filePath = matchResult ? matchResult[1] : '';
    
    return {
        title,
        artist,
        cover,
        songId,
        filePath,
        element
    };
}

/**
 * Set up long press detection for song elements
 */
function setupSongLongPress() {
    // Find all song elements
    const songElements = document.querySelectorAll('.song-card, .song-row');
    
    // Add long press detection to each element
    songElements.forEach(addLongPressDetection);
    
    // Set up observer for dynamically added song elements
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // Element node
                    if (node.classList && (node.classList.contains('song-card') || node.classList.contains('song-row'))) {
                        addLongPressDetection(node);
                    } else {
                        // Check for song elements inside the added node
                        const songElements = node.querySelectorAll('.song-card, .song-row');
                        songElements.forEach(addLongPressDetection);
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Add long press detection to an element
 * @param {HTMLElement} element - Element to add long press to
 */
function addLongPressDetection(element) {
    let longPressTimer;
    let startX, startY;
    
    element.addEventListener('touchstart', function(e) {
        // Store start position
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        
        // Set up long press timer
        longPressTimer = setTimeout(() => {
            openBottomSheet(element);
        }, 500); // 500ms for long press
    });
    
    element.addEventListener('touchmove', function(e) {
        // Calculate movement
        const diffX = Math.abs(e.touches[0].clientX - startX);
        const diffY = Math.abs(e.touches[0].clientY - startY);
        
        // If moved more than 10px, cancel long press
        if (diffX > 10 || diffY > 10) {
            clearTimeout(longPressTimer);
        }
    });
    
    element.addEventListener('touchend', function() {
        clearTimeout(longPressTimer);
    });
    
    element.addEventListener('touchcancel', function() {
        clearTimeout(longPressTimer);
    });
}

/**
 * Display a toast message
 * @param {string} message - Message to display
 */
function showToastMessage(message) {
    // Remove existing toast if present
    const existingToast = document.querySelector('.toast-message');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.textContent = message;
    
    // Add to body
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Hide after 2 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 2000);
}

/**
 * mobile features
 */
function initializeMobileFeatures() {
    // Only on mobile
    if (window.innerWidth <= 768) {
        createMobileBottomSheet();
        setupSongLongPress();
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768 && !document.getElementById('songBottomSheet')) {
            createMobileBottomSheet();
            setupSongLongPress();
        }
    });
}

// Export functions to window for global access
window.initializeMobileFeatures = initializeMobileFeatures;
window.openBottomSheet = openBottomSheet;
window.closeBottomSheet = closeBottomSheet;
window.showToastMessage = showToastMessage;