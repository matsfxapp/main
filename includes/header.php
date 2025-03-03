<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../user_handlers.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user = [];

if (isset($_SESSION['user_id'])) {
    $user = getUserData($_SESSION['user_id']);
    
    if (empty($user) || !isset($user['username'])) {
        error_log("Failed to get data for user ID: " . $_SESSION['user_id']);
        
        // Clear the session if user data cannot be retrieved
        // This prevents an endless loop of failed retrievals
        $_SESSION = array();
    }
}
?>
<link rel="stylesheet" href="/includes/css/header.css?v=<?php echo time(); ?>">
	<header class="global-header">
        <div class="header-container">
            <!-- Logo -->
            <a href="/" class="brand">
                <div class="logo-wrapper">
                    <img src="/app_logos/matsfx_logo.png" alt="Logo" class="logo-image">
                    <span class="brand-text">alpha_0.5.1</span>
                </div>
            </a>

			<!-- Search Bar -->
			<div class="search-container">
				<span class="search-icon">🔍</span>
				<input type="text" class="search-input" id="artistSearch">
				<div class="search-results" id="searchResults" style="display: none;"></div>
			</div>

            <!-- User Profile with Dropdown -->
            <div class="nav-user-profile">
				<img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '../defaults/default-profile.jpg'); ?>" 
                 alt="User Profile" class="nav-profile-picture" onclick="toggleDropdown()">
                
                <!-- Dropdown Menu -->
				<div class="profile-dropdown" id="profileDropdown">
					<?php if (isset($user['username'])): ?>
						<!-- Logged in user content -->
						<p class="dropdown-msg">
							Hello <?php echo htmlspecialchars($user['username']); ?>
						</p>
						<?php if (isset($user['is_admin']) && intval($user['is_admin']) === 1): ?>
							<a href="/admin" class="dropdown-item">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
								Admin Panel
							</a>
						<?php endif; ?>
						<a href="/settings" class="dropdown-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
							Settings
						</a>
						<a href="/upload" class="dropdown-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
							Upload
						</a>
						<a href="/logout" class="dropdown-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
							Logout
						</a>
					<?php else: ?>
						<!-- Not logged in content -->
						<p class="dropdown-msg">Not Logged In</p>
						<a href="/login" class="dropdown-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
							Login
						</a>
						<a href="/register" class="dropdown-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
							Register
						</a>
					<?php endif; ?>

					<div class="dropdown-footer">
						<div class="social-links">
							<a href="https://discord.matsfx.com" target="_blank" title="Discord">
								<img src="/includes/images/discord.png" alt="Discord">
							</a>
							<a href="https://youtube.matsfx.com" target="_blank" title="YouTube">
								<img src="/includes/images/youtube.png" alt="YouTube">
							</a>
							<a href="https://twitter.com/@matsfxmusic" target="_blank" title="Twitter">
								<img src="/includes/images/twitter.png" alt="Twitter">
							</a>
							<a href="https://tiktok.matsfx.com" target="_blank" title="TikTok">
								<img src="/includes/images/tiktok.png" target="_blank" alt="TikTok">
							</a>
						</div>
						<div class="footer-links">
							<div class="links">
								<a href="/what-are-these-badges">What are these Badges?</a>
								<a href="/legal/terms">Terms of Service</a>
								<a href="/legal/privacy">Privacy Policies</a>
								<a href="/legal/license">Open Source License</a>
								<a href="/legal/contact">Contact</a>
							</div>
						</div>
					</div>
				</div>
            </div>
        </div>
    </header>

	<script src="../js/search.js"></script>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');

            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('.nav-user-profile')) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }		

		function setPlaceholder() {
            const searchInput = document.getElementById('artistSearch');
            const placeholderContent = getComputedStyle(document.documentElement).getPropertyValue('--placeholder-content').trim();
            const placeholderContentMobile = getComputedStyle(document.documentElement).getPropertyValue('--placeholder-content-mobile').trim();
            
            if (window.innerWidth <= 768) {
                searchInput.placeholder = placeholderContentMobile;
            } else {
                searchInput.placeholder = placeholderContent;
            }
        }

		function refreshDropdown() {
			// Use AJAX to fetch the latest header content
			fetch('/get_header_content.php')
				.then(response => response.text())
				.then(data => {
					document.getElementById('profileDropdown').innerHTML = data;
				});
		}

        // Call setPlaceholder on load and on resize
        window.addEventListener('load', setPlaceholder);
        window.addEventListener('resize', setPlaceholder);
    </script>
