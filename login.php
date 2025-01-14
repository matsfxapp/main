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

    if ($user && password_verify($password, $user['password'])) {
        if ($user['email_verified'] == 1) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Handle remember me
            if ($remember) {
                createRememberMeToken($user['user_id']);
            }
            
            // Redirect to stored return url default home
            $return_url = $_SESSION['return_url'] ?? '/';
            unset($_SESSION['return_url']);
            header("Location: $return_url");
            exit();
        } else {
            $error = "Please verify your email address before logging in.";
        }
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
    <link rel="stylesheet" href="css/style.css">
    
    <?php outputChristmasThemeCSS(); ?>
    
    <style>
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        
        .remember-me label {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-form">
            <h2>Login</h2>

            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember"
                           <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                    <label for="remember">Remember Me</label>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>
  
            <p>Don't have an account? <a href="register">Sign up</a></p>
            <p>Forgot Password? <a href="resetpassword">Reset Password</a></p>
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