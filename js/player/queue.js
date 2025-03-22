/**
 * Player Queue Module
 * Handles queue functionality for the audio player
 */

// queue if it doesnt exist
if (!window.songQueue) {
    window.songQueue = []; // Song queue array
}

/**
 * Add a song to the queue
 * @param {string} filePath - Path to the audio file
 * @param {HTMLElement} element - DOM element containing song data
 */
function addToQueue(filePath, element) {
    // Extract song info
    let songTitle, artistName, coverArt, songId;
    
    if (element.classList.contains('song-row')) {
        songTitle = element.getAttribute('data-song-title') || element.querySelector('.song-row-title')?.textContent || 'Unknown Title';
        artistName = element.getAttribute('data-song-artist') || element.querySelector('.song-row-artist')?.textContent || 'Unknown Artist';
        songId = element.getAttribute('data-song-id');
        
        // Get cover art
        const albumSection = element.closest('.album-section');
        coverArt = albumSection ? albumSection.querySelector('.album-cover')?.src : 'defaults/default-cover.jpg';
    } else if (element.classList.contains('song-card')) {
        songTitle = element.querySelector('.song-card-title, .song-title')?.textContent || 'Unknown Title';
        artistName = element.querySelector('.song-card-artist, .artist-link')?.textContent || 'Unknown Artist';
        songId = element.getAttribute('data-song-id');
        coverArt = element.querySelector('.song-card-image, .cover-art')?.src || 'defaults/default-cover.jpg';
    } else {
        songTitle = element.getAttribute('data-song-title') || 'Unknown Title';
        artistName = element.getAttribute('data-song-artist') || 'Unknown Artist';
        songId = element.getAttribute('data-song-id');
        coverArt = element.getAttribute('data-cover-art') || 'defaults/default-cover.jpg';
    }
    
    // Add to queue
    window.songQueue.push({
        filePath: filePath,
        title: songTitle,
        artist: artistName,
        songId: songId,
        cover: coverArt
    });
    
    // Show notification
    showQueueNotification(songTitle, artistName);
    
    // Update queue count on queue button
    updateQueueButtonCount();
    
    // Update queue display if it's open
    if (document.getElementById('queueView').classList.contains('active')) {
        updateQueueDisplay();
    }
}

/**
 * Update the queue button count indicator
 */
function updateQueueButtonCount() {
    const queueBtn = document.getElementById('queueToggle');
    if (window.songQueue.length > 0) {
        queueBtn.classList.add('has-items');
        queueBtn.setAttribute('data-count', window.songQueue.length);
    } else {
        queueBtn.classList.remove('has-items');
    }
}

/**
 * Play the next song from the queue
 * @returns {boolean} - Whether a song was played from the queue
 */
function playNextFromQueue() {
    if (window.songQueue.length > 0) {
        playSongFromQueue(0);
        return true;
    }
    return false;
}

/**
 * Play a specific song from the queue
 * @param {number} index - Index in the queue to play
 */
function playSongFromQueue(index) {
    if (index >= 0 && index < window.songQueue.length) {
        const song = window.songQueue[index];
        
        // Create a mock element with the song data
        const mockElement = document.createElement('div');
        mockElement.setAttribute('data-song-title', song.title);
        mockElement.setAttribute('data-song-artist', song.artist);
        mockElement.setAttribute('data-song-id', song.songId);
        mockElement.setAttribute('data-cover-art', song.cover || 'defaults/default-cover.jpg');
        
        // Play the song
        window.playSong(song.filePath, mockElement);
        
        // Remove from queue
        window.songQueue.splice(index, 1);
        updateQueueButtonCount();
        updateQueueDisplay();
    }
}

/**
 * Show notification when a song is added to the queue
 * @param {string} title - Song title
 * @param {string} artist - Artist name
 */
function showQueueNotification(title, artist) {
    const notification = document.createElement('div');
    notification.className = 'queue-notification';
    notification.innerHTML = `
        <div class="queue-notification-icon">
            <i class="fas fa-list"></i>
        </div>
        <div class="queue-notification-content">
            <div class="queue-notification-title">Added to Queue</div>
            <div class="queue-notification-song">${title} - ${artist}</div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Update the queue display in the UI
 */
function updateQueueDisplay() {
    const queueList = document.getElementById('queueList');
    if (!queueList) return;
    
    queueList.innerHTML = '';
    
    if (window.songQueue.length === 0) {
        queueList.innerHTML = '<li class="empty-queue">Your queue is empty</li>';
        return;
    }
    
    window.songQueue.forEach((song, index) => {
        const queueItem = document.createElement('li');
        queueItem.className = 'queue-item';
        queueItem.innerHTML = `
            <img src="${song.cover || 'defaults/default-cover.jpg'}" class="queue-item-image" alt="${song.title}">
            <div class="queue-item-info">
                <div class="queue-item-title">${song.title}</div>
                <div class="queue-item-artist">${song.artist}</div>
            </div>
            <div class="queue-item-actions">
                <button class="queue-remove" data-index="${index}" title="Remove from queue">✕</button>
            </div>
        `;
        queueList.appendChild(queueItem);
        
        // Add click event to remove button
        queueItem.querySelector('.queue-remove').addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the items click
            const removeIndex = parseInt(this.getAttribute('data-index'));
            window.songQueue.splice(removeIndex, 1);
            updateQueueButtonCount();
            updateQueueDisplay();
        });
        
        // Add click event to play this song
        queueItem.addEventListener('click', function() {
            playSongFromQueue(index);
        });
    });
}

/**
 * queue UI and controls
 */
function initializeQueueUI() {
    // Set up queue toggle button
    const queueToggle = document.getElementById('queueToggle');
    if (queueToggle) {
        queueToggle.addEventListener('click', function() {
            const queueView = document.getElementById('queueView');
            if (queueView) {
                queueView.classList.toggle('active');
                if (queueView.classList.contains('active')) {
                    updateQueueDisplay();
                }
            }
        });
    }
    
    // Set up queue close button
    const queueClose = document.querySelector('.queue-close');
    if (queueClose) {
        queueClose.addEventListener('click', function() {
            document.getElementById('queueView').classList.remove('active');
        });
    }
    
    // Update queue button count on load
    updateQueueButtonCount();
}

/**
 * Add queue buttons to song elements
 */
function addQueueButtonsToSongs() {
    // Add queue button to song cards
    document.querySelectorAll('.song-card').forEach(card => {
        // Check if card already has a queue button
        if (!card.querySelector('.queue-add-btn')) {
            const actionsDiv = card.querySelector('.song-card-actions') || 
                             card.querySelector('.song-actions');
            
            if (actionsDiv) {
                // Extract file path from onclick attribute
                const onclickAttr = card.getAttribute('onclick') || '';
                const matchResult = onclickAttr.match(/'([^']+)'/);
                const filePath = matchResult ? matchResult[1] : '';
                
                // Create queue button
                const queueBtn = document.createElement('button');
                queueBtn.className = 'queue-add-btn';
                queueBtn.innerHTML = '<i class="fas fa-plus"></i>';
                queueBtn.title = 'Add to queue';
                
                // Add click handler
                queueBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent triggering the cards playSong function
                    addToQueue(filePath, card);
                });
                
                actionsDiv.appendChild(queueBtn);
            }
        }
    });
    
    // Add queue button to song rows
    document.querySelectorAll('.song-row').forEach(row => {
        // Check if row already has a queue button
        if (!row.querySelector('.queue-add-btn')) {
            const actionsDiv = row.querySelector('.song-action-buttons');
            
            if (actionsDiv) {
                // Extract file path from onclick attribute
                const onclickAttr = row.getAttribute('onclick') || '';
                const matchResult = onclickAttr.match(/'([^']+)'/);
                const filePath = matchResult ? matchResult[1] : '';
                
                // Create queue button
                const queueBtn = document.createElement('button');
                queueBtn.className = 'queue-add-btn';
                queueBtn.innerHTML = '<i class="fas fa-plus"></i>';
                queueBtn.title = 'Add to queue';
                
                // Add click handler
                queueBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent triggering the rows playSong function
                    addToQueue(filePath, row);
                });
                
                actionsDiv.appendChild(queueBtn);
            }
        }
    });
}

/**
 * Set up observer to detect new song elements
 */
function setupSongElementObserver() {
    // Create mutation observer to watch for new elements
    const observer = new MutationObserver(function(mutations) {
        let shouldAddButtons = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && 
                            (node.classList.contains('song-card') || 
                             node.classList.contains('song-row') ||
                             node.querySelector('.song-card') ||
                             node.querySelector('.song-row'))) {
                            shouldAddButtons = true;
                        }
                    }
                });
            }
        });
        
        if (shouldAddButtons) {
            setTimeout(addQueueButtonsToSongs, 100);
        }
    });
    
    // Start observing document body
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Initial run
    setTimeout(addQueueButtonsToSongs, 100);
}

// Export functions to window for global access
window.addToQueue = addToQueue;
window.playNextFromQueue = playNextFromQueue;
window.playSongFromQueue = playSongFromQueue;
window.updateQueueDisplay = updateQueueDisplay;
window.initializeQueueUI = initializeQueueUI;
window.setupSongElementObserver = setupSongElementObserver;