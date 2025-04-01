/**
 * matSFX Image Preloader
 * Ensures all images are fully loaded before displaying content
 */

// Create and initialize the preloader
function initImagePreloader() {
    // Create preloader elements
    const preloaderOverlay = document.createElement('div');
    preloaderOverlay.id = 'preloader-overlay';
    
    // Apply styles
    preloaderOverlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #0A1220;
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    transition: opacity 0.5s ease-out;
    `;

    // Create logo animation
    const logoContainer = document.createElement('div');
    logoContainer.style.cssText = `
    width: 100px;
    height: 100px;
    position: relative;
    margin-bottom: 20px;
    `;

    // Create animated logo with disk spinning effect
    const logo = document.createElement('div');
    logo.innerHTML = `
    <svg width="100" height="100" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="45" fill="#0A1220" stroke="#2D7FF9" stroke-width="2" />
        <circle cx="50" cy="50" r="20" fill="#2D7FF9" />
        <circle cx="50" cy="50" r="5" fill="#0A1220" />
        <!-- Spinning track lines -->
        <g class="spinning-tracks">
        <line x1="50" y1="25" x2="50" y2="10" stroke="#2D7FF9" stroke-width="2" />
        <line x1="75" y1="50" x2="90" y2="50" stroke="#2D7FF9" stroke-width="2" />
        <line x1="50" y1="75" x2="50" y2="90" stroke="#2D7FF9" stroke-width="2" />
        <line x1="25" y1="50" x2="10" y2="50" stroke="#2D7FF9" stroke-width="2" />
        <line x1="67" y1="33" x2="78" y2="22" stroke="#2D7FF9" stroke-width="2" />
        <line x1="67" y1="67" x2="78" y2="78" stroke="#2D7FF9" stroke-width="2" />
        <line x1="33" y1="67" x2="22" y2="78" stroke="#2D7FF9" stroke-width="2" />
        <line x1="33" y1="33" x2="22" y2="22" stroke="#2D7FF9" stroke-width="2" />
        </g>
    </svg>
    `;
    logo.style.cssText = `
    width: 100px;
    height: 100px;
    animation: pulse 2s infinite ease-in-out;
    `;

    // Create progress bar container
    const progressContainer = document.createElement('div');
    progressContainer.style.cssText = `
    width: 250px;
    height: 4px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
    `;

    // Create progress bar
    const progressBar = document.createElement('div');
    progressBar.id = 'preloader-progress';
    progressBar.style.cssText = `
    height: 100%;
    width: 0%;
    background-color: #2D7FF9;
    border-radius: 10px;
    transition: width 0.3s ease-out;
    `;
    progressContainer.appendChild(progressBar);

    // Create loading text
    const loadingText = document.createElement('div');
    loadingText.id = 'preloader-text';
    loadingText.textContent = 'Preparing your music experience...';
    loadingText.style.cssText = `
    color: rgba(255, 255, 255, 0.8);
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    text-align: center;
    `;

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .spinning-tracks {
        animation: spin 4s linear infinite;
        transform-origin: center;
    }
    body {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
    body.content-loaded {
        opacity: 1;
    }
    `;
    document.head.appendChild(style);

    // Assemble and add preloader to DOM
    logoContainer.appendChild(logo);
    preloaderOverlay.appendChild(logoContainer);
    preloaderOverlay.appendChild(progressContainer);
    preloaderOverlay.appendChild(loadingText);
    document.body.prepend(preloaderOverlay);

    // Hide scrollbar while loading
    document.body.style.overflow = 'hidden';

    return {
    overlay: preloaderOverlay,
    progressBar: progressBar,
    loadingText: loadingText
    };
}

// Track all image loading on the page
function trackImageLoading(preloaderElements) {
    // Get all images on the page
    const images = Array.from(document.images);
    const totalImages = images.length;
    
    // If no images, just hide preloader
    if (totalImages === 0) {
    completeLoading(preloaderElements);
    return;
    }

    let loadedCount = 0;
    const updateProgress = () => {
    loadedCount++;
    const percentage = Math.min(Math.round((loadedCount / totalImages) * 100), 100);
    
    // Update progress bar and text
    preloaderElements.progressBar.style.width = percentage + '%';
    preloaderElements.loadingText.textContent = `Loading images... ${percentage}%`;
    
    // When all images are loaded
    if (loadedCount >= totalImages) {
        completeLoading(preloaderElements);
    }
    };

    // Check each image
    images.forEach(img => {
    if (img.complete) {
        updateProgress();
    } else {
        img.addEventListener('load', updateProgress);
        img.addEventListener('error', updateProgress); // Count errors as "loaded"
    }
    });

    // Safety timeout - hide preloader after 8 seconds even if not all images loaded
    setTimeout(() => {
    if (loadedCount < totalImages) {
        preloaderElements.loadingText.textContent = 'Some resources still loading, but we\'re ready!';
        setTimeout(() => completeLoading(preloaderElements), 1000);
    }
    }, 8000);
}

// Complete loading and transition to content
function completeLoading(preloaderElements) {
    preloaderElements.loadingText.textContent = 'Ready to play!';
    preloaderElements.progressBar.style.width = '100%';
    
    // Add a small delay for visual feedback
    setTimeout(() => {
    // Fade out preloader
    preloaderElements.overlay.style.opacity = '0';
    
    // Show body content
    document.body.classList.add('content-loaded');
    
    // Re-enable scrolling
    document.body.style.overflow = '';
    
    // Remove preloader after transition
    setTimeout(() => {
        preloaderElements.overlay.remove();
    }, 500);
    }, 500);
}

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Create preloader
    const preloader = initImagePreloader();
    
    // Start tracking image loading
    trackImageLoading(preloader);
});