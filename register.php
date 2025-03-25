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
        $password = $_POST['password'];
        
        // Validation
        $validation_errors = [];
        
        // Check username
        if (strlen($username) < 3) {
            $validation_errors['username'] = "Username must be at least 3 characters long";
        }
        
        // Check email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors['email'] = "Please enter a valid email address";
        }
        
        // Check password
        if (strlen($password) < 6) {
            $validation_errors['password'] = "Password must be at least 6 characters long";
        }
        
        // Check if username already exists
        if (empty($validation_errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $validation_errors['username'] = "Username already taken. Please choose another one.";
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $validation_errors['email'] = "Email already registered. Please use a different email or login instead.";
            }
        }
        
        if (empty($validation_errors)) {
            $password = password_hash($password, PASSWORD_BCRYPT);
            $verification_code = bin2hex(random_bytes(16));
            
            $_SESSION['registration_data'] = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'verification_code' => $verification_code
            ];
            
            header("Location: register?step=2");
            exit();
        } else {
            $error = true;
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

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, email_verified, verification_code, profile_picture) 
                              VALUES (?, ?, ?, 0, ?, ?)");
        if ($stmt->execute([
            $registration_data['username'],
            $registration_data['email'],
            $registration_data['password'],
            $registration_data['verification_code'],
            $profile_picture
        ])) {
            if (sendVerificationEmail($registration_data['email'], $registration_data['verification_code'])) {
                $success = "Registration successful! Check your inbox and spam folders for the verification link.";
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
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join matSFX to discover and share music</p>
            </div>
            
            <div class="auth-body">
                <?php if (isset($success)): ?>
                    <div class="auth-alert success">
                        <div class="auth-alert-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="auth-alert-content">
                            <div class="auth-alert-title">Registration Complete</div>
                            <p class="auth-alert-message"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error) && $error === true && isset($validation_errors)): ?>
                    <div class="auth-alert error">
                        <div class="auth-alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="auth-alert-content">
                            <div class="auth-alert-title">Registration Failed</div>
                            <p class="auth-alert-message">Please correct the errors below.</p>
                        </div>
                    </div>
                <?php elseif (isset($error) && is_string($error)): ?>
                    <div class="auth-alert error">
                        <div class="auth-alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="auth-alert-content">
                            <div class="auth-alert-title">Registration Failed</div>
                            <p class="auth-alert-message"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="registration-steps">
                    <div class="step-indicator <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                        <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                    </div>
                    <div class="step-indicator <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                        2
                    </div>
                </div>

                <?php if ($step === 1): ?>
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input <?php echo isset($validation_errors['username']) ? 'error' : ''; ?>" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   placeholder="Choose a username" required>
                            <?php if (isset($validation_errors['username'])): ?>
                                <span class="form-error"><?php echo $validation_errors['username']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input <?php echo isset($validation_errors['email']) ? 'error' : ''; ?>" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   placeholder="Enter your email" required>
                            <?php if (isset($validation_errors['email'])): ?>
                                <span class="form-error"><?php echo $validation_errors['email']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input <?php echo isset($validation_errors['password']) ? 'error' : ''; ?>" 
                                   placeholder="Create a password" required>
                            <?php if (isset($validation_errors['password'])): ?>
                                <span class="form-error"><?php echo $validation_errors['password']; ?></span>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="auth-btn">Continue</button>
                        
                        <div class="social-auth">
                            <div class="social-auth-divider">
                                <div class="divider-line"></div>
                                <div class="divider-text">or sign up with</div>
                                <div class="divider-line"></div>
                            </div>
                            
                            <div class="coming-soon-badge">Coming in Full Release</div>
                            
                            <div class="social-buttons">
                                <button type="button" class="social-btn" disabled>
                                    <i class="fab fa-google"></i>
                                </button>
                                <button type="button" class="social-btn" disabled>
                                    <i class="fab fa-github"></i>
                                </button>
                                <button type="button" class="social-btn" disabled>
                                    <i class="fab fa-discord"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="POST" class="auth-form" enctype="multipart/form-data">
                        <div class="profile-upload">
                            <img src="defaults/default-profile.jpg" alt="Profile Preview" class="profile-preview" id="preview">
                            <label for="profile_picture" class="profile-upload-label">
                                <i class="fas fa-camera"></i> Choose Profile Picture
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" class="profile-upload-input" accept="image/*">
                            <button type="submit" class="skip-upload">Skip this step</button>
                        </div>

                        <button type="submit" class="auth-btn">Complete Registration</button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login" class="auth-link">Log in</a></p>
                </div>
            </div>
        </div>
    </div>

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

    document.addEventListener('DOMContentLoaded', function() {
        const profileInput = document.getElementById('profile_picture');
        if (profileInput) {
            profileInput.addEventListener('change', function() {
                previewImage(this);
            });
        }
    });
    </script>
    
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