<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
	<link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app_logos/matsfx_logo.png">
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --primary-light: rgba(45, 127, 249, 0.1);
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --darker-bg: #060912;
            --card-bg: #111827;
            --card-hover: #1F2937;
            --nav-bg: rgba(17, 24, 39, 0.95);
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-color: #1F2937;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        body {
            background-color: var(--darker-bg);
            color: var(--light-text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .tos-container {
            max-width: 900px;
            margin: 8rem auto;
            padding: 4rem 1.5rem;
        }

        .tos-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .tos-header::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .tos-header h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        .tos-header p {
            color: var(--gray-text);
            font-size: 1.1rem;
        }

        .tos-content {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-md);
        }

        .tos-section {
            margin-bottom: 3rem;
        }

        .tos-section:last-child {
            margin-bottom: 0;
        }

        .tos-section h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .tos-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tos-section ul li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--gray-text);
        }

        .tos-section ul li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--primary-color);
        }

        .tos-section p {
            color: var(--gray-text);
            margin-bottom: 1rem;
        }

        .last-updated {
            text-align: center;
            margin-top: 2rem;
            color: var(--gray-text);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .tos-container {
                padding: 2rem 1rem;
            }

            .tos-header h1 {
                font-size: 2.5rem;
            }

            .tos-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
	<?php
    require_once 'includes/header.php';
    ?>
	
    <div class="tos-container">
        <div class="tos-header">
            <h1>Terms of Service</h1>
            <p>Please read these terms carefully before using our service.</p>
        </div>

        <div class="tos-content">
            <div class="tos-section">
                <h2>Acceptance of Terms</h2>
                <p>By using our service, you acknowledge and agree to be bound by these terms. You must be at least 12 years old to access the service. You are responsible for ensuring the security of your account. You consent to receiving communications related to the service.</p>
            </div>

            <div class="tos-section">
                <h2>User Responsibilities</h2>
                <ul>
                    <li>Provide accurate, truthful, and complete information.</li>
                    <li>Use the service in accordance with all applicable laws and regulations.</li>
                    <li>Do not attempt unauthorized access or misuse of the service.</li>
                    <li>Respect the intellectual property rights of others.</li>
                </ul>
            </div>

            <div class="tos-section">
                <h2>Service Usage</h2>
                <ul>
                    <li>Our service is provided "as is" without any warranties.</li>
                    <li>We reserve the right to modify or discontinue the service at any time without notice.</li>
                    <li>Violation of these terms may result in account termination.</li>
                    <li>Fair use policies and usage limitations apply.</li>
                </ul>
            </div>

            <div class="tos-section">
                <h2>Content Guidelines</h2>
                <ul>
                    <li>Do not post harmful, malicious, or illegal content.</li>
                    <li>Do not infringe on the intellectual property rights of third parties.</li>
                    <li>Unauthorized commercial activities are prohibited.</li>
                    <li>Content may be moderated or removed at our discretion.</li>
                </ul>
            </div>

            <div class="tos-section">
                <h2>Limitation of Liability</h2>
                <ul>
                    <li>We are not liable for any indirect, incidental, or consequential damages.</li>
                    <li>We are not responsible for any third-party content.</li>
                    <li>Service interruptions may occur, and we are not responsible for any consequences resulting from such interruptions.</li>
                </ul>
            </div>

            <div class="tos-section">
                <h2>Indemnification</h2>
                <p>You agree to indemnify and hold us harmless for any violations of these terms.</p>
            </div>

            <p class="last-updated">Last updated: December 28, 2024</p>
        </div>
    </div>
</body>
</html>