/**
 * Player Index Module
 * Main entry point for the player functionality
 */

// start player when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.initializePlayer();

    window.initializePlayerUI();

    window.initializeMobileFeatures();
});

window.playPause = function() {
    const audioPlayer = window.audioPlayer;
    if (!audioPlayer) return;
    
    if (audioPlayer.paused) {
        audioPlayer.play()
            .then(() => window.updatePlayPauseButton(true))
            .catch(error => {
                console.error('Error playing audio:', error);
                window.displayError('Could not play audio');
            });
    } else {
        audioPlayer.pause();
        window.updatePlayPauseButton(false);
    }
};

window.audioPlayer = new Audio();