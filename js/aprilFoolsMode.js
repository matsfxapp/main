/**
 * April Fools Mode - Audio Distortion
 * distorts all audio playback.
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
        
        // Create extreme distortion
        distortion = audioContext.createWaveShaper();
        distortion.curve = createExtremeDistortionCurve();
        distortion.oversample = '4x';
        
        // Create super aggressive compressor
        compressor = audioContext.createDynamicsCompressor();
        compressor.threshold.value = -70;
        compressor.knee.value = 0;
        compressor.ratio.value = 20;
        compressor.attack.value = 0;
        compressor.release.value = 0.1;
        
        // Create biquad filter for destroyed sound effect
        biquadFilter = audioContext.createBiquadFilter();
        biquadFilter.type = 'bandpass';
        biquadFilter.frequency.value = 700; // Focus on midrange frequencies
        biquadFilter.Q.value = 3; // Narrow bandwidth for more destruction
        
        // Add a second filter for even more destruction
        const highpassFilter = audioContext.createBiquadFilter();
        highpassFilter.type = 'highpass';
        highpassFilter.frequency.value = 2000; // Cut low frequencies
        highpassFilter.Q.value = 1;
        
        // Add a bitcrusher effect - implemented as a ScriptProcessor
        const bufferSize = 4096;
        const bitcrusher = audioContext.createScriptProcessor(bufferSize, 1, 1);
        let bits = 3; // Extremely low bit depth for maximum destruction
        bitcrusher.onaudioprocess = function(e) {
            const inputBuffer = e.inputBuffer.getChannelData(0);
            const outputBuffer = e.outputBuffer.getChannelData(0);
            
            for (let i = 0; i < bufferSize; i++) {
                // Reduce bit depth
                const step = Math.pow(2, bits - 1);
                outputBuffer[i] = Math.round(inputBuffer[i] * step) / step;
                
                // Random glitches
                if (Math.random() < 0.01) {
                    outputBuffer[i] = Math.random() * 2 - 1;
                }
                
                // Occasional silence
                if (Math.random() < 0.005) {
                    outputBuffer[i] = 0;
                }
            }
        };
        
        // Create gain node to control volume
        gainNode = audioContext.createGain();
        gainNode.gain.value = 0.5; // Reduce volume to prevent extreme clipping
        
        // Connect the audio graph for maximum destruction
        source.connect(distortion);
        distortion.connect(biquadFilter);
        biquadFilter.connect(highpassFilter);
        highpassFilter.connect(compressor);
        compressor.connect(bitcrusher);
        bitcrusher.connect(gainNode);
        gainNode.connect(audioContext.destination);
    }
    
    // Function to create an extreme distortion curve
    function createExtremeDistortionCurve() {
        const n_samples = 44100;
        const curve = new Float32Array(n_samples);
        
        for (let i = 0; i < n_samples; ++i) {
            const x = i * 2 / n_samples - 1;
            
            // Extreme clipping and folding distortion
            if (x < 0) {
                // Negative values get extreme distortion
                curve[i] = Math.tanh(x * 10) * Math.sin(x * 15);
            } else {
                // Positive values get different extreme distortion
                curve[i] = Math.sin(x * Math.PI * 3) * Math.sign(Math.sin(x * 20));
            }
            
            // Add some digital aliasing artifacts
            if (Math.abs(x) > 0.7) {
                curve[i] *= Math.tan(x);
            }
            
            // Ensure the output is within [-1, 1] to prevent complete destruction
            curve[i] = Math.max(-1, Math.min(1, curve[i]));
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
        // Occasionally add corrupted glitches to page elements to enhance the effect
        addRandomGlitches();
        
        const banner = document.createElement('div');
        banner.className = 'april-fools-banner';
        banner.innerHTML = `
            <div class="april-fools-content">
                <div class="april-fools-icon">🤪</div>
                <div class="april-fools-message">
                    <h3>April Fools!</h3>
                    <p>WARNING: Audio malfunction detected! Our servers seem to be... broken today! 🔥🔊</p>
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
    
    // Add random glitches to page elements
    function addRandomGlitches() {
        // Add glitchy UI effects
        const style = document.createElement('style');
        style.textContent = `
            @keyframes glitch {
                0% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); text-shadow: -1px 0 red, 1px 0 blue; }
                80% { transform: translate(2px, -2px); }
                100% { transform: translate(0); }
            }
            
            @keyframes static {
                0% { background-position: 0% 0%; }
                100% { background-position: 100% 100%; }
            }
            
            .glitch-effect {
                animation: glitch 0.3s infinite;
                position: relative;
            }
            
            .glitch-effect::before, .glitch-effect::after {
                content: attr(data-text);
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            
            .glitch-effect::before {
                left: 2px;
                text-shadow: -1px 0 red;
                clip: rect(44px, 450px, 56px, 0);
                animation: glitch 0.5s infinite linear alternate-reverse;
            }
            
            .glitch-effect::after {
                left: -2px;
                text-shadow: 1px 0 blue;
                clip: rect(24px, 450px, 36px, 0);
                animation: glitch 0.52s infinite linear alternate-reverse;
            }
            
            .static-overlay {
                position: relative;
            }
            
            .static-overlay::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                opacity: 0.03;
                pointer-events: none;
                background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
                animation: static 0.2s infinite linear;
            }
        `;
        document.head.appendChild(style);
        
        // Apply glitch effects to random elements
        setTimeout(() => {
            // Add glitch to some song titles
            const songTitles = document.querySelectorAll('.song-title');
            songTitles.forEach(title => {
                if (Math.random() < 0.1) { // 10% chance
                    title.classList.add('glitch-effect');
                    title.setAttribute('data-text', title.textContent);
                }
            });
            
            // Add static overlay to some album covers
            const coverArts = document.querySelectorAll('.cover-art, .album-cover');
            coverArts.forEach(cover => {
                if (Math.random() < 0.15) { // 15% chance
                    cover.classList.add('static-overlay');
                }
            });
            
            // Occasionally add a screen-wide glitch effect
            setInterval(() => {
                if (Math.random() < 0.05) { // 5% chance every few seconds
                    const glitchOverlay = document.createElement('div');
                    glitchOverlay.className = 'global-glitch-overlay';
                    glitchOverlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(255,0,0,0.02);
                        z-index: 9998;
                        pointer-events: none;
                        mix-blend-mode: exclusion;
                    `;
                    document.body.appendChild(glitchOverlay);
                    
                    // Remove after a short time
                    setTimeout(() => {
                        glitchOverlay.remove();
                    }, 50 + Math.random() * 200);
                }
            }, 5000);
        }, 3000);
    }
    
    // Add cleanup
    window.addEventListener('beforeunload', cleanupAudioProcessing);
}
