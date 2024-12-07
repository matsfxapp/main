<?php
require_once 'config.php';

// Register new user
function registerUser($username, $email, $password, $profile_picture) {
    global $conn;

    $username = sanitizeInput($username);
    $email = sanitizeInput($email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(16)); // Generate unique verification code

    $query = "INSERT INTO users (username, email, password, profile_picture, email_verified, verification_code) 
              VALUES (:username, :email, :password, :profile_picture, 0, :verification_code)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':profile_picture', $profile_picture);
    $stmt->bindParam(':verification_code', $verification_code);

    if ($stmt->execute()) {
        return $verification_code; // Return the code for sending email
    }
    return false;
}

// Login user
function loginUser($email, $password) {
    global $conn;

    $email = sanitizeInput($email);

    $query = "SELECT user_id, username, password, is_admin, email_verified FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            if ($user['email_verified'] == 0) {
                // Email not verified
                return ['error' => 'Please verify your email before logging in.'];
            }

            // Set session variables
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_guest'] = false;
            return true;
        }
    }
    return ['error' => 'Invalid email or password.'];
}

// Logout user
function logoutUser() {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Guest access
class Authentication {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function allowGuestAccess() {
        if (!isset($_SESSION)) {
            session_start();
        }

        // Create a temporary guest session
        $guest_id = 'guest_' . bin2hex(random_bytes(8));

        // Store guest session
        $stmt = $this->pdo->prepare("INSERT INTO guest_sessions (guest_id) VALUES (?)");
        $stmt->execute([$guest_id]);

        // Set session variables
        $_SESSION['is_guest'] = true;
        $_SESSION['guest_id'] = $guest_id;

        return $guest_id;
    }

    public function checkAccess($requires_auth = false) {
        if (!isset($_SESSION)) {
            session_start();
        }

        // If user is not logged in
        if (!isset($_SESSION['user_id'])) {
            if ($requires_auth) {
                $this->handleRestrictedAccess();
                return false;
            }

            // Create guest account if none exists
            if (!isset($_SESSION['is_guest'])) {
                $this->allowGuestAccess();
            }

            return true;
        }
        return true;
    }

    private function handleRestrictedAccess() {
        echo json_encode([
            'status' => 'error',
            'message' => 'This feature requires an account. Please log in or sign up.',
            'redirect' => '/login.php'
        ]);
        exit();
    }

    public function isFeatureAllowed($feature) {
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        $restricted_features = [
            'upload_song' => false,
            'like_song' => false,
            'follow_user' => false,
            'edit_profile' => false,
            'comment' => false
        ];

        return $restricted_features[$feature] ?? false;
    }
}
?>
