<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../music_handlers.php';
require_once __DIR__ . '/../user_handlers.php';

if (!isLoggedIn()) {
    http_response_code(403);
    die("Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$userData = getUserData($user_id);

try {
    // Check if necessary columns exist in the users table
    $columns = ['marked_for_deletion', 'deletion_requested_at'];
    foreach ($columns as $column) {
        $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $checkColumnStmt->execute([$column]);
        if ($checkColumnStmt->rowCount() == 0) {
            $type = ($column == 'marked_for_deletion') ? 'TINYINT(1) DEFAULT 0' : 'DATETIME NULL';
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type}");
        }
    }
    
    // Mark the user for deletion
    $markStmt = $pdo->prepare("
        UPDATE users 
        SET 
            marked_for_deletion = 1,
            deletion_requested_at = NOW()
        WHERE user_id = ?
    ");
    
    $result = $markStmt->execute([$user_id]);
    
    if ($result) {
        // Send a deletion confirmation email
        if (function_exists('sendDeletionConfirmationEmail')) {
            sendDeletionConfirmationEmail($userData['email'], $user_id);
        }
        
        // Redirect with recovery information
        header("Location: /settings?message=Account%20marked%20for%20deletion.%20You%20have%207%20days%20to%20recover%20your%20account%20before%20permanent%20deletion.");
        exit();
    } else {
        throw new Exception("Failed to mark account for deletion");
    }
} catch (Exception $e) {
    error_log("Account deletion marking error: " . $e->getMessage());
    header("Location: /settings?error=Account%20marking%20failed:%20" . urlencode($e->getMessage()));
    exit();
}

// Keep the email function in case it's needed
function sendDeletionConfirmationEmail($email, $user_id) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');
        
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), 'matSFX Support');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Account Deletion Request for matSFX';
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>Account Deletion Request</h2>
            <p>We've received a request to delete your matSFX account.</p>
            
            <div style='background-color: #f4f4f4; padding: 15px; border-radius: 5px;'>
                <h3>What happens next?</h3>
                <p>Your account has been marked for deletion and will be permanently deleted in 7 days.</p>
                <p>If this was a mistake, you can cancel this process by logging in and clicking the 'Cancel Account Deletion' button in your account settings.</p>
            </div>
            
            <p>If you did not request this deletion, please contact support at support@matsfx.com</p>
            
            <p>Best regards,<br>matSFX Support Team</p>
        </body>
        </html>";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Deletion email error: " . $e->getMessage());
    }
}
?>