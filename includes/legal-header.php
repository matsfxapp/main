<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../user_handlers.php';

$user = [];
if (isset($_SESSION['user_id'])) {
	$user = getUserData($_SESSION['user_id']);
}
?>
<link rel="stylesheet" href="/includes/css/header.css"> 
<style>
	* {
		font-family: 'Inter', system-ui, -apple-system, sans-serif;
		margin: 0;
		padding: 0;
		box-sizing: border-box;
	}

	:root {
		--header-bg: rgba(17, 24, 39, 0.95);
		--header-border: rgba(255, 255, 255, 0.08);
		--primary-text: rgba(255, 255, 255, 0.95);
		--secondary-text: rgba(255, 255, 255, 0.7);
		--hover-bg: rgba(255, 255, 255, 0.12);
		--active-bg: rgba(255, 255, 255, 0.15);
		--dropdown-bg: rgba(25, 28, 35, 0.98);
	}

	.global-header {
		background-color: var(--header-bg);
		backdrop-filter: blur(12px) saturate(180%);
		padding: 0.875rem;
		width: 100%;
		position: fixed;
		top: 0;
		left: 0;
		z-index: 1000;
		border-bottom: 1px solid var(--header-border);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.header-container {
		display: flex;
		align-items: center;
		justify-content: space-between;
		max-width: 1400px;
		margin: 0 auto;
		gap: 2rem;
		padding: 0 1.5rem;
	}

	/* Logo Styles */
	.brand {
		text-decoration: none;
		color: var(--primary-text);
		transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	}

	.brand:hover {
		transform: translateY(-1px);
	}

	.logo-wrapper {
		display: flex;
		align-items: center;
		gap: 0.875rem;
	}

	.logo-image {
		width: 38px;
		height: 38px;
		border-radius: 5px;
		object-fit: contain;
		filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
	}

	.brand-text {
		font-size: 0.9375rem;
		font-weight: 600;
		background: linear-gradient(120deg, #fff, rgba(255, 255, 255, 0.8));
		background-clip: text;
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}

	/* Navigation Styles */
	.main-nav {
		display: flex;
		gap: 1.5rem;
		align-items: center;
	}

	.nav-link {
		color: var(--secondary-text);
		text-decoration: none;
		font-weight: 500;
		padding: 0.625rem 1rem;
		border-radius: 10px;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		font-size: 0.9375rem;
		position: relative;
		overflow: hidden;
	}

	.nav-link::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: var(--hover-bg);
		transform: translateY(100%);
		transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		z-index: -1;
		border-radius: 10px;
	}

	.nav-link:hover {
		color: var(--primary-text);
	}

	.nav-link:hover::before {
		transform: translateY(0);
	}

	/* User Profile Styles */
	.nav-user-profile {
		position: relative;
	}

	.nav-profile-picture {
		width: 42px;
		height: 42px;
		border-radius: 50%;
		object-fit: cover;
		cursor: pointer;
		border: 2px solid var(--header-border);
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
	}

	.nav-profile-picture:hover {
		border-color: rgba(255, 255, 255, 0.3);
		transform: scale(1.05);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
	}

	/* Dropdown Menu Styles */
	.profile-dropdown {
		position: absolute;
		top: calc(100% + 14px);
		right: 0;
		background-color: var(--dropdown-bg);
		backdrop-filter: blur(20px) saturate(180%);
		border-radius: 0px 0px 16px 16px;
		width: 280px;
		opacity: 0;
		visibility: hidden;
		transform: translateY(-8px) scale(0.98);
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
		border: 1px solid var(--header-border);
	}

	.profile-dropdown.show {
		opacity: 1;
		visibility: visible;
		transform: translateY(0) scale(1);
	}

	.dropdown-item {
		display: flex;
		align-items: center;
		gap: 14px;
		padding: 14px 18px;
		color: var(--secondary-text);
		text-decoration: none;
		font-size: 0.9375rem;
		transition: all 0.2s ease;
		position: relative;
		overflow: hidden;
	}

	.dropdown-item::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: var(--hover-bg);
		transform: translateX(-100%);
		transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		z-index: 0;
	}

	.dropdown-item:hover::before {
		transform: translateX(0);
	}

	.dropdown-item:hover {
		color: var(--primary-text);
	}

	.dropdown-item svg {
		color: rgba(255, 255, 255, 0.5);
		transition: all 0.3s ease;
		position: relative;
		z-index: 1;
	}

	.dropdown-item:hover svg {
		color: var(--primary-text);
		transform: scale(1.1);
	}

	/* Dropdown Footer Styles */
	.dropdown-footer {
		margin-top: 10px;
		border-top: 1px solid var(--header-border);
	}

	.social-links {
		display: flex;
		justify-content: center;
		gap: 28px;
		padding: 20px 0;
	}

	.social-links a {
		position: relative;
		transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	}

	.social-links a img {
		width: 22px;
		height: 22px;
		opacity: 0.6;
		transition: all 0.3s ease;
		filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
	}

	.social-links a:hover {
		transform: translateY(-2px);
	}

	.social-links a:hover img {
		opacity: 1;
		filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
	}

	.footer-links {
		padding: 18px;
		background-color: rgba(37, 42, 52, 0.5);
		border-bottom-left-radius: 16px;
		border-bottom-right-radius: 16px;
		backdrop-filter: blur(8px);
	}

	.footer-links p {
		color: var(--secondary-text);
		font-size: 0.9375rem;
		margin-bottom: 14px;
		font-weight: 500;
	}

	.footer-links .links {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.footer-links .links a {
		color: rgba(255, 255, 255, 0.5);
		text-decoration: none;
		font-size: 0.875rem;
		transition: all 0.3s ease;
		display: inline-block;
	}

	.footer-links .links a:hover {
		color: var(--primary-text);
		transform: translateX(4px);
	}

	/* Responsive Design */
	@media (max-width: 768px) {
		.header-container {
			padding: 0 1rem;
			gap: 1rem;
			height: 3rem;
		}

		.search-container {
			max-width: none;
			flex-grow: 1;
		}

		.brand-text {
			display: none;
		}

		.nav-link {
			padding: 0.25rem 0.5rem;
		}

		.profile-dropdown {
			width: 260px;
		}

		.search-icon {
			left: 1.5rem;
		}

		.logo-image {
			width: 28px;
			height: 28px;
		}

		.search-input {
			padding: 0.5rem 1rem 0.5rem 2.5rem;
			height: 2.25rem;
		}

		.nav-profile-picture {
			width: 32px;
			height: 32px;
			transform: translateY(8px);
		}

		.global-header {
			padding: 0.5rem;
		}
	}

	@media (prefers-color-scheme: dark) {
		.global-header {
			background-color: rgba(10, 12, 16, 0.95);
		}

		.profile-dropdown {
			background-color: rgba(20, 22, 28, 0.98);
		}
	}

	html {
		scroll-behavior: smooth;
	}
</style>  
	
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
				<img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'defaults/default-profile.jpg'); ?>" 
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
    </script>