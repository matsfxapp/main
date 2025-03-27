function createLoadingAnimation() {
    // Create the overlay
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #0A1220;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease-out;
    `;

    // Create the loading container
    const loadingContainer = document.createElement('div');
    loadingContainer.style.cssText = `
        position: relative;
        width: 120px;
        height: 120px;
        display: flex;
        justify-content: center;
        align-items: center;
    `;

    // Create the spinner
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.style.cssText = `
        position: absolute;
        width: 100%;
        height: 100%;
        border: 4px solid transparent;
        border-top: 4px solid #2D7FF9;
        border-radius: 50%;
        animation: spin 1.5s linear infinite;
    `;

    // Create the logo container
    const logoContainer = document.createElement('div');
    logoContainer.style.cssText = `
        width: 80px;
        height: 80px;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #0A1220;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        animation: pulse 2s infinite;
    `;

    // Create the logo image
    const logo = document.createElement('img');
    logo.src = '/app_logos/matsfx_logo.png';
    logo.alt = 'matSFX Logo';
    logo.style.cssText = `
        width: 60px;
        height: 60px;
        object-fit: contain;
    `;

    // Append elements
    logoContainer.appendChild(logo);
    loadingContainer.appendChild(spinner);
    loadingContainer.appendChild(logoContainer);
    overlay.appendChild(loadingContainer);

    // Create CSS animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        body {
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s 0.5s, opacity 0.5s linear;
        }
        body.loaded {
            visibility: visible;
            opacity: 1;
            transition: opacity 0.5s linear;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    `;

    // Add to document
    document.head.appendChild(style);
    document.body.appendChild(overlay);

    return overlay;
}

// Hide and remove the loading animation
function hideLoadingAnimation(overlay) {
    overlay.style.opacity = '0';
    document.body.classList.add('loaded');
    
    setTimeout(() => {
        if (overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    }, 500);
}

// Create and show loading animation when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const loadingOverlay = createLoadingAnimation();
    
    // Hide loading animation when all resources are loaded
    window.addEventListener('load', function() {
        hideLoadingAnimation(loadingOverlay);
    });
    
    // Fallback to hide loading after 10 seconds in case something doesnt load
    setTimeout(() => {
        hideLoadingAnimation(loadingOverlay);
    }, 10000);
});