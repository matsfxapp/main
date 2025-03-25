<?php
session_start();
require_once 'config/config.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    // Get return URL from session or default to home
    $return_url = isset($_SESSION['return_url']) ? $_SESSION['return_url'] : '/';
    unset($_SESSION['return_url']);
    header("Location: $return_url");
    exit();
}

// Store return URL in session if coming from a valid page
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    // Check if referrer is from your domain and not the login page itself
    if (strpos($referer['host'], 'alpha.matsfx.com') !== false && 
        !strpos($referer['path'], 'login') && 
        !strpos($referer['path'], 'register') && 
        !strpos($referer['path'], 'resetpassword')) {
        $_SESSION['return_url'] = $_SERVER['HTTP_REFERER'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $stmt = $pdo->prepare("SELECT user_id, username, password, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // login check
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email_verified'] = $user['email_verified']; // Store verification status in session
        
        // Handle remember me
        if ($remember) {
            createRememberMeToken($user['user_id']);
        }
        
        // Redirect to stored return url or default home
        $return_url = isset($_SESSION['return_url']) ? $_SESSION['return_url'] : '/';
        unset($_SESSION['return_url']);
        header("Location: $return_url");
        exit();
    } else {
        $error = "Invalid email or password.";
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

    <title>Login - matSFX</title>
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
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Login to continue to matSFX</p>
            </div>
            
            <div class="auth-body">
                <?php if (isset($error)): ?>
                    <div class="auth-alert error">
                        <div class="auth-alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="auth-alert-content">
                            <div class="auth-alert-title">Login Failed</div>
                            <p class="auth-alert-message"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required
                               placeholder="Enter your password">
                    </div>
                    
                    <div class="form-group forgot-password">
                        <a href="resetpassword">Forgot password?</a>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-container">
                            Remember me
                            <input type="checkbox" name="remember" id="remember"
                                <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                        </label>
                    </div>

                    <button type="submit" class="auth-btn">Login</button>
                    
                    <div class="social-auth">
                        <div class="social-auth-divider">
                            <div class="divider-line"></div>
                            <div class="divider-text">or continue with</div>
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
  
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register" class="auth-link">Sign up</a></p>
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