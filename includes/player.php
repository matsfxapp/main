<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="css/global/player.css">
<link rel="stylesheet" href="css/global/mobileContextMenu.css">
<link rel="stylesheet" href="css/global/queue.css">

<div id="errorContainer"></div>

<div class="player">
    <div class="player-container">
        <div class="song-info">
            <img id="player-album-art" 
                 src="defaults/default-cover.jpg" 
                 alt="Album Art" 
                 class="album-art" 
                 onerror="this.src='defaults/default-cover.jpg'">
            <div class="track-info">
                <h3 id="songTitle" class="track-name"></h3>
                <div id="artistName" class="artist-name"></div>
            </div>
        </div>
        
        <div class="player-controls">
            <div class="control-buttons">
                <button onclick="previousTrack()" aria-label="Previous Track">
                    <i class="fas fa-step-backward"></i>
                </button>
                <button onclick="playPause()" id="playPauseBtn" aria-label="Play/Pause">
                    <i class="fas fa-play"></i>
                </button>
                <button onclick="nextTrack()" aria-label="Next Track">
                    <i class="fas fa-step-forward"></i>
                </button>
                <button onclick="toggleLoop()" id="loopBtn" aria-label="Loop Track">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         viewBox="0 0 24 24" 
                         width="60" 
                         height="60" 
                         fill="none" 
                         stroke="currentColor" 
                         stroke-width="2" 
                         stroke-linecap="round" 
                         stroke-linejoin="round">
                        <path d="M3 12c0-3.866 3.134-7 7-7h6.5"/>
                        <polyline points="14 2 17 5 14 8"/>
                        <path d="M21 12c0 3.866-3.134 7-7 7H7.5"/>
                        <polyline points="10 22 7 19 10 16"/>
                    </svg>
                </button>
                
                <!-- Volume Control -->
                <div class="volume-control-container">
                    <button id="volumeIcon" aria-label="Volume">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <div id="volumeControlPopup" class="volume-popup">
                        <input type="range" id="volumeSlider" min="0" max="100" value="70" class="volume-slider">
                    </div>
                </div>
                
                <!-- Queue Button -->
                <button id="queueToggle" aria-label="Queue">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            <div class="progress-container">
                <span id="currentTime">0:00</span>
                <input type="range" 
                       id="progress" 
                       value="0" 
                       max="100" 
                       class="slider" 
                       aria-label="Song Progress">
                <span id="duration">0:00</span>
            </div>
        </div>
    </div>
</div>

<!-- Queue View Panel -->
<div id="queueView" class="queue-panel">
    <div class="queue-header">
        <h3>Playing Queue</h3>
        <button class="queue-close">✕</button>
    </div>
    <ul id="queueList" class="queue-list">
        <!-- Queue items will be added here dynamically -->
        <li class="empty-queue">Your queue is empty</li>
    </ul>
</div>

<!-- Context menu for adding to queue -->
<div id="songContextMenu">
</div>

<!-- Load player scripts -->
<script src="js/player/core.js"></script>
<script src="js/player/queue.js"></script>
<script src="js/player/ui.js"></script>
<script src="js/player/mobile.js"></script>
<script src="js/player/navigation.js"></script>
<script src="js/player/helper.js"></script>
<script src="js/player/index.js"></script>