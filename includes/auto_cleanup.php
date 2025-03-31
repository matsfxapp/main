<?php
// This function will automatically process account deletions
function processAccountDeletions($pdo) {
    try {
        // Find accounts marked for deletion more than 7 days ago
        $stmt = $pdo->prepare("
            SELECT user_id 
            FROM users 
            WHERE marked_for_deletion = 1 
            AND deletion_requested_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $usersToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usersToDelete as $user) {
            $user_id = $user['user_id'];
            
            try {
                $pdo->beginTransaction();
                
                // Anonymize the user data
                $anonymizeStmt = $pdo->prepare("
                    UPDATE users 
                    SET 
                        email = CONCAT('deleted_', user_id, '_', SHA2(email, 256)),
                        username = CONCAT('DeletedUser', user_id),
                        profile_picture = '/defaults/default-profile.jpg',
                        profile_banner = '/defaults/default-banner.jpg',
                        bio = 'Account deleted',
                        is_verified = 0,
                        email_verified = 0,
                        is_active = 0
                    WHERE user_id = ?
                ");
                $anonymizeStmt->execute([$user_id]);
                
                // Process related data (likes, playlists, etc.) if needed
                
                $pdo->commit();
                error_log("Auto-processed deletion for user_id: $user_id");
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error auto-processing deletion for user_id: $user_id - " . $e->getMessage());
            }
        }
        
        return count($usersToDelete);
    } catch (Exception $e) {
        error_log("Auto cleanup error: " . $e->getMessage());
        return 0;
    }
}

// Check if we should run cleanup (throttle to avoid performance issues)
function shouldRunCleanup() {
    $lastRun = isset($_SESSION['last_cleanup_run']) ? $_SESSION['last_cleanup_run'] : 0;
    $currentTime = time();
    
    // Only run once per hour at most
    if ($currentTime - $lastRun > 3600) {
        $_SESSION['last_cleanup_run'] = $currentTime;
        return true;
    }
    
    return false;
}