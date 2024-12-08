<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'config.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = "mx.freenet.de";
        $mail->SMTPAuth   = true;
        $mail->Username   = "mat@fn.de";
        $mail->Password   = "eE3BO72eYg";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = "587";

        // Recipients
        $mail->setFrom('mat@fn.de', 'matSFX Accounts');
        $mail->addAddress($to);
        $mail->addReplyTo('mat@fn.de', 'matSFX Accounts');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

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

    $stmt = $conn->prepare("UPDATE users SET reset_token = :token WHERE email = :email");
    $stmt->execute([
        ':token' => $reset_token,
        ':email' => $email
    ]);

    $reset_link = "https://alpha.matsfx.com/resetpassword?token=$reset_token";
    $email_body = "
        <html>
        <body>
            <p>Hello,</p>
            <p>Click the link below to reset your password:</p>
            <p><a href=\"$reset_link\">$reset_link</a></p>
            <p>If you did not request this, please ignore this email.</p>
        </body>
        </html>
    ";

    if (sendEmail($email, "Password Reset Request", $email_body)) {
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
</body>
</html>