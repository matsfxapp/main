<?php
require_once 'config.php';

// Register new user
function registerUser($username, $email, $password, $profile_picture) {
    global $conn;
    
    $username = sanitizeInput($username);
    $email = sanitizeInput($email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (username, email, password, profile_picture) VALUES (:username, :email, :password, :profile_picture)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':profile_picture', $profile_picture);
    
    if ($stmt->execute()) {
        return true;
    }
    return false;
}

// Login user
function loginUser($email, $password) {
    global $conn;
    
    $email = sanitizeInput($email);
    
    $query = "SELECT user_id, username, password FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        if (password_verify($password, $result['password'])) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            return true;
        }
    }
    return false;
}

// Logout user
function logoutUser() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>