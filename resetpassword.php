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
                    <img src="'.$_SERVER['APP_URL'].'/app_logos/matsfx_logo.png" alt="matSFX Logo">
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

$status_message = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_reset') {
    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status_message = "Invalid email address.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $status_message = "If your email is registered, we'll send you a password reset link.";
            $success = true;
        } else {
            $reset_token = bin2hex(random_bytes(32));
            $token_expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, token_expiration = :expiration WHERE email = :email");
            $stmt->execute([
                ':token' => $reset_token,
                ':expiration' => $reset_token_expiration,
                ':email' => $email
            ]);

            $reset_link = "https://alpha.matsfx.com/resetpassword?token=$reset_token";
            $email_body = '';

            if (sendEmail($email, "Password Reset Request", $email_body, $reset_link)) {
                $status_message = "If your email is registered, we'll send you a password reset link.";
                $success = true;
            } else {
                $status_message = "Failed to send the email. Please try again later.";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $status_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $status_message = "Password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE reset_token = :token AND token_expiration > NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $status_message = "Invalid or expired token. Please request a new password reset link.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiration = NULL WHERE reset_token = :token");
            $stmt->execute([
                ':password' => $hashed_password,
                ':token' => $token
            ]);

            $status_message = "Password successfully reset. You can now log in with your new password.";
            $success = true;
        }
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
    <meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>Reset Password - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/auth.css">
    
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/">
                    <img src="/app_logos/matsfx_logo.png" alt="matSFX Logo" class="auth-logo">
                </a>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])): ?>
                    <h1 class="auth-title">Reset Password</h1>
                    <p class="auth-subtitle">Create a new password for your account</p>
                <?php else: ?>
                    <h1 class="auth-title">Forgot Password</h1>
                    <p class="auth-subtitle">Enter your email to reset your password</p>
                <?php endif; ?>
            </div>
            
            <div class="auth-body">
                <?php if ($status_message): ?>
                    <div class="auth-alert <?php echo $success ? 'success' : 'error'; ?>">
                        <div class="auth-alert-icon">
                            <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        </div>
                        <div class="auth-alert-content">
                            <div class="auth-alert-title"><?php echo $success ? 'Success' : 'Error'; ?></div>
                            <p class="auth-alert-message"><?php echo $status_message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])): ?>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                        
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-input" required
                                   placeholder="Enter your new password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
                                   placeholder="Confirm your new password">
                        </div>
                        
                        <button type="submit" class="auth-btn">Reset Password</button>
                    </form>
                <?php else: ?>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="action" value="request_reset">
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" required
                                   placeholder="Enter your email address">
                        </div>
                        
                        <button type="submit" class="auth-btn">Request Password Reset</button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <p>Remember your password? <a href="login" class="auth-link">Log in</a></p>
                </div>
            </div>
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