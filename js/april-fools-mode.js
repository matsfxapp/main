/**
 * April Fools Mode - Audio Distortion
 * April Fools mode that distorts all audio playback.
 * Only activates on April 1st.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if today is April 1st
    const today = new Date();
    const isAprilFools = today.getMonth() === 3 && today.getDate() === 1;
    
    // For testing purposes, you can force enable with this cookie
    const forcedEnabled = document.cookie.includes('aprilFoolsMode=enabled');
    
    if (isAprilFools || forcedEnabled) {
        initializeAprilFoolsMode();
    }
});

function initializeAprilFoolsMode() {
    // Create AudioContext and nodes for distortion effects
    let audioContext;
    let source;
    let distortion;
    let compressor;
    let pitchShifter;
    let biquadFilter;
    let gainNode;
    
    // Add banner announcement for April Fools
    addAprilFoolsBanner();
    
    // Modify the original audio player and apply effects
    const originalAudioElement = window.audioPlayer;
    
    // Override the playSong function to add distortion
    const originalPlaySong = window.playSong;
    window.playSong = function(filePath, element) {
        // Setup audio context before playing
        setupAudioProcessing();
        
        // Call the original playSong function
        originalPlaySong(filePath, element);
    };
    
    // Setup audio context and processing nodes
    function setupAudioProcessing() {
        // Clean up previous audio processing if it exists
        cleanupAudioProcessing();
        
        // Create new audio context and connect nodes
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Create media element source
        source = audioContext.createMediaElementSource(originalAudioElement);
        
        // Create distortion
        distortion = audioContext.createWaveShaper();
        distortion.curve = createDistortionCurve(400);
        distortion.oversample = '4x';
        
        // Create compressor
        compressor = audioContext.createDynamicsCompressor();
        compressor.threshold.value = -50;
        compressor.knee.value = 40;
        compressor.ratio.value = 12;
        compressor.attack.value = 0;
        compressor.release.value = 0.25;
        
        // Create biquad filter for lo-fi effect
        biquadFilter = audioContext.createBiquadFilter();
        biquadFilter.type = 'lowpass';
        biquadFilter.frequency.value = 1000;
        biquadFilter.Q.value = 0.7;
        
        // Create gain node to control volume
        gainNode = audioContext.createGain();
        gainNode.gain.value = 0.7; // Reduce volume to prevent clipping
        
        // Connect the audio graph
        source.connect(distortion);
        distortion.connect(biquadFilter);
        biquadFilter.connect(compressor);
        compressor.connect(gainNode);
        gainNode.connect(audioContext.destination);
    }
    
    // Function to create the distortion curve
    function createDistortionCurve(amount) {
        const k = typeof amount === 'number' ? amount : 50;
        const n_samples = 44100;
        const curve = new Float32Array(n_samples);
        const deg = Math.PI / 180;
        
        for (let i = 0; i < n_samples; ++i) {
            const x = i * 2 / n_samples - 1;
            curve[i] = (3 + k) * x * 20 * deg / (Math.PI + k * Math.abs(x));
        }
        
        return curve;
    }
    
    // Clean up audio processing
    function cleanupAudioProcessing() {
        if (source) {
            source.disconnect();
        }
        
        if (audioContext && audioContext.state !== 'closed') {
            audioContext.close();
        }
    }
    
    // Add a banner to inform users about April Fools
    function addAprilFoolsBanner() {
        const banner = document.createElement('div');
        banner.className = 'april-fools-banner';
        banner.innerHTML = `
            <div class="april-fools-content">
                <div class="april-fools-icon">🤪</div>
                <div class="april-fools-message">
                    <h3>April Fools!</h3>
                    <p>Something sounds off today... must be the spring air! 🎵</p>
                </div>
                <button class="april-fools-close">&times;</button>
            </div>
        `;
        
        // Add styles for the banner
        const style = document.createElement('style');
        style.textContent = `
            .april-fools-banner {
                position: fixed;
                top: 80px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9999;
                width: 90%;
                max-width: 600px;
                background: linear-gradient(135deg, #FF6B6B, #FF9E80);
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                animation: bounce-in 0.5s cubic-bezier(0.215, 0.610, 0.355, 1.000);
                overflow: hidden;
                border: 2px solid #FFE66D;
            }
            
            .april-fools-content {
                display: flex;
                align-items: center;
                padding: 16px;
            }
            
            .april-fools-icon {
                font-size: 2.5rem;
                margin-right: 16px;
                animation: spin 3s infinite linear;
            }
            
            .april-fools-message {
                flex: 1;
            }
            
            .april-fools-message h3 {
                margin: 0 0 5px 0;
                font-size: 1.2rem;
                color: #fff;
            }
            
            .april-fools-message p {
                margin: 0;
                color: rgba(255, 255, 255, 0.9);
                font-size: 0.95rem;
            }
            
            .april-fools-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 5px;
                opacity: 0.8;
                transition: opacity 0.2s;
            }
            
            .april-fools-close:hover {
                opacity: 1;
            }
            
            @keyframes bounce-in {
                0% { transform: translateX(-50%) scale(0.8); opacity: 0; }
                70% { transform: translateX(-50%) scale(1.05); }
                100% { transform: translateX(-50%) scale(1); opacity: 1; }
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                25% { transform: rotate(20deg); }
                75% { transform: rotate(-15deg); }
                100% { transform: rotate(0deg); }
            }
            
            @media (max-width: 768px) {
                .april-fools-banner {
                    top: 70px;
                    width: 95%;
                }
                
                .april-fools-icon {
                    font-size: 2rem;
                }
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(banner);
        
        // Add event listener to close button
        banner.querySelector('.april-fools-close').addEventListener('click', function() {
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.remove();
            }, 300);
        });
        
        // Auto-hide banner after 15 seconds
        setTimeout(() => {
            if (document.body.contains(banner)) {
                banner.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(banner)) {
                        banner.remove();
                    }
                }, 300);
            }
        }, 15000);
    }
    
    // Toggle function for testing
    window.toggleAprilFoolsMode = function() {
        if (document.cookie.includes('aprilFoolsMode=enabled')) {
            document.cookie = 'aprilFoolsMode=disabled; path=/; max-age=86400';
            alert('April Fools Mode disabled. Refresh the page for changes to take effect.');
        } else {
            document.cookie = 'aprilFoolsMode=enabled; path=/; max-age=86400';
            alert('April Fools Mode enabled. Refresh the page for changes to take effect.');
        }
    };
    
    // Add cleanup
    window.addEventListener('beforeunload', cleanupAudioProcessing);
}