/**
 * prevent unwanted playback in song cards
 */
function fixSongCardNavigation() {
    // Select all artist links within song cards
    document.querySelectorAll('.song-card .artist-link, .song-row .artist-link').forEach(link => {
    // Stop propagation on artist links
    link.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    });
    
    // Set up a mutation observer to handle dynamically added song cards
    const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
        if (mutation.addedNodes.length) {
        mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1) { // Element node
            // Check new nodes for artist links
            const links = node.querySelectorAll('.artist-link');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                e.stopPropagation();
                });
            });
            }
        });
        }
    });
    });
    
    // Start observing
    observer.observe(document.body, {
    childList: true,
    subtree: true
    });
}

// Call this function when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    fixSongCardNavigation();
});