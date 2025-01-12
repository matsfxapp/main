<?php
require_once 'config/config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
    <title>Email Verification</title>
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
            background-color: var(--dark-bg);
            color: var(--light-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .verification-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .verification-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .success {
            color: var(--primary-color);
        }

        .error {
            color: #FF4E4E;
        }

        h2 {
            margin-bottom: 15px;
        }

        p {
            color: var(--gray-text);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php
        if (isset($_GET['code'])) {
            $verification_code = $_GET['code'];
            $stmt = $pdo->prepare("SELECT user_id, email FROM users WHERE verification_code = ? AND email_verified = 0");
            $stmt->execute([$verification_code]);
            $user = $stmt->fetch();
            
            if ($user) {
                $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE user_id = ?");
                $updateStmt->execute([$user['user_id']]);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['email_verified'] = true;
                ?>
                <div class="verification-icon success">
                    &#10004;
                </div>
                <h2 class="success">Email Verified</h2>
                <p>Your email has been verified successfully! You will be redirected to your dashboard shortly.</p>
                <script>
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                </script>
                <?php
            } else {
                ?>
                <div class="verification-icon error">
                    &#10006;
                </div>
                <h2 class="error">Verification Failed</h2>
                <p>Invalid or expired verification code. Please request a new verification link.</p>
                <script>
                    setTimeout(() => {
                        window.location.href = '/register';
                    }, 2000);
                </script>
                <?php
            }
        } else {
            ?>
            <div class="verification-icon error">
                &#9888;
            </div>
            <h2 class="error">No Verification Code</h2>
            <p>No verification code was provided. Please check the link you received.</p>
            <script>
                setTimeout(() => {
                    window.location.href = '/signup';
                }, 2000);
            </script>
            <?php
        }
        ?>
    </div>
	<script src='https://storage.ko-fi.com/cdn/scripts/overlay-widget.js'></script>
	<script>
	  kofiWidgetOverlay.draw('matsfx', {
		'type': 'floating-chat',
		'floating-chat.donateButton.text': 'Support Us',
		'floating-chat.donateButton.background-color': '#ffffff',
		'floating-chat.donateButton.text-color': '#323842'
	  });
	</script>
</body>
</html>