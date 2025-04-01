<?php
/**
 * Middleware to check if user account is terminated
 * Checks both session variables and performs a fresh database check
 */
function checkTerminatedAccount() {
    global $pdo;
    
    // No need to check if not logged in
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    // First check session flag (for performance)
    $isTerminated = isset($_SESSION['is_terminated']) && $_SESSION['is_terminated'] === true;
    
    // If not terminated according to session, verify from database (for accuracy)
    if (!$isTerminated) {
        try {
            $stmt = $pdo->prepare("
                SELECT is_active, is_terminated, termination_reason, terminated_at, terminated_by 
                FROM users WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check both is_active=0 and is_terminated=1
            if ($userData && ((isset($userData['is_active']) && $userData['is_active'] == 0) || 
                              (isset($userData['is_terminated']) && $userData['is_terminated'] == 1))) {
                $isTerminated = true;
                
                // Update session with latest termination data
                $_SESSION['is_terminated'] = true;
                $_SESSION['termination_reason'] = $userData['termination_reason'] ?? 'No reason provided';
                $_SESSION['terminated_at'] = $userData['terminated_at'] ?? null;
                $_SESSION['terminated_by'] = $userData['terminated_by'] ?? null;
            }
        } catch (PDOException $e) {
            error_log("Error checking termination status: " . $e->getMessage());
        }
    }
    
    // If terminated, restrict access to allowed pages
    if ($isTerminated) {
        $allowedScripts = ['terminated.php', 'logout.php', 'appeal.php'];
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        
        if (!in_array($currentScript, $allowedScripts)) {
            header("Location: /terminated.php");
            exit;
        }
    }
}