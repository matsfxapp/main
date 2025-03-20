<?php
require_once 'config/config.php';
require_once 'config/auth.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isLoggedIn()) {
    header("Location: /");
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username already taken. Please choose another one.";
        } else {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $verification_code = bin2hex(random_bytes(16));
            
            $_SESSION['registration_data'] = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
                'verification_code' => $verification_code
            ];
            
            header("Location: register?step=2");
            exit();
        }
    } else if ($step === 2) {
        $registration_data = $_SESSION['registration_data'] ?? null;
        
        if (!$registration_data) {
            header("Location: register");
            exit();
        }
        
        $profile_picture = 'defaults/default-profile.jpg'; // Default path
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                require_once 'music_handlers.php';
                
                $upload_result = uploadToMinIO('profiles', $_FILES['profile_picture']);
                
                if ($upload_result['success']) {
                    $profile_picture = $upload_result['path'];
                } else {
                    error_log("Failed to upload profile picture to MinIO: " . $upload_result['message']);
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, email_verified, verification_code, profile_picture) VALUES (?, ?, ?, 0, ?, ?)");
        if ($stmt->execute([
            $registration_data['username'],
            $registration_data['email'],
            $registration_data['password'],
            $registration_data['verification_code'],
            $profile_picture
        ])) {
            if (sendVerificationEmail($registration_data['email'], $registration_data['verification_code'])) {
                $success = "Registration successful! Check your email for verification.";
                unset($_SESSION['registration_data']);
            } else {
                $error = "Registration successful, but failed to send verification email.";
            }
        } else {
            $error = "Error registering user. Please try again.";
        }
    }
}

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME'));
        $mail->addAddress($email);
        $verifyLink = getenv('APP_URL') . "/verify?code=$code";

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to matSFX!';
        $mail->Body = '
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
                .link-text {
                    word-break: break-all;
                    color: #2D7FF9;
                    font-size: 14px;
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #666666;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="header">
                    <img src="alpha.matsfx.com/app_logos/matsfx_logo.png" alt="matSFX Logo">
                </div>
                <div class="content">
                    <h1>Welcome to matSFX!</h1>
                    <p>Thank you for joining matSFX! To get started, please verify your email address by clicking the button below:</p>
                    <a href="'.$verifyLink.'" class="button">Verify Email Address</a>
                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p class="link-text">'.$verifyLink.'</p>
                </div>
                <div class="footer">
                    <p>&copy; '.date("Y").' matSFX. All rights reserved.</p>
                    <p>This email was sent to '.$email.'</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Welcome to matSFX! Please verify your email by clicking this link: $verifyLink";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
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
    <meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <title>Register - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
    
    <style>
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        a:hover {
            color: var(--accent-color);
        }

        .profile-picture-upload {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .profile-image-label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .profile-picture-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            transition: transform 0.2s;
        }
        
        .profile-image-label:hover .profile-picture-preview {
            transform: scale(1.05);
        }
        
        .profile-preview-text {
            color: var(--primary-color);
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .hidden-file-input {
            display: none;
        }
        
        .skip-step {
            color: var(--gray-text);
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.2s;
        }
        
        .skip-step:hover {
            color: var(--light-text);
        }
        
        .complete-registration {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .steps-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .steps-indicator::before {
            content: '';
            position: absolute;
            width: 30px;
            height: 2px;
            background: var(--primary-color);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light-text);
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .step.active {
            background: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-form">
            <h2>Register</h2>
            
            <div class="steps-indicator">
                <div class="step <?php echo $step === 1 ? 'active' : ''; ?>">1</div>
                <div class="step <?php echo $step === 2 ? 'active' : ''; ?>">2</div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
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

                    <button type="submit" class="btn">Continue</button>
                </form>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-picture-upload">
                        <label for="profile_picture" class="profile-image-label">
                            <img src="defaults/default-profile.jpg" alt="Profile Preview" class="profile-picture-preview" id="preview">
                            <div class="profile-preview-text">Profile Preview</div>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewImage(this)" class="hidden-file-input">
                        
                        <a href="#" class="skip-step">Skip this step</a>
                        <button type="submit" class="btn complete-registration">COMPLETE REGISTRATION</button>
                    </div>
                </form>

                <script>
                function previewImage(input) {
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('preview').src = e.target.result;
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                document.querySelector('.skip-step').addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector('form').submit();
                });
                </script>
            <?php endif; ?>

            <p>Already have an account? <a class="register-footer-link" href="login">Log in</a></p>
        </div>
    </div>
</body>
</html>
