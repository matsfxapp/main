<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matSFX Badges</title>
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --card-bg: #111827;
            --card-hover: #1F2937;
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
			--background-secondary: #111827;
			--background: #fff;
			--accent: #2D7FF9;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--dark-bg);
            color: var(--light-text);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .section-description {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 40px;
            color: var(--gray-text);
        }

        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .badge-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .badge-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-md);
            background-color: var(--card-hover);
        }

        .badge-card img {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 15px;
            object-fit: contain;
        }

        .badge-card h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: var(--light-text);
        }

        .badge-card p {
            color: var(--gray-text);
            font-size: 0.9rem;
        }
		
		.socials {
			position: absolute;
			bottom: 1rem;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 1.5rem;
			padding: 1rem;
			background-color: var(--background-secondary);
			border-radius: 15px;
			border: 1.5px solid #3f3f4f;
			animation: appear 1.15s cubic-bezier(0.55, 0, 0.25, 0.95) forwards;
		}

		.social-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			position: relative;
		}

		.bubble {
			background-color: var(--accent);
			color: var(--background);
			padding: 0.5rem 1rem;
			border-radius: 15px;
			font-size: 0.8rem;
			position: absolute;
			bottom: 100%;
			left: 50%;
			transform: translateX(-50%);
			white-space: nowrap;
			opacity: 0;
			transition: opacity 0.3s ease, transform 0.3s ease;
			pointer-events: none;
		}

		.bubble::after {
			content: '';
			position: absolute;
			top: 100%;
			left: 50%;
			border: 8px solid transparent;
			border-top-color: var(--accent);
			transform: translateX(-50%);
		}

		.social-item:hover .bubble {
			opacity: 1;
			transform: translateX(-50%) translateY(-10px);
		}

		.socials a {
			color: var(--text);
			text-decoration: none;
			transition: color 0.3s ease;
		}

		.socials a:hover {
			color: var(--accent);
		}

		.socials img, .socials svg {
			width: 40px;
			height: 40px;
			transition: all 0.5s ease;
		}

		.socials img:hover {
			filter: brightness(0) saturate(100%) invert(64%) sepia(14%) saturate(5757%) hue-rotate(92deg) brightness(91%) contrast(77%);
		}

		.socials svg path {
			fill: var(--text);
			transition: fill 0.3s ease;
		}

		.socials svg:hover path {
			fill: var(--accent);
		}

		@keyframes appear {
			from { bottom: -10rem; }
			to { bottom: 1rem; }
		}

		@media screen and (max-width: 600px) {
			h1 { font-size: 2.5rem; }
			.social-item { margin-bottom: 0rem; }
		}

        @media (max-width: 768px) {
            .badges-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="section-title">matSFX Badges</h1>
        <p class="section-description">
            Earn these badges by using the matSFX app and engaging with our community. 
            Each badge represents a unique achievement or contribution.
        </p>
        
        <div class="badges-grid">
            <div class="badge-card">
                <img src="app-images/admin-badge.png" alt="Admin Badge">
                <h3>Admin Badge</h3>
                <p>Reserved for admins and high-level team members with special privileges.</p>
            </div>
            
            <div class="badge-card">
                <img src="app-images/developer-badge.png" alt="Developer Badge">
                <h3>Developer Badge</h3>
                <p>Awarded to official developers and contributors to the matSFX project.</p>
            </div>
            
            <div class="badge-card">
                <img src="app-images/donator-badge.png" alt="Donator Badge">
                <h3>Donator Badge</h3>
                <p>Celebrates our loyal supporters who contribute financially, no matter the amount.</p>
            </div>
            
            <div class="badge-card">
                <img src="app-images/helper-badge.png" alt="Helper Badge">
                <h3>Helper Badge</h3>
                <p>Recognizes community members who help identify or fix bugs in the app.</p>
            </div>
            
            <div class="badge-card">
                <img src="app-images/verified-badge.png" alt="Verified Badge">
                <h3>Verified Badge</h3>
                <p>Earned by uploading two original songs, verified within two weeks of submission.</p>
            </div>
        </div>
    </div>
	
	<!-- <div class="socials">
        <div class="social-item">
            <div class="bubble">Join our Discord community Server</div>
            <a href="https://discord.gg/YjvgAGU2ys" title="Discord">
                <svg viewBox="0 -28.5 256 256" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" fill="#ffffff" stroke="#ffffff">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <g>
                            <path d="M216.856339,16.5966031 C200.285002,8.84328665 182.566144,3.2084988 164.041564,0 C161.766523,4.11318106 159.108624,9.64549908 157.276099,14.0464379 C137.583995,11.0849896 118.072967,11.0849896 98.7430163,14.0464379 C96.9108417,9.64549908 94.1925838,4.11318106 91.8971895,0 C73.3526068,3.2084988 55.6133949,8.86399117 39.0420583,16.6376612 C5.61752293,67.146514 -3.4433191,116.400813 1.08711069,164.955721 C23.2560196,181.510915 44.7403634,191.567697 65.8621325,198.148576 C71.0772151,190.971126 75.7283628,183.341335 79.7352139,175.300261 C72.104019,172.400575 64.7949724,168.822202 57.8887866,164.667963 C59.7209612,163.310589 61.5131304,161.891452 63.2445898,160.431257 C105.36741,180.133187 151.134928,180.133187 192.754523,160.431257 C194.506336,161.891452 196.298154,163.310589 198.110326,164.667963 C191.183787,168.842556 183.854737,172.420929 176.223542,175.320965 C180.230393,183.341335 184.861538,190.991831 190.096624,198.16893 C211.238746,191.588051 232.743023,181.531619 254.911949,164.955721 C260.227747,108.668201 245.831087,59.8662432 216.856339,16.5966031 Z M85.4738752,135.09489 C72.8290281,135.09489 62.4592217,123.290155 62.4592217,108.914901 C62.4592217,94.5396472 72.607595,82.7145587 85.4738752,82.7145587 C98.3405064,82.7145587 108.709962,94.5189427 108.488529,108.914901 C108.508531,123.290155 98.3405064,135.09489 85.4738752,135.09489 Z M170.525237,135.09489 C157.88039,135.09489 147.510584,123.290155 147.510584,108.914901 C147.510584,94.5396472 157.658606,82.7145587 170.525237,82.7145587 C183.391518,82.7145587 193.761324,94.5189427 193.539891,108.914901 C193.539891,123.290155 183.391518,135.09489 170.525237,135.09489 Z" fill="#ffffff" fill-rule="nonzero">
                            </path>
                        </g>
                    </g>
                </svg>
            </a>
        </div>
		<div class="social-item">
            <div class="bubble">View the matSFX GitHub Repository</div>
            <a href="https://github.com/matsfx-music" title="GitHub">
                <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 1024 1024" fill="#fff">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8C0 11.54 2.29 14.53 5.47 15.59C5.87 15.66 6.02 15.42 6.02 15.21C6.02 15.02 6.01 14.39 6.01 13.72C4 14.09 3.48 13.23 3.32 12.78C3.23 12.55 2.84 11.84 2.5 11.65C2.22 11.5 1.82 11.13 2.49 11.12C3.12 11.11 3.57 11.7 3.72 11.94C4.44 13.15 5.59 12.81 6.05 12.6C6.12 12.08 6.33 11.73 6.56 11.53C4.78 11.33 2.92 10.64 2.92 7.58C2.92 6.71 3.23 5.99 3.74 5.43C3.66 5.23 3.38 4.41 3.82 3.31C3.82 3.31 4.49 3.1 6.02 4.13C6.66 3.95 7.34 3.86 8.02 3.86C8.7 3.86 9.38 3.95 10.02 4.13C11.55 3.09 12.22 3.31 12.22 3.31C12.66 4.41 12.38 5.23 12.3 5.43C12.81 5.99 13.12 6.7 13.12 7.58C13.12 10.65 11.25 11.33 9.47 11.53C9.76 11.78 10.01 12.26 10.01 13.01C10.01 14.08 10 14.94 10 15.21C10 15.42 10.15 15.67 10.55 15.59C13.71 14.53 16 11.53 16 8C16 3.58 12.42 0 8 0Z" transform="scale(64)" fill="#1B1F23" />
                </svg>
            </a>
        </div>
		<div class="social-item">
            <div class="bubble">View the matSFX Archive with all our progress on the App</div>
            <a href="archive" title="Gallery">
                <svg viewBox="0 0 20 24" fill="#fff" xmlns="http://www.w3.org/2000/svg" stroke="">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.41959 3.23866C6.23018 3.05852 7.19557 3 8.312 3H9.92963C10.9327 3 11.8694 3.5013 12.4258 4.3359L13.2383 5.5547C13.4238 5.83288 13.736 6 14.0704 6H19.1258C20.7233 6 22.0181 7.26115 22.0029 8.8852C21.9847 10.8192 22 12.7539 22 14.688C22 15.8044 21.9415 16.7698 21.7613 17.5804C21.5787 18.4024 21.2579 19.1251 20.6915 19.6915C20.1251 20.2579 19.4024 20.5787 18.5804 20.7613C17.7698 20.9415 16.8044 21 15.688 21H8.312C7.19557 21 6.23018 20.9415 5.41959 20.7613C4.59764 20.5787 3.87488 20.2579 3.30848 19.6915C2.74209 19.1251 2.42133 18.4024 2.23866 17.5804C2.05852 16.7698 2 15.8044 2 14.688V9.312C2 8.19557 2.05852 7.23018 2.23866 6.41959C2.42133 5.59764 2.74209 4.87488 3.30848 4.30848C3.87488 3.74209 4.59764 3.42133 5.41959 3.23866Z" fill="#ffffff" />
                    </g>
                </svg>
            </a>
        </div>
		<div class="social-item">
			<div class="bubble">matSFX is hosted by dNodes.net</div>
			<a href="https://dnodes.net/" title="dNodes">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="300" height="300" viewBox="0 0 300 300" preserveAspectRatio="xMidYMid meet">
					<g transform="translate(0,300) scale(0.1,-0.1)" fill="#ffffff" stroke="none">
						<path d="M213 2620 c-90 -18 -156 -70 -192 -150 -20 -44 -21 -61 -21 -963 0 -914 0 -917 21 -962 12 -25 41 -63 66 -84 85 -76 -11 -71 1408 -71 1407 0 1332 -3 1412 65 23 20 53 60 67 90 l26 54 0 908 0 908 -24 50 c-25 55 -89 118 -145 143 -33 16 -148 17 -1306 19 -698 0 -1289 -3 -1312 -7z m2544 -121 c35 -13 79 -54 101 -94 15 -27 17 -108 19 -865 1 -497 -1 -855 -7 -883 -12 -61 -41 -102 -90 -127 -38 -20 -64 -20 -1280 -20 -1025 0 -1246 2 -1273 14 -42 17 -83 66 -97 114 -8 25 -10 303 -8 893 l3 855 27 41 c15 23 44 49 70 62 l43 21 1232 0 c846 0 1241 -3 1260 -11z" />
						<path d="M293 2420 c-13 -5 -32 -24 -43 -42 -19 -31 -20 -47 -18 -236 l3 -204 33 -29 33 -30 142 4 c109 2 153 7 187 22 27 11 80 20 135 23 79 4 100 1 169 -23 48 -17 101 -27 135 -28 l56 0 0 47 c-1 25 -2 150 -3 276 l-2 230 -402 -1 c-222 0 -413 -4 -425 -9z" />
						<path d="M1595 2417 c-3 -7 -4 -129 -3 -272 l3 -260 551 -3 c543 -2 551 -2 577 18 38 30 46 74 45 255 0 192 -7 229 -51 255 -31 19 -54 20 -575 20 -429 0 -544 -3 -547 -13z m659 -163 c20 -19 22 -151 4 -177 -12 -16 -37 -17 -263 -15 l-250 3 -3 88 c-2 57 1 94 9 103 9 11 58 14 250 14 203 0 240 -2 253 -16z m322 -15 c26 -24 40 -75 30 -107 -4 -11 -18 -32 -33 -46 -77 -77 -209 25 -159 123 32 62 112 77 162 30z" />
						<path d="M268 1761 l-33 -29 -3 -204 c-2 -179 0 -207 15 -237 30 -58 39 -49 46 47 12 172 49 293 118 395 l39 57 -75 0 c-68 0 -77 -2 -107 -29z" />
						<path d="M1116 1765 c9 -14 20 -25 24 -25 4 0 5 11 2 25 -3 16 -11 25 -23 25 -18 0 -19 -1 -3 -25z" />
						<path d="M1595 1777 c-3 -7 -4 -131 -3 -277 l3 -265 540 -3 c478 -3 544 -1 570 13 56 29 59 43 62 245 3 207 -4 250 -46 279 -28 21 -41 21 -575 21 -432 0 -548 -3 -551 -13z m657 -174 c14 -13 18 -31 18 -90 0 -116 7 -113 -266 -113 -136 0 -233 4 -245 10 -17 9 -19 22 -19 98 0 55 4 92 12 100 9 9 77 12 247 12 204 0 237 -2 253 -17z m298 2 c32 -17 60 -61 60 -95 0 -59 -67 -116 -121 -103 -36 9 -78 52 -85 89 -7 40 16 88 54 108 35 19 56 20 92 1z" />
						<path d="M907 1546 c-48 -17 -83 -49 -108 -97 -18 -36 -39 -138 -39 -191 l0 -28 201 0 202 0 -6 78 c-3 42 -11 94 -17 115 -15 50 -73 114 -111 121 -73 14 -86 15 -122 2z" />
						<path d="M773 1137 c-7 -12 7 -68 28 -109 45 -89 159 -124 253 -78 36 17 50 32 70 73 14 29 26 67 28 85 l3 33 -188 2 c-104 1 -191 -1 -194 -6z" />
						<path d="M1594 1135 c-3 -6 -3 -129 -2 -275 l3 -265 540 -3 c478 -3 544 -1 570 13 57 29 60 42 61 247 0 201 -6 240 -46 269 -23 18 -60 19 -574 21 -364 2 -550 0 -552 -7z m656 -185 c16 -16 20 -33 20 -92 0 -113 11 -108 -270 -108 -170 0 -239 3 -248 12 -8 8 -12 45 -12 100 0 72 3 89 18 97 10 6 109 11 245 11 214 0 228 -1 247 -20z m333 -21 c50 -61 19 -155 -56 -174 -63 -16 -136 59 -122 125 7 31 50 76 80 82 31 6 78 -10 98 -33z" />
						<path d="M254 1103 c-17 -24 -19 -52 -22 -213 -5 -293 0 -300 223 -300 l140 0 -51 34 c-69 46 -137 125 -174 199 -31 65 -70 201 -70 248 0 32 -9 59 -20 59 -4 0 -16 -12 -26 -27z" />
						<path d="M1091 708 c-17 -24 -46 -53 -64 -66 -18 -13 -41 -29 -52 -37 -18 -13 -4 -14 118 -15 75 0 137 2 137 5 0 15 -86 155 -96 155 -5 0 -25 -19 -43 -42z" />
					</g>
				</svg>
			</a>
		</div>
    </div> -->
</body>
</html>