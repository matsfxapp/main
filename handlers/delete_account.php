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
        
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), 'matSFX');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your matSFX account deletion request';
        $mail->Body = "
        <html>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333333; background-color: #fafafa; padding: 20px;'>
            <div style='background-color: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                <div style='text-align: center; margin-bottom: 25px;'>
                    <h2 style='font-size: 22px; font-weight: 600; color: #222222; margin: 0;'>Account Deletion Request</h2>
                </div>
                
                <div style='line-height: 1.6; font-size: 16px;'>
                    <p>Hello,</p>
                    <p>We've received a request to delete your matSFX account.</p>
                    
                    <div style='background-color: #f7f7f7; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                        <h3 style='font-size: 18px; margin-top: 0; margin-bottom: 15px;'>What happens now?</h3>
                        <p style='margin-top: 0;'>Your account has been marked for deletion and will be permanently deleted in 7 days.</p>
                        <p style='margin-bottom: 0;'>If this was a mistake, you can cancel this process by logging in and selecting the 'Cancel Account Deletion' option in your account settings.</p>
                    </div>
                    
                    <p>If you did not request this deletion, please contact us immediately at support@matsfx.com</p>
                    
                    <p style='margin-top: 25px;'>
                        Regards,<br>
                        The matSFX Team
                    </p>
                </div>
            </div>
                
            <div style='text-align: center; margin-top: 20px; font-size: 13px; color: #888888;'>
                <p>© " . date("Y") . " matSFX. All rights reserved.</p>
                <p>This email was sent to {$email}</p>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Account Deletion Request\n\nHello,\n\nWe've received a request to delete your matSFX account.\n\nYour account has been marked for deletion and will be permanently deleted in 7 days.\n\nIf this was a mistake, you can cancel this process by logging in and selecting the 'Cancel Account Deletion' option in your account settings.\n\nIf you did not request this deletion, please contact us immediately at support@matsfx.com";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Deletion email error: " . $e->getMessage());
        return false;
    }
}
?>