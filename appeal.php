<?php
require_once 'config/config.php';
require_once 'vendor/autoload.php';
require_once 'config/terminated_account_middleware.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if not logged in or not terminated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_terminated']) || $_SESSION['is_terminated'] !== true) {
    header("Location: /");
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appeal_reason = sanitizeInput($_POST['appeal_reason'] ?? '');
    
    if (empty($appeal_reason)) {
        $message = "Please provide a reason for your appeal.";
    } else if (strlen($appeal_reason) < 50) {
        $message = "Your appeal reason is too short. Please provide more details.";
    } else {
        // Store the appeal in the database
        try {
            // Check if appeals table exists, create if not
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'account_appeals'");
            if ($tableCheck->rowCount() == 0) {
                $pdo->exec("CREATE TABLE account_appeals (
                    appeal_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    appeal_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    appeal_reason TEXT NOT NULL,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    admin_response TEXT,
                    response_date DATETIME,
                    reviewed_by INT,
                    FOREIGN KEY (user_id) REFERENCES users(user_id),
                    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
                )");
            }
            
            // Check if user already has a pending appeal
            $checkStmt = $pdo->prepare("SELECT appeal_id FROM account_appeals WHERE user_id = ? AND status = 'pending'");
            $checkStmt->execute([$_SESSION['user_id']]);
            
            if ($checkStmt->rowCount() > 0) {
                $message = "You already have a pending appeal. Please wait for a response before submitting another.";
            } else {
                // Insert the appeal
                $stmt = $pdo->prepare("INSERT INTO account_appeals (user_id, appeal_reason) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $appeal_reason]);
                
                // Send email notification to admin
                sendAppealNotification($_SESSION['username'], $appeal_reason);
                
                $success = true;
                $message = "Your appeal has been submitted successfully. We will review your case and respond as soon as possible.";
            }
        } catch (PDOException $e) {
            error_log("Error submitting appeal: " . $e->getMessage());
            $message = "An error occurred while submitting your appeal. Please try again later.";
        }
    }
}

function sendAppealNotification($username, $appeal_reason) {
    // Get admin email
    global $pdo;
    $stmt = $pdo->prepare("SELECT email FROM users WHERE is_admin = 1 LIMIT 1");
    $stmt->execute();
    $adminEmail = $stmt->fetchColumn();
    
    if (!$adminEmail) {
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), 'matSFX System');
        $mail->addAddress($adminEmail);
        
        $mail->isHTML(true);
        $mail->Subject = 'Account Appeal: ' . $username;
        $mail->Body = "
        <html>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333333; background-color: #fafafa; padding: 20px;'>
            <div style='background-color: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                <div style='text-align: center; margin-bottom: 25px;'>
                    <h2 style='font-size: 22px; font-weight: 600; color: #222222; margin: 0;'>Account Termination Appeal</h2>
                </div>
                
                <div style='line-height: 1.6; font-size: 16px;'>
                    <p>A user has submitted an appeal for their account termination.</p>
                    
                    <div style='margin: 25px 0;'>
                        <div style='background-color: #f7f7f7; border-radius: 8px; padding: 20px; margin-bottom: 15px;'>
                            <p style='margin-top: 0; margin-bottom: 5px; font-weight: bold;'>Username:</p>
                            <p style='margin-top: 0; margin-bottom: 0;'>" . htmlspecialchars($username) . "</p>
                        </div>
                        
                        <div style='background-color: #f7f7f7; border-radius: 8px; padding: 20px;'>
                            <p style='margin-top: 0; margin-bottom: 5px; font-weight: bold;'>Appeal Reason:</p>
                            <div style='background-color: white; border-radius: 6px; padding: 15px; border-left: 3px solid #dddddd;'>
                                " . nl2br(htmlspecialchars($appeal_reason)) . "
                            </div>
                        </div>
                    </div>
                    
                    <p>Please review this appeal in the admin panel at your earliest convenience.</p>
                    
                    <div style='margin-top: 25px;'>
                        <a href='" . getenv('APP_URL') . "/admin/appeals' style='display: inline-block; background-color: #222222; color: white; text-decoration: none; padding: 12px 30px; border-radius: 50px; font-size: 16px; font-weight: 500;'>Review Appeal</a>
                    </div>
                </div>
            </div>
                
            <div style='text-align: center; margin-top: 20px; font-size: 13px; color: #888888;'>
                <p>© " . date("Y") . " matSFX. All rights reserved.</p>
                <p>This is an automated system notification.</p>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Account Termination Appeal\n\nUser: $username\n\nAppeal Reason: $appeal_reason\n\nPlease review this appeal in the admin panel.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending appeal notification: " . $mail->ErrorInfo);
        return false;
    }
}

