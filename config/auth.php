<?php
require_once 'config.php';

// Register new user
function registerUser($username, $email, $password, $profile_picture) {
    global $pdo;

    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['error' => 'Password must be at least 8 characters long'];
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        return ['error' => 'Email already registered'];
    }

    $username = sanitizeInput($username);
    $email = sanitizeInput($email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(16));

    $query = "INSERT INTO users (username, email, password, profile_picture, email_verified, verification_code) 
              VALUES (:username, :email, :password, :profile_picture, 0, :verification_code)";
    $stmt = $pdo->prepare($query);
    
    try {
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':profile_picture' => $profile_picture,
            ':verification_code' => $verification_code
        ]);
        return ['success' => true, 'verification_code' => $verification_code];
    } catch (PDOException $e) {
        return ['error' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Login user - Modified to allow login without email verification
function loginUser($email, $password, $remember = false) {
    global $pdo;
    
    $email = sanitizeInput($email);
    
    $query = "SELECT user_id, username, password, is_admin, email_verified, 
              login_attempts, last_attempt_time 
              FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check for brute force attempts
    if ($user && checkBruteForce($user['login_attempts'], $user['last_attempt_time'])) {
        return ['error' => 'Account temporarily locked. Please try again later.'];
    }

    if ($user && password_verify($password, $user['password'])) {
        // No longer checking for email verification as a login requirement
        
        // Reset login attempts on successful login
        resetLoginAttempts($user['user_id']);
        
        // Start secure session
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_only_cookies', 1);
            session_start();
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['email_verified'] = $user['email_verified']; // Store verification status
        $_SESSION['is_guest'] = false;
        $_SESSION['last_activity'] = time();
        
        // Set remember me token if requested
        if ($remember) {
            createRememberMeToken($user['user_id']);
        }
        
        return ['success' => true];
    }
    
    // Increment login attempts on failure
    if ($user) {
        incrementLoginAttempts($user['user_id']);
    }
    
    return ['error' => 'Invalid email or password.'];
}

// Create remember me token
function createRememberMeToken($user_id) {
    global $pdo;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $query = "UPDATE users SET remember_token = :token, token_expires = :expires 
              WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':token' => password_hash($token, PASSWORD_DEFAULT),
        ':expires' => $expires,
        ':user_id' => $user_id
    ]);
    
    setcookie(
        'remember_me',
        $user_id . ':' . $token,
        [
            'expires' => strtotime('+30 days'),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Clear remember me token
function clearRememberMeToken() {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        $query = "UPDATE users SET remember_token = NULL, token_expires = NULL 
                  WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    }
    
    setcookie('remember_me', '', time() - 3600, '/');
}

// Logout user
function logoutUser() {
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    try {
        clearRememberMeToken();
    } catch (PDOException $e) {
        error_log("Error in clearRememberMeToken: " . $e->getMessage());
    }
    
    $_SESSION = [];
    session_destroy();
    
    header("Location: /");
    exit();
}
function checkBruteForce($attempts, $last_attempt_time) {
    if ($attempts >= 5) {
        $lockout_time = strtotime($last_attempt_time) + 15 * 60;
        if (time() < $lockout_time) {
            return true;
        }
        return false;
    }
    return false;
}

function incrementLoginAttempts($user_id) {
    global $pdo;
    
    $query = "UPDATE users SET login_attempts = login_attempts + 1, 
              last_attempt_time = NOW() WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
}

// Reset login attempts
function resetLoginAttempts($user_id) {
    global $pdo;
    
    $query = "UPDATE users SET login_attempts = 0, last_attempt_time = NULL 
              WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
}

// Verify email
function verifyEmail($verification_code) {
    global $pdo;
    
    $query = "UPDATE users SET email_verified = 1, verification_code = NULL 
              WHERE verification_code = :code AND email_verified = 0";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':code' => $verification_code]);
    
    return $stmt->rowCount() > 0;
}

// Function to check if user has verified email
function isEmailVerified($user_id) {
    global $pdo;
    
    $query = "SELECT email_verified FROM users WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['email_verified'] == 1;
}

// Function to resend verification email
function resendVerificationEmail($user_id) {
    global $pdo;
    
    // Generate new verification code
    $verification_code = bin2hex(random_bytes(16));
    
    // Update the user's verification code
    $updateStmt = $pdo->prepare("UPDATE users SET verification_code = :code WHERE user_id = :user_id");
    if (!$updateStmt->execute([':code' => $verification_code, ':user_id' => $user_id])) {
        return ['error' => 'Failed to update verification code'];
    }
    
    // Get user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['error' => 'User not found'];
    }
    
    // Send the verification email
    // This would call the sendVerificationEmail function from register.php
    // or implement it here
    
    return ['success' => true, 'email' => $user['email']];
}

// Update user profile
function updateUserProfile($user_id, $data) {
    global $pdo;
    
    $allowed_fields = ['username', 'email', 'profile_picture'];
    $updates = [];
    $params = [':user_id' => $user_id];
    
    foreach ($data as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $updates[] = "$field = :$field";
            $params[":$field"] = sanitizeInput($value);
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $query = "UPDATE users SET " . implode(', ', $updates) . 
             " WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    
    return $stmt->execute($params);
}