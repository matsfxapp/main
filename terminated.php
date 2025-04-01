<?php
require_once 'config/config.php';

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verify termination status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_terminated']) || $_SESSION['is_terminated'] !== true) {
    header("Location: /");
    exit;
}

$reason = $_SESSION['termination_reason'] ?? 'No reason provided';
$username = $_SESSION['username'] ?? 'User';

// Get termination details if available
$terminationDetails = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.termination_reason, u.terminated_at, 
               a.username as admin_name
        FROM users u 
        LEFT JOIN users a ON u.terminated_by = a.user_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $terminationDetails = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching termination details: " . $e->getMessage());
}

$terminationDate = isset($terminationDetails['terminated_at']) ? 
    date("F j, Y", strtotime($terminationDetails['terminated_at'])) : 
    'Unknown date';

$adminName = $terminationDetails['admin_name'] ?? 'matSFX administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <title>Account Terminated - matSFX</title>
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
 
    <style>
        .termination-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .termination-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-top: 3rem;
            text-align: center;
        }
        
        .termination-icon {
            font-size: 4rem;
            color: #FF4B4B;
            margin-bottom: 1.5rem;
        }
        
        .termination-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #FF4B4B;
        }
        
        .termination-message {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        .termination-details {
            background-color: rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: left;
            margin: 1.5rem 0;
        }
        
        .termination-details p {
            margin: 0.75rem 0;
        }
        
        .termination-reason {
            margin-top: 1rem;
            padding: 1rem;
            background-color: rgba(255, 75, 75, 0.1);
            border-radius: var(--border-radius);
            border-left: 4px solid #FF4B4B;
        }
        
        .appeal-button {
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
            margin-top: 1.5rem;
            text-decoration: none;
        }
        
        .appeal-button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .logout-link {
            display: block;
            margin-top: 1.5rem;
            color: var(--gray-text);
            text-decoration: none;
        }
        
        .logout-link:hover {
            color: var(--light-text);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .termination-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="termination-container">
        <div class="termination-card">
            <div class="termination-icon">
                <i class="fas fa-ban"></i>
            </div>
            
            <h1 class="termination-title">Account Terminated</h1>
            
            <div class="termination-message">
                <p>Hello <?php echo htmlspecialchars($username); ?>,</p>
                <p>Your account has been terminated by a matSFX administrator due to a violation of our community guidelines or terms of service.</p>
            </div>
            
            <div class="termination-details">
                <p><strong>Account:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Terminated on:</strong> <?php echo htmlspecialchars($terminationDate); ?></p>
                <p><strong>Terminated by:</strong> <?php echo htmlspecialchars($adminName); ?></p>
                
                <div class="termination-reason">
                    <p><strong>Reason for termination:</strong></p>
                    <p><?php echo htmlspecialchars($reason); ?></p>
                </div>
            </div>
            
            <p>If you believe this decision was made in error, you may submit an appeal for review.</p>
            
            <a href="appeal.php" class="appeal-button">Submit an Appeal</a>
            
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </div>
</body>
</html>