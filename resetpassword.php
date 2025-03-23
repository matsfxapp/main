<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'config/config.php';

function sendEmail($to, $subject, $body, $reset_link = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');

        // Recipients
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME2'));
        $mail->addAddress($to);
        $mail->addReplyTo(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME2'));

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body ?: '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    line-height: 1.6;
                    background-color: #f4f4f4;
                    color: #333333;
                }
                .email-wrapper {
                    background-color: #ffffff;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 0;
                }
                .header {
                    background-color: #2D7FF9;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header img {
                    max-width: 150px;
                    height: auto;
                }
                .content {
                    padding: 40px 20px;
                    background-color: #ffffff;
                }
                h1 {
                    color: #2D7FF9;
                    font-size: 24px;
                    margin: 0 0 20px 0;
                    text-align: center;
                }
                p {
                    margin: 0 0 20px 0;
                    font-size: 16px;
                    color: #555555;
                }
                .button {
                    display: block;
                    width: 200px;
                    margin: 30px auto;
                    padding: 15px 25px;
                    background-color: #2D7FF9;
                    color: #ffffff !important;
                    text-align: center;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    font-size: 16px;
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #666666;
                }
                .security-notice {
                    background-color: #fff3cd;
                    border: 1px solid #ffeeba;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                    font-size: 14px;
                    color: #856404;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="header">
                    <img src="'.$_SERVER['APP_URL'].'/assets/images/logo.png" alt="matSFX Logo">
                </div>
                <div class="content">
                    <h1>Password Reset Request</h1>
                    <p>We received a request to reset your password for your matSFX account.</p>
                    <a href="'.$reset_link.'" class="button">Reset Password</a>
                    <p>If you didn\'t request this, you can safely ignore this email. Your password will not be changed.</p>
                    <div class="security-notice">
                        <p>For security: This link will expire in 1 hour and can only be used once.</p>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; '.date("Y").' matSFX. All rights reserved.</p>
                    <p>This email was sent to '.$to.'</p>
                </div>
            </div>
        </body>
        </html>';

        // Plain text alternative
        $mail->AltBody = "Password Reset Request\n\n" .
                        "We received a request to reset your password for your matSFX account.\n\n" .
                        "To reset your password, click or copy this link:\n" .
                        $reset_link . "\n\n" .
                        "If you didn't request this, you can safely ignore this email.\n" .
                        "For security: This link will expire in 1 hour and can only be used once.\n\n" .
                        "© " . date("Y") . " matSFX. All rights reserved.\n" .
                        "This email was sent to " . $to;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_reset') {
    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Email not found.";
        exit;
    }

    $reset_token = bin2hex(random_bytes(32));
    $reset_token_expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expiration = :expiration WHERE email = :email");
    $stmt->execute([
        ':token' => $reset_token,
        ':expiration' => $reset_token_expiration,
        ':email' => $email
    ]);

    $reset_link = "https://alpha.matsfx.com/resetpassword?token=$reset_token";
    $email_body = '';

    if (sendEmail($email, "Password Reset Request", $email_body, $reset_link)) {
        echo "A password reset link has been sent to your email.";
    } else {
        echo "Failed to send the email. Please try again later.";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['token'];
    $new_password = $_POST['password'];

    if (strlen($new_password) < 6) {
        echo "Password must be at least 6 characters long.";
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Invalid token.";
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL WHERE reset_token = :token");
    $stmt->execute([
        ':password' => $hashed_password,
        ':token' => $token
    ]);

    echo "Password successfully reset.";
    exit;
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
	<meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
	<link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>matSFX - Password Reset</title>
    <link rel="stylesheet" href="css/reset-password.css">
</head>
<body>
    <div class="container">
        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])): ?>
            <div class="form-title">Reset Password</div>
            <form method="POST" action="resetpassword">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                <div class="form-group">
                    <label for="password" class="form-label">New Password:</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="form-title">Forgot Password</div>
            <form method="POST" action="resetpassword">
                <input type="hidden" name="action" value="request_reset">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address:</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <button type="submit" class="btn">Request Password Reset</button>
            </form>
        <?php endif; ?>
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