// Get previous appeals
$previousAppeals = [];
try {
    $stmt = $pdo->prepare("
        SELECT appeal_date, status, admin_response, response_date 
        FROM account_appeals 
        WHERE user_id = ? 
        ORDER BY appeal_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $previousAppeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching previous appeals: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <title>Appeal Account Termination - matSFX</title>
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .appeal-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .appeal-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-top: 2rem;
        }
        
        .appeal-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }
        
        .appeal-form {
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-color);
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--light-text);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
        }
        
        textarea.form-input {
            min-height: 200px;
            resize: vertical;
        }
        
        .submit-button {
            background-color: var(--primary-color);
            color: white;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-block;
        }
        
        .submit-button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--gray-text);
            text-decoration: none;
        }
        
        .back-link:hover {
            color: var(--light-text);
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .guidelines {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: rgba(45, 127, 249, 0.1);
            border-radius: var(--border-radius);
        }
        
        .guidelines h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .guidelines ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }
        
        .guidelines li {
            margin-bottom: 0.5rem;
        }
        
        .appeal-history {
            margin-top: 3rem;
        }
        
        .appeal-item {
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .appeal-date {
            color: var(--gray-text);
            font-size: 0.875rem;
        }
        
        .appeal-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 500px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-approved {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .admin-response {
            margin-top: 1rem;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: var(--border-radius);
        }
        
        @media (max-width: 768px) {
            .appeal-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="appeal-container">
        <div class="appeal-card">
            <h1 class="appeal-title">Appeal Account Termination</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <div class="guidelines">
                    <h3>Appeal Guidelines</h3>
                    <ul>
                        <li>Be honest and specific about why you believe the termination was incorrect.</li>
                        <li>Provide any relevant context or information that may have been missed.</li>
                        <li>If you acknowledge a violation, explain what steps you will take to ensure it doesn't happen again.</li>
                        <li>Keep your appeal respectful and to the point.</li>
                        <li>Appeals are typically reviewed within 3-5 business days.</li>
                    </ul>
                </div>
                
                <form method="POST" class="appeal-form">
                    <div class="form-group">
                        <label for="appeal_reason" class="form-label">Why do you believe your account should be reinstated?</label>
                        <textarea id="appeal_reason" name="appeal_reason" class="form-input" placeholder="Please provide a detailed explanation..." required minlength="50"></textarea>
                    </div>
                    
                    <button type="submit" class="submit-button">Submit Appeal</button>
                </form>
            <?php endif; ?>
            
            <a href="terminated.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Termination Notice
            </a>
            
            <?php if (!empty($previousAppeals)): ?>
                <div class="appeal-history">
                    <h3>Appeal History</h3>
                    
                    <?php foreach ($previousAppeals as $appeal): ?>
                        <div class="appeal-item">
                            <div>
                                <span class="appeal-date">
                                    <?php echo date('F j, Y g:i A', strtotime($appeal['appeal_date'])); ?>
                                </span>
                                <span class="appeal-status status-<?php echo htmlspecialchars($appeal['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($appeal['status'])); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($appeal['admin_response'])): ?>
                                <div class="admin-response">
                                    <strong>Admin Response (<?php echo date('F j, Y', strtotime($appeal['response_date'])); ?>):</strong>
                                    <p><?php echo nl2br(htmlspecialchars($appeal['admin_response'])); ?></p>
                                </div>
                            <?php elseif ($appeal['status'] === 'pending'): ?>
                                <p><em>Your appeal is being reviewed. We'll respond as soon as possible.</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>