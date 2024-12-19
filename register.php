<?php
require_once 'config.php';
require_once 'auth.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isLoggedIn()) {
    header("Location: /");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $verification_code = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, email_verified, verification_code) VALUES (?, ?, ?, 0, ?)");
    if ($stmt->execute([$username, $email, $password, $verification_code])) {
        if (sendVerificationEmail($email, $verification_code)) {
            $success = "Registration successful! Check your email for verification.";
        } else {
            $error = "Registration successful, but failed to send verification email.";
        }
    } else {
        $error = "Error registering user. Please try again.";
    }
}

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'your_php_mailer_host';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_php_mailer_username';
        $mail->Password = 'your_php_mailer_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('set_to_your_email', 'Your Site Verification');
        $mail->addAddress($email);
        $verifyLink = "https://alpha.matsfx.com/verify?code=$code";

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to matSFX!';
        $mail->Body = '
		<!DOCTYPE html>
		<html lang="en">
		<head>
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
					font-family: Arial, sans-serif;
					background-color: var(--dark-bg);
					color: var(--light-text);
					margin: 0;
					padding: 20px;
					line-height: 1.6;
				}
				.container {
					max-width: 600px;
					margin: 0 auto;
					background-color: var(--card-bg);
					border-radius: var(--border-radius-lg);
					padding: 20px;
					box-shadow: var(--shadow-lg);
				}
				.btn {
					display: inline-block;
					background-color: var(--primary-color);
					color: var(--light-text);
					text-decoration: none;
					padding: 10px 20px;
					border-radius: var(--border-radius);
					margin: 20px 0;
				}
				.btn:hover {
					background-color: var(--primary-hover);
				}
			</style>
		</head>
		<body>
			<div class="container">
				<h1>Welcome to matSFX!</h1>
				<p>Welcome to matSFX! Verify your E-Mail by clicking the link below:</p>
				<a href="'.$verifyLink.'" class="btn">Verify Email</a>
				<p>If the button does not work, copy and paste this link:</p>
				<p>'.$verifyLink.'</p>
			</div>
		</body>
		</html>';

        $mail->AltBody = "Verify your email by clicking the link: $verifyLink";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx-logo-squared.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
    <title>Register - matSFX</title>
	<link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app_logos/matsfx-logo-squared.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

	<?php outputChristmasThemeCSS(); ?>
</head>
<body>
    <div class="container">
        <div class="upload-form">
            <h2>Register</h2>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn">Register</button>
            </form>

            <p>Already have an account? <a class="register-footer-link" href="login">Log in</a></p>
        </div>
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