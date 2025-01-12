<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../user_handlers.php';

$user = [];
if (isset($_SESSION['user_id'])) {
	$user = getUserData($_SESSION['user_id']);
}
?>
<link rel="stylesheet" href="/includes/css/header.css">    
<header class="global-header">
        <div class="header-container">
            <!-- Logo -->
            <a href="/" class="brand">
                <div class="logo-wrapper">
                    <img src="/app_logos/matsfx_logo.png" alt="Logo" class="logo-image">
                    <span class="brand-text">alpha_0.4.23</span>
                </div>
            </a>

            <!-- User Profile with Dropdown -->
            <div class="nav-user-profile">
				<img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/defaults/default-profile.jpg'); ?>" 
                 alt="User Profile" class="nav-profile-picture" onclick="toggleDropdown()">
                
                <!-- Dropdown Menu -->
                <div class="profile-dropdown" id="profileDropdown">
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
                    <div class="dropdown-footer">
                        <div class="social-links">
                            <a href="https://discord.gg/YjvgAGU2ys" target="_blank" title="Discord">
                                <img src="/includes/images/discord.png" alt="Discord">
                            </a>
							 <a href="https://youtube.com/@matsfxmusic" target="_blank" title="YouTube">
                                <img src="/includes/images/youtube.png" alt="YouTube">
                            </a>
                            <a href="https://twitter.com/@matsfxmusic" target="_blank" title="Twitter">
                                <img src="/includes/images/twitter.png" alt="Twitter">
                            </a>
                            <a href="https://tiktok.com/@matsfxmusic" target="_blank" title="TikTok">
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

        // Call setPlaceholder on load and on resize
        window.addEventListener('load', setPlaceholder);
        window.addEventListener('resize', setPlaceholder);
    </script>