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

        .privacy-container {
            max-width: 900px;
            margin: 8rem auto;
            padding: 4rem 1.5rem;
        }

        .privacy-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .privacy-header::after {
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

        .privacy-header h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        .privacy-header p {
            color: var(--gray-text);
            font-size: 1.1rem;
        }

        .privacy-content {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-md);
        }

        .privacy-intro {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(to bottom, var(--card-hover), var(--card-bg));
            border-radius: var(--border-radius);
        }

        .privacy-intro h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .privacy-section {
            margin-bottom: 3rem;
        }

        .privacy-section:last-child {
            margin-bottom: 0;
        }

        .privacy-section h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .privacy-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .privacy-section ul li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--gray-text);
        }

        .privacy-section ul li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--primary-color);
        }

        .last-updated {
            text-align: center;
            margin-top: 2rem;
            color: var(--gray-text);
            font-size: 0.9rem;
        }

        .privacy-section.highlight {
            background-color: var(--card-hover);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .privacy-container {
                padding: 2rem 1rem;
            }

            .privacy-header h1 {
                font-size: 2.5rem;
            }

            .privacy-content {
                padding: 1.5rem;
            }

            .privacy-intro {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
	<?php
    require_once '../includes/header.php';
    ?>
	
    <div class="privacy-container">
        <div class="privacy-header">
            <h1>Privacy Policy</h1>
            <p>We value your privacy and are committed to protecting your personal information.</p>
        </div>

        <div class="privacy-content">
            <div class="privacy-intro">
                <h2>Your Privacy Matters</h2>
                <p>We believe in transparency and giving you control over your data.</p>
            </div>

            <div class="privacy-section">
                <h2>Information We Collect</h2>
                <ul>
                    <li>Personal information provided during account registration.</li>
                    <li>Usage data, including analytics and interaction with the service.</li>
                    <li>Device and browser information.</li>
                    <li>Cookies and similar technologies used to enhance your experience.</li>
                </ul>
            </div>

            <div class="privacy-section">
                <h2>How We Use Your Information</h2>
                <ul>
                    <li>To provide, improve, and personalize our services.</li>
                    <li>To communicate with you regarding updates and important service information.</li>
                    <li>To maintain and enhance platform security.</li>
                </ul>
            </div>

            <div class="privacy-section highlight">
                <h2>Data Protection</h2>
                <ul>
                    <li>We implement industry-standard security measures to protect your data.</li>
                    <li>Regular security audits are conducted to ensure compliance.</li>
                    <li>All data transmission is encrypted to ensure privacy.</li>
                    <li>Your data is stored securely using best practices.</li>
                </ul>
            </div>

            <div class="privacy-section">
                <h2>Your Rights</h2>
                <ul>
                    <li>You have access to your personal data and can request to view it.</li>
                    <li>You can request the deletion of your personal data at any time.</li>
                    <li>Options to opt-out of marketing communications are available.</li>
                    <li>You can manage your cookie preferences to control tracking and usage.</li>
                </ul>
            </div>

            <p class="last-updated">Last updated: December 28, 2024</p>
        </div>
    </div>
</body>
</html>