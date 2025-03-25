document.addEventListener('DOMContentLoaded', function() {
    // Handle follow/unfollow button
    const followBtn = document.getElementById('follow-btn');
    
    if (followBtn) {
        followBtn.addEventListener('click', function() {
            const action = followBtn.classList.contains('following') ? 'unfollow' : 'follow';
    
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `follow_action=${action}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    if (action === 'follow') {
                        followBtn.classList.add('following');
                        followBtn.innerHTML = `
                            <i class="fas fa-user-check"></i>
                            <span class="follow-text"><span>Following</span></span>
                        `;
                    } else {
                        followBtn.classList.remove('following');
                        followBtn.innerHTML = `
                            <i class="fas fa-user-plus"></i>
                            <span class="follow-text">Follow</span>
                        `;
                    }
                } else {
                    throw new Error(data.message || 'Failed to update follow status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update follow status');
            });
        });
    }

    // Make album sections collapsible
    const albumHeaders = document.querySelectorAll('.album-header');
    albumHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const albumSection = this.closest('.album-section');
            const songsSection = albumSection.querySelector('.album-songs');
            this.classList.toggle('collapsed');
            
            if (songsSection.style.display === 'none') {
                songsSection.style.display = 'block';
            } else {
                songsSection.style.display = 'none';
            }
        });
    });

    // Add Play All button for albums
    const albumInfoSections = document.querySelectorAll('.album-info');
    albumInfoSections.forEach(info => {
        // Check if the play button already exists
        if (!info.querySelector('.album-play-button')) {
            const playButton = document.createElement('button');
            playButton.className = 'album-play-button';
            playButton.innerHTML = '<i class="fas fa-play"></i> Play All';
            playButton.style.marginLeft = '10px';
            playButton.style.padding = '8px 12px';
            playButton.style.borderRadius = '50px';
            playButton.style.backgroundColor = 'var(--primary-color)';
            playButton.style.color = 'white';
            playButton.style.border = 'none';
            playButton.style.cursor = 'pointer';
            playButton.style.fontSize = '0.85rem';
            playButton.style.fontWeight = '500';
            playButton.style.transition = 'var(--transition)';
            
            playButton.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--primary-hover)';
                this.style.transform = 'scale(1.05)';
            });
            
            playButton.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'var(--primary-color)';
                this.style.transform = 'scale(1)';
            });
            
            playButton.addEventListener('click', function(e) {
                e.stopPropagation(); // Don't trigger album collapse/expand
                const albumSection = this.closest('.album-section');
                if (albumSection) {
                    const songs = albumSection.querySelectorAll('.song-row');
                    if (songs.length > 0) {
                        const firstSong = songs[0];
                        const filePath = firstSong.getAttribute('data-song-file') || 
                                    getPathFromOnclick(firstSong.getAttribute('onclick'));
                        
                        if (filePath) {
                            if (typeof playSong === 'function') {
                                playSong(filePath, firstSong);
                            } else {
                                // Fallback if global playSong function isn't available
                                const event = new CustomEvent('play', { 
                                    detail: { path: filePath, element: firstSong } 
                                });
                                document.dispatchEvent(event);
                            }
                        }
                    }
                }
            });
            
            info.appendChild(playButton);
        }
    });
    
    // Helper function to extract file path from onclick attribute
    function getPathFromOnclick(onclickAttr) {
        if (!onclickAttr) return null;
        const match = onclickAttr.match(/'([^']+)'/);
        return match ? match[1] : null;
    }
    
    // Handle album covers double-click to play the album
    const albumCovers = document.querySelectorAll('.album-cover');
    albumCovers.forEach(cover => {
        cover.addEventListener('dblclick', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const albumSection = this.closest('.album-section');
            if (albumSection) {
                const songs = albumSection.querySelectorAll('.song-row');
                if (songs.length > 0) {
                    const firstSong = songs[0];
                    const filePath = firstSong.getAttribute('data-song-file') || 
                                getPathFromOnclick(firstSong.getAttribute('onclick'));
                    
                    if (filePath && typeof playSong === 'function') {
                        playSong(filePath, firstSong);
                    }
                }
            }
        });
    });
    
    // Add visual feedback for playing state
    function updatePlayingState() {
        // Check if there's a currently playing song
        const audioPlayer = window.audioPlayer;
        if (audioPlayer && !audioPlayer.paused) {
            const songId = document.getElementById('currentSongId')?.value;
            if (songId) {
                // Reset all playing states
                document.querySelectorAll('.song-row, .song-card').forEach(el => {
                    el.classList.remove('playing', 'active-song');
                });
                
                // Add playing state to current song
                document.querySelectorAll(`[data-song-id="${songId}"]`).forEach(el => {
                    if (el.classList.contains('song-row')) {
                        el.classList.add('playing');
                    } else if (el.classList.contains('song-card')) {
                        el.classList.add('active-song');
                    }
                });
            }
        }
    }
    
    // Listen for play events
    document.addEventListener('play', function() {
        setTimeout(updatePlayingState, 100);
    });
    
    // Initial check for playing state
    updatePlayingState();
});