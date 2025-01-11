<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 5rem;
            position: relative;
        }

        .contact-header::after {
            content: '';
            position: absolute;
            bottom: -2rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .contact-header h1 {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.03em;
            font-weight: 800;
        }

        .contact-header p {
            color: var(--gray-text);
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .contact-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .contact-card:hover::before {
            transform: scaleX(1);
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .contact-card i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .contact-card h3 {
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
            color: var(--light-text);
        }

        .contact-card p {
            color: var(--gray-text);
            margin: 0.5rem 0;
            font-size: 1.1rem;
        }

        .contact-card a {
            color: var(--gray-text);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-card a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .contact-header h1 {
                font-size: 3rem;
            }

            .contact-card {
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {
            .contact-header h1 {
                font-size: 2.5rem;
            }

            .contact-header p {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

	<?php require_once '../includes/header.php';?>
	
    <div class="contact-container">
        <div class="contact-header">
            <h1>Let's Connect</h1>
            <p>Have questions about matSFX? We're here to provide quick and professional support.</p>
        </div>

        <div class="contact-cards">
            <div class="contact-card">
                <i class="fas fa-envelope-open-text"></i>
                <h3>Email Support</h3>
                <p><a href="mailto:support@matsfx.com">support@matsfx.com</a></p>
                <p><a href="mailto:business@matsfx.com">business@matsfx.com</a></p>
            </div>

            <div class="contact-card">
                <i class="fas fa-bolt"></i>
                <h3>Quick Response</h3>
                <p>24 Hour Response Time</p>
                <p>Professional Support</p>
            </div>
        </div>
    </div>
</body>
</html>