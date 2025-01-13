<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet"href="/../css/player-style.css">

<div id="errorContainer"></div>
	
	<div class="player">
	    <div class="player-container">
	        <div class="song-info">
	            <img id="player-album-art" 
	                 src="" 
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
	        
			<!--
	        <div class="volume-control">
	            <i class="fas fa-volume-up volume-icon" id="volumeIcon"></i>
	            <input type="range" 
	                   id="volume" 
	                   min="0" 
	                   max="1" 
	                   step="0.01" 
	                   value="1" 
	                   class="volume-slider" 
	                   aria-label="Volume Control">
	        </div> -->
	    </div>
	</div>