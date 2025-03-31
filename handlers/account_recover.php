<?php
require_once 'config/config.php';

// Recovery page logic
$token = $_GET['token'] ?? null;
$recoveryError = null;
$recoverySuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    try {
        // Verify token and restore account
        $stmt = $pdo->prepare("
            SELECT user_id, recovery_token, recovery_token_expires_at, email 
            FROM users 
            WHERE recovery_token IS NOT NULL 
            AND recovery_token_expires_at > NOW()
        ");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($token, $user['recovery_token'])) {
            // Restore account
            $restoreStmt = $pdo->prepare("
                UPDATE users 
                SET 
                    is_active = 1, 
                    deletion_requested_at = NULL,
                    deletion_protection_expires_at = NULL,
                    recovery_token = NULL,
                    recovery_token_expires_at = NULL,
                    termination_reason = NULL
                WHERE user_id = ?
            ");
            $restoreStmt->execute([$user['user_id']]);

            $recoverySuccess = true;
        } else {
            $recoveryError = "Invalid or expired recovery token.";
        }
    } catch (PDOException $e) {
        $recoveryError = "Database error occurred.";
        error_log("Account recovery error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Recovery - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0A1220;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .recovery-container {
            background-color: #111827;
            padding: 2rem;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid #1F2937;
        }
        .recovery-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .recovery-btn {
            background-color: #2D7FF9;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1rem;
            transition: background-color 0.3s ease;
        }
        .recovery-btn:hover {
            background-color: #1E6AD4;
        }
        .error-message {
            color: #FF4E4E;
            margin-top: 1rem;
        }
        .success-message {
            color: #48BB78;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <?php if ($recoverySuccess): ?>
            <div class="recovery-icon">✅</div>
            <h2>Account Recovered!</h2>
            <p>Your account has been successfully restored.</p>
            <a href="/login" class="recovery-btn">Log In</a>
        <?php elseif ($recoveryError): ?>
            <div class="recovery-icon">⚠️</div>
            <h2>Recovery Failed</h2>
            <p class="error-message"><?php echo htmlspecialchars($recoveryError); ?></p>
            <a href="/login" class="recovery-btn">Back to Login</a>
        <?php else: ?>
            <div class="recovery-icon">🔐</div>
            <h2>Account Recovery</h2>
            <p>Click the button below to recover your account within the 7-day protection period.</p>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <button type="submit" class="recovery-btn">Restore My Account</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>