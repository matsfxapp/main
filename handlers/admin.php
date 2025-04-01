<?php
session_start();
require_once 'config/config.php';

// First define the helper functions and classes
function isAdmin($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['is_admin'] == 1;
}

// Log admin actions
function logAdminAction($adminId, $action, $targetId = null, $details = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, details, action_time) 
            VALUES (:admin_id, :action, :target_id, :details, NOW())
        ");
        return $stmt->execute([
            ':admin_id' => $adminId,
            ':action' => $action,
            ':target_id' => $targetId,
            ':details' => $details
        ]);
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
        return false;
    }
}
// This will get all Account which Marked for Deletion = 1
function getMarkedForDeletionUsers() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                user_id, 
                username, 
                email, 
                profile_picture, 
                created_at, 
                deletion_requested_at
            FROM users 
            WHERE marked_for_deletion = 1
            ORDER BY deletion_requested_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching marked users: " . $e->getMessage());
        return [];
    }
}

// Handle restoring an account from deletion queue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore_from_deletion') {
    $user_id = $_POST['user_id'] ?? null;
    
    if ($user_id) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET 
                    marked_for_deletion = 0, 
                    deletion_requested_at = NULL,
                    is_active = 1
            WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            
            // Log the action
            logAdminAction(
                $_SESSION['user_id'], 
                'restore_from_deletion', 
                $user_id, 
                "Restored account from deletion queue"
            );
            
            echo json_encode(['success' => true, 'message' => 'Account restored successfully']);
        } catch (PDOException $e) {
            error_log("Error restoring account: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to restore account']);
        }
        exit;
    }
}


/**
 * Get all account appeals
 * @param int $limit Maximum number of appeals to retrieve
 * @param int $page Current page number
 * @param string $status Filter by status (pending, approved, rejected, or null for all)
 * @return array Array of appeals with user information
 */
function getAccountAppeals($limit = 10, $page = 1, $status = null) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $whereClause = '';
    $params = [];
    
    if ($status) {
        $whereClause = 'WHERE a.status = :status';
        $params[':status'] = $status;
    }
    
    try {
        // Get total count
        $countQuery = "
            SELECT COUNT(*) FROM account_appeals a
            $whereClause
        ";
        $countStmt = $pdo->prepare($countQuery);
        if ($status) {
            $countStmt->bindValue(':status', $status);
        }
        $countStmt->execute();
        $totalAppeals = $countStmt->fetchColumn();
        
        // Get appeals with user data
        $query = "
            SELECT a.*, u.username, u.email,
                   u.termination_reason, u.terminated_at,
                   admin.username as admin_name
            FROM account_appeals a
            JOIN users u ON a.user_id = u.user_id
            LEFT JOIN users admin ON a.reviewed_by = admin.user_id
            $whereClause
            ORDER BY 
                CASE WHEN a.status = 'pending' THEN 0 ELSE 1 END,
                a.appeal_date DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $appeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'appeals' => $appeals,
            'total' => $totalAppeals,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalAppeals / $limit)
        ];
    } catch (PDOException $e) {
        error_log("Error getting appeals: " . $e->getMessage());
        return [
            'appeals' => [],
            'total' => 0,
            'page' => 1,
            'limit' => $limit,
            'total_pages' => 0,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process an appeal (approve or reject)
 * @param int $appealId Appeal ID
 * @param string $status New status (approved or rejected)
 * @param string $response Admin response message
 * @param int $adminId Admin user ID
 * @return array Result with success status and message
 */
function processAppeal($appealId, $status, $response, $adminId) {
    global $pdo;
    
    if (!in_array($status, ['approved', 'rejected'])) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get appeal data
        $appealStmt = $pdo->prepare("
            SELECT a.user_id, u.email, u.username 
            FROM account_appeals a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.appeal_id = :appeal_id
        ");
        $appealStmt->execute([':appeal_id' => $appealId]);
        $appeal = $appealStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appeal) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Appeal not found'];
        }
        
        // Update appeal
        $updateStmt = $pdo->prepare("
            UPDATE account_appeals 
            SET status = :status, 
                admin_response = :response, 
                response_date = NOW(), 
                reviewed_by = :admin_id 
            WHERE appeal_id = :appeal_id
        ");
        
        $updateStmt->execute([
            ':status' => $status,
            ':response' => $response,
            ':admin_id' => $adminId,
            ':appeal_id' => $appealId
        ]);
        
        // If approved, reactivate the account
        if ($status === 'approved') {
            $reactivateStmt = $pdo->prepare("
                UPDATE users 
                SET is_active = 1,
                    is_terminated = NULL,
                    termination_reason = NULL,
                    terminated_at = NULL,
                    terminated_by = NULL
                WHERE user_id = :user_id
            ");
            
            $reactivateStmt->execute([':user_id' => $appeal['user_id']]);
        }
        
        // Log admin action
        logAdminAction(
            $adminId,
            $status === 'approved' ? 'approve_appeal' : 'reject_appeal',
            $appeal['user_id'],
            "Appeal for {$appeal['username']} was " . 
            ($status === 'approved' ? 'approved' : 'rejected')
        );
        
        // Notify user via email
        sendAppealResponseEmail(
            $appeal['email'],
            $appeal['username'],
            $status,
            $response
        );
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Appeal has been ' . ($status === 'approved' ? 'approved' : 'rejected'),
            'status' => $status
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error processing appeal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Send email notification to user about appeal decision
 * @param string $email User's email
 * @param string $username User's username
 * @param string $status Appeal status (approved or rejected)
 * @param string $response Admin response
 * @return bool Success
 */
function sendAppealResponseEmail($email, $username, $status, $response) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');

        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), 'matSFX');
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = 'Your matSFX Account Appeal - ' . ($status === 'approved' ? 'Approved' : 'Rejected');
        
        $statusText = $status === 'approved' ? 'approved' : 'rejected';
        $statusColor = $status === 'approved' ? '#22c55e' : '#ef4444';
        $actionText = $status === 'approved' ? 
            'Your account has been restored. You can now log in to matSFX.' : 
            'Your account remains terminated.';
            
        $mail->Body = '
        <html>
        <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333333; background-color: #fafafa; padding: 20px;">
            <div style="background-color: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="font-size: 22px; font-weight: 600; color: #222222; margin: 0;">Account Appeal ' . ucfirst($statusText) . '</h2>
                </div>
                
                <div style="line-height: 1.6; font-size: 16px;">
                    <p>Hello ' . htmlspecialchars($username) . ',</p>
                    <p>We have reviewed your appeal regarding your terminated matSFX account.</p>
                    
                    <div style="text-align: center; margin: 25px 0;">
                        <span style="display: inline-block; background-color: ' . $statusColor . '; color: white; padding: 8px 20px; border-radius: 50px; font-weight: 500; font-size: 15px;">' . ucfirst($statusText) . '</span>
                    </div>
                    
                    <p style="font-weight: 600;">' . $actionText . '</p>
                    
                    <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; margin: 25px 0;">
                        <p style="margin-top: 0; margin-bottom: 10px; font-weight: bold;">Admin Response:</p>
                        <div style="background-color: white; border-radius: 6px; padding: 15px; border-left: 3px solid #dddddd;">
                            ' . nl2br(htmlspecialchars($response)) . '
                        </div>
                    </div>
                    
                    ' . ($status === 'approved' ? '
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="https://alpha.matsfx.com/login" style="display: inline-block; background-color: #222222; color: white; text-decoration: none; padding: 12px 30px; border-radius: 50px; font-size: 16px; font-weight: 500;">Log in to matSFX</a>
                    </div>
                    ' : '') . '
                </div>
            </div>
                
            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: #888888;">
                <p>© ' . date('Y') . ' matSFX. All rights reserved.</p>
                <p>This email was sent to ' . $email . '</p>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = "
        Hello $username,
        
        We have reviewed your appeal regarding your terminated matSFX account.
        
        Status: " . ucfirst($statusText) . "
        
        $actionText
        
        Admin Response:
        $response
        
        " . ($status === 'approved' ? "You can log in at: https://alpha.matsfx.com/login" : "") . "
        
        © " . date('Y') . " matSFX. All rights reserved.
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending appeal response email: " . $mail->ErrorInfo);
        return false;
    }
}

class AdminPanel {
    private $pdo;
    private $adminId;
    
    public function __construct($pdo, $adminId) {
        $this->pdo = $pdo;
        $this->adminId = $adminId;
    }
    
    public function getSiteStats() {
        $stats = [
            'user_count' => 0,
            'song_count' => 0,
            'play_count' => 0,
            'recent_users' => [],
            'recent_logs' => []
        ];
        
        try {
            // Get user count
            $userStmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
            if ($userStmt) {
                $stats['user_count'] = $userStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            }
            
            // Get song count
            $songStmt = $this->pdo->query("SELECT COUNT(*) as count FROM songs");
            if ($songStmt) {
                $stats['song_count'] = $songStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            }

            try {
                $checkColumnStmt = $this->pdo->query("SHOW COLUMNS FROM songs LIKE 'play_count'");
                $playCountExists = ($checkColumnStmt && $checkColumnStmt->rowCount() > 0);
                
                if ($playCountExists) {
                    $playStmt = $this->pdo->query("SELECT SUM(play_count) as count FROM songs");
                    if ($playStmt) {
                        $stats['play_count'] = $playStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                    }
                } else {
                    $stats['play_count'] = 0;
                }
            } catch (PDOException $e) {
                error_log("Warning: Couldn't get play count: " . $e->getMessage());
            }
            
            // Get recent users
            try {
                $recentUserQuery = "
                    SELECT user_id, username, created_at, 
                           COALESCE(profile_picture, '/defaults/default-profile.jpg') as profile_picture
                    FROM users
                    ORDER BY created_at DESC
                    LIMIT 5
                ";
                
                $recentUserStmt = $this->pdo->query($recentUserQuery);
                if ($recentUserStmt) {
                    $stats['recent_users'] = $recentUserStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            } catch (PDOException $e) {
                error_log("Warning: Couldn't get recent users: " . $e->getMessage());
                $stats['recent_users'] = [];
            }
            
            // Get admin logs
            try {
                $logQuery = "
                    SELECT l.log_id, l.action, l.target_id, l.details, l.action_time,
                           u.username as admin_name
                    FROM admin_logs l
                    JOIN users u ON l.admin_id = u.user_id
                    ORDER BY l.action_time DESC
                    LIMIT 10
                ";
                
                $logStmt = $this->pdo->query($logQuery);
                if ($logStmt) {
                    $stats['recent_logs'] = $logStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            } catch (PDOException $e) {
                error_log("Warning: Couldn't get admin logs: " . $e->getMessage());
                $stats['recent_logs'] = [];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Admin panel error: " . $e->getMessage());
        }
        
        return $stats;
    }

    public function getAllUsers($page = 1, $limit = 10, $search = null) {
        $offset = ($page - 1) * $limit;
        
        try {
            $params = [];
            $whereClause = "";
            
            if ($search) {
                $whereClause = " WHERE username LIKE :search OR email LIKE :search ";
                $params[':search'] = "%$search%";
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as count FROM users" . $whereClause;
            $countStmt = $this->pdo->prepare($countQuery);
            if ($search) {
                $countStmt->bindParam(':search', $params[':search']);
            }
            $countStmt->execute();
            $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Check if necessary columns exist
            $columns = [
                'is_admin' => true,
                'is_verified' => true,
                'is_active' => false,
                'follower_count' => false
            ];
            
            foreach ($columns as $column => &$exists) {
                try {
                    $checkCol = $this->pdo->query("
                        SELECT {$column} FROM users LIMIT 1
                    ");
                    $exists = ($checkCol !== false);
                } catch (PDOException $e) {
                    $exists = false;
                }
            }
            
            // Build dynamic column selection
            $selectCols = "u.user_id, u.username, u.email, u.created_at, u.profile_picture";
            foreach ($columns as $column => $exists) {
                if ($exists) {
                    $selectCols .= ", u.{$column}";
                } else {
                    $selectCols .= ", 0 as {$column}";
                }
            }
            
            // Get paginated users
            $query = "
                SELECT {$selectCols},
                       COUNT(DISTINCT s.song_id) as song_count,
                       COUNT(DISTINCT l.user_id) as likes_count
                FROM users u
                LEFT JOIN songs s ON u.user_id = s.uploaded_by
                LEFT JOIN likes l ON u.user_id = l.user_id
                $whereClause
                GROUP BY u.user_id
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if ($search) {
                $stmt->bindParam(':search', $params[':search']);
            }
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'users' => $users,
                'total' => $totalUsers,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalUsers / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Error getting users: " . $e->getMessage());
            return ['error' => 'Failed to load users', 'users' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }
    }
    
    public function getUserData($userId) {
        try {
            // First get basic user data
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return ['error' => 'User not found'];
            }
            
            // Set default values for stats
            $stats = [
                'song_count' => 0,
                'likes_given' => 0,
                'likes_received' => 0,
                'followers' => 0,
                'following' => 0
            ];
            
            // Get song count
            try {
                $songStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count FROM songs WHERE uploaded_by = :user_id
                ");
                $songStmt->execute(['user_id' => $userId]);
                $stats['song_count'] = $songStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error getting song count: " . $e->getMessage());
            }
            
            // Get likes given
            try {
                $likesStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count FROM likes WHERE user_id = :user_id
                ");
                $likesStmt->execute(['user_id' => $userId]);
                $stats['likes_given'] = $likesStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error getting likes given: " . $e->getMessage());
            }
            
            // Get likes received
            try {
                $likesReceivedStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM likes l
                    JOIN songs s ON l.song_id = s.song_id
                    WHERE s.uploaded_by = :user_id
                ");
                $likesReceivedStmt->execute(['user_id' => $userId]);
                $stats['likes_received'] = $likesReceivedStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error getting likes received: " . $e->getMessage());
            }
            
            // Get followers count - check if followers table exists first
            try {
                $checkFollowersTable = $this->pdo->query("SELECT 1 FROM followers LIMIT 1");
                if ($checkFollowersTable !== false) {
                    $followersStmt = $this->pdo->prepare("
                        SELECT COUNT(*) as count FROM followers WHERE followed_id = :user_id
                    ");
                    $followersStmt->execute(['user_id' => $userId]);
                    $stats['followers'] = $followersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                    
                    $followingStmt = $this->pdo->prepare("
                        SELECT COUNT(*) as count FROM followers WHERE follower_id = :user_id
                    ");
                    $followingStmt->execute(['user_id' => $userId]);
                    $stats['following'] = $followingStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                } else if (isset($userData['follower_count'])) {
                    $stats['followers'] = $userData['follower_count'];
                }
            } catch (PDOException $e) {
                error_log("Error getting followers count: " . $e->getMessage());
                // Check if follower_count column exists
                if (isset($userData['follower_count'])) {
                    $stats['followers'] = $userData['follower_count'];
                }
            }
            
            // Get badge info
            $badges = [];
            try {
                $badgeStmt = $this->pdo->prepare("
                    SELECT b.badge_name, b.image_path
                    FROM user_badges ub
                    JOIN badges b ON ub.badge_id = b.badge_id
                    WHERE ub.user_id = :user_id
                ");
                $badgeStmt->execute(['user_id' => $userId]);
                $badges = $badgeStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting badges: " . $e->getMessage());
            }
            
            // Get user songs
            $songs = [];
            try {
                $songStmt = $this->pdo->prepare("
                    SELECT song_id, title, artist, album, upload_date, play_count
                    FROM songs
                    WHERE uploaded_by = :user_id
                    ORDER BY upload_date DESC
                    LIMIT 10
                ");
                $songStmt->execute(['user_id' => $userId]);
                $songs = $songStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting songs: " . $e->getMessage());
            }
            
            // Get recent activity
            $activities = [];
            try {
                // Check for necessary tables first
                $tables = [
                    'songs' => false,
                    'likes' => false
                ];
                
                foreach ($tables as $table => &$exists) {
                    try {
                        $this->pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                        $exists = true;
                    } catch (PDOException $e) {
                        $exists = false;
                    }
                }
                
                $parts = [];
                $unionParams = [];
                
                if ($tables['songs']) {
                    $parts[] = "(SELECT 'upload' as action_type, s.title as item, s.upload_date as action_date
                             FROM songs s
                             WHERE s.uploaded_by = :user_id_1)";
                    $unionParams[':user_id_1'] = $userId;
                }
                
                if ($tables['songs'] && $tables['likes']) {
                    $parts[] = "(SELECT 'like' as action_type, s.title as item, l.like_date as action_date
                             FROM likes l
                             JOIN songs s ON l.song_id = s.song_id
                             WHERE l.user_id = :user_id_2)";
                    $unionParams[':user_id_2'] = $userId;
                }
                
                if (!empty($parts)) {
                    $query = implode(" UNION ALL ", $parts) . " ORDER BY action_date DESC LIMIT 10";
                    $activityStmt = $this->pdo->prepare($query);
                    foreach ($unionParams as $param => $value) {
                        $activityStmt->bindValue($param, $value);
                    }
                    
                    $activityStmt->execute();
                    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                error_log("Error getting activities: " . $e->getMessage());
            }
            
            // Merge user data and stats
            $userData = array_merge($userData, $stats);
            
            return [
                'user' => $userData,
                'badges' => $badges,
                'songs' => $songs,
                'activities' => $activities
            ];
        } catch (PDOException $e) {
            error_log("Error getting user data: " . $e->getMessage());
            return ['error' => 'Failed to load user data: ' . $e->getMessage()];
        }
    }
    
    public function terminateAccount($userId, $reason) {
        try {
            // Check if termination columns exist
            $hasTerminationCols = false;
            try {
                $this->pdo->query("
                    SELECT is_active, is_terminated, termination_reason, terminated_at, terminated_by
                    FROM users
                    LIMIT 1
                ");
                $hasTerminationCols = true;
            } catch (PDOException $e) {
                $hasTerminationCols = false;
            }
    
            if (!$hasTerminationCols) {
                return [
                    'success' => false,
                    'message' => 'Database schema is missing termination columns. Please run the database updates first.'
                ];
            }
    
            // Start transaction
            $this->pdo->beginTransaction();
    
            // First get the user data for logging
            $userStmt = $this->pdo->prepare("SELECT username, email FROM users WHERE user_id = :user_id");
            $userStmt->execute(['user_id' => $userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$userData) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'User not found'];
            }
    
            // Update user status to inactive
            $updateStmt = $this->pdo->prepare("
                UPDATE users
                SET is_active = 0,
                    is_terminated = 1,
                    termination_reason = :reason,
                    terminated_at = NOW(),
                    terminated_by = :admin_id
                WHERE user_id = :user_id
            ");
            $updateStmt->execute([
                'reason' => $reason,
                'admin_id' => $this->adminId,
                'user_id' => $userId
            ]);
    
            // Log the action
            logAdminAction(
                $this->adminId,
                'terminate_account',
                $userId,
                "Terminated user {$userData['username']} ({$userData['email']}) - Reason: $reason"
            );
    
            // Commit the transaction
            $this->pdo->commit();
    
            return [
                'success' => true,
                'message' => "User {$userData['username']} has been terminated"
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error terminating account: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function restoreAccount($userId) {
        try {
            // Check if termination columns exist
            $hasTerminationCols = false;
            try {
                $this->pdo->query("
                    SELECT is_active, is_terminated FROM users LIMIT 1
                ");
                $hasTerminationCols = true;
            } catch (PDOException $e) {
                $hasTerminationCols = false;
            }
    
            if (!$hasTerminationCols) {
                return [
                    'success' => false,
                    'message' => 'Database schema is missing termination columns. Please run the database updates first.'
                ];
            }
    
            // Start transaction
            $this->pdo->beginTransaction();
    
            // First get the user data for logging
            $userStmt = $this->pdo->prepare("SELECT username, email FROM users WHERE user_id = :user_id");
            $userStmt->execute(['user_id' => $userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$userData) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'User not found'];
            }
    
            // Update user status to active
            $updateStmt = $this->pdo->prepare("
                UPDATE users
                SET is_active = 1,
                    is_terminated = NULL,
                    termination_reason = NULL,
                    terminated_at = NULL,
                    terminated_by = NULL
                WHERE user_id = :user_id
            ");
            $updateStmt->execute(['user_id' => $userId]);
    
            // Log the action
            logAdminAction(
                $this->adminId,
                'restore_account',
                $userId,
                "Restored user {$userData['username']} ({$userData['email']})"
            );
    
            // Commit the transaction
            $this->pdo->commit();
    
            return [
                'success' => true,
                'message' => "User {$userData['username']} has been restored"
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error restoring account: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function toggleAdminStatus($userId) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // First get the user data for logging
            $userStmt = $this->pdo->prepare("SELECT username, email, is_admin FROM users WHERE user_id = :user_id");
            $userStmt->execute(['user_id' => $userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Toggle admin status
            $newStatus = $userData['is_admin'] ? 0 : 1;
            $action = $newStatus ? 'promote_to_admin' : 'demote_from_admin';
            
            $updateStmt = $this->pdo->prepare("
                UPDATE users 
                SET is_admin = :status
                WHERE user_id = :user_id
            ");
            $updateStmt->execute([
                'status' => $newStatus,
                'user_id' => $userId
            ]);
            
            // Check if badges table exists
            $hasBadges = false;
            try {
                $this->pdo->query("SELECT 1 FROM badges LIMIT 1");
                $hasBadges = true;
            } catch (PDOException $e) {
                $hasBadges = false;
            }
            
            if ($hasBadges) {
                // Update badge if needed
                if ($newStatus) {
                    // Check if admin badge exists
                    $badgeStmt = $this->pdo->prepare("
                        SELECT badge_id FROM badges WHERE badge_name = 'Admin'
                    ");
                    $badgeStmt->execute();
                    $badge = $badgeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($badge) {
                        // Add the badge if they don't have it
                        $checkStmt = $this->pdo->prepare("
                            SELECT 1 FROM user_badges 
                            WHERE user_id = :user_id AND badge_id = :badge_id
                        ");
                        $checkStmt->execute([
                            'user_id' => $userId,
                            'badge_id' => $badge['badge_id']
                        ]);
                        
                        if (!$checkStmt->fetch()) {
                            $badgeInsert = $this->pdo->prepare("
                                INSERT INTO user_badges (user_id, badge_id, assigned_by)
                                VALUES (:user_id, :badge_id, :admin_id)
                            ");
                            $badgeInsert->execute([
                                'user_id' => $userId,
                                'badge_id' => $badge['badge_id'],
                                'admin_id' => $this->adminId
                            ]);
                        }
                    }
                } else {
                    // Check if admin badge exists and remove it
                    $badgeStmt = $this->pdo->prepare("
                        SELECT b.badge_id 
                        FROM badges b
                        WHERE b.badge_name = 'Admin'
                    ");
                    $badgeStmt->execute();
                    $badge = $badgeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($badge) {
                        $removeBadge = $this->pdo->prepare("
                            DELETE FROM user_badges
                            WHERE user_id = :user_id AND badge_id = :badge_id
                        ");
                        $removeBadge->execute([
                            'user_id' => $userId,
                            'badge_id' => $badge['badge_id']
                        ]);
                    }
                }
            }
            
            // Log the action
            $actionDesc = $newStatus ? 
                "Promoted user {$userData['username']} to admin" : 
                "Demoted user {$userData['username']} from admin";
            
            logAdminAction(
                $this->adminId,
                $action,
                $userId,
                $actionDesc
            );
            
            // Commit the transaction
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => $actionDesc,
                'new_status' => $newStatus
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error toggling admin status: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function toggleVerification($userId) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // First get the user data for logging
            $userStmt = $this->pdo->prepare("SELECT username, email, is_verified FROM users WHERE user_id = :user_id");
            $userStmt->execute(['user_id' => $userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Toggle verified status
            $newStatus = $userData['is_verified'] ? 0 : 1;
            $action = $newStatus ? 'verify_user' : 'unverify_user';
            
            $updateStmt = $this->pdo->prepare("
                UPDATE users 
                SET is_verified = :status
                WHERE user_id = :user_id
            ");
            $updateStmt->execute([
                'status' => $newStatus,
                'user_id' => $userId
            ]);
            
            // Check if badges table exists
            $hasBadges = false;
            try {
                $this->pdo->query("SELECT 1 FROM badges LIMIT 1");
                $hasBadges = true;
            } catch (PDOException $e) {
                $hasBadges = false;
            }
            
            if ($hasBadges) {
                // Update badge if needed
                if ($newStatus) {
                    // Check if verified badge exists
                    $badgeStmt = $this->pdo->prepare("
                        SELECT badge_id FROM badges WHERE badge_name = 'Verified'
                    ");
                    $badgeStmt->execute();
                    $badge = $badgeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($badge) {
                        // Add the badge if they don't have it
                        $checkStmt = $this->pdo->prepare("
                            SELECT 1 FROM user_badges 
                            WHERE user_id = :user_id AND badge_id = :badge_id
                        ");
                        $checkStmt->execute([
                            'user_id' => $userId,
                            'badge_id' => $badge['badge_id']
                        ]);
                        
                        if (!$checkStmt->fetch()) {
                            $badgeInsert = $this->pdo->prepare("
                                INSERT INTO user_badges (user_id, badge_id, assigned_by)
                                VALUES (:user_id, :badge_id, :admin_id)
                            ");
                            $badgeInsert->execute([
                                'user_id' => $userId,
                                'badge_id' => $badge['badge_id'],
                                'admin_id' => $this->adminId
                            ]);
                        }
                    }
                } else {
                    // Check if verified badge exists and remove it
                    $badgeStmt = $this->pdo->prepare("
                        SELECT b.badge_id 
                        FROM badges b
                        WHERE b.badge_name = 'Verified'
                    ");
                    $badgeStmt->execute();
                    $badge = $badgeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($badge) {
                        $removeBadge = $this->pdo->prepare("
                            DELETE FROM user_badges
                            WHERE user_id = :user_id AND badge_id = :badge_id
                        ");
                        $removeBadge->execute([
                            'user_id' => $userId,
                            'badge_id' => $badge['badge_id']
                        ]);
                    }
                }
            }
            
            // Log the action
            $actionDesc = $newStatus ? 
                "Verified user {$userData['username']}" : 
                "Removed verification from user {$userData['username']}";
            
            logAdminAction(
                $this->adminId,
                $action,
                $userId,
                $actionDesc
            );
            
            // Commit the transaction
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => $actionDesc,
                'new_status' => $newStatus
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error toggling verification status: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

class BadgeManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllBadges() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM badges ORDER BY badge_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting badges: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUsersWithBadges() {
        try {
            $query = "SELECT u.user_id, u.username, GROUP_CONCAT(b.badge_name) as badges
                    FROM users u
                    LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                    LEFT JOIN badges b ON ub.badge_id = b.badge_id
                    GROUP BY u.user_id, u.username
                    ORDER BY u.username";
            
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting users with badges: " . $e->getMessage());
            return [];
        }
    }
    
    public function assignBadge($userId, $badgeId, $adminId) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO user_badges (user_id, badge_id, assigned_by) 
                 VALUES (:user_id, :badge_id, :assigned_by)"
            );
            $success = $stmt->execute([
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'assigned_by' => $adminId
            ]);
            
            if ($success) {
                // Get badge and user info for logging
                $infoStmt = $this->pdo->prepare("
                    SELECT b.badge_name, u.username 
                    FROM badges b, users u 
                    WHERE b.badge_id = :badge_id AND u.user_id = :user_id
                ");
                $infoStmt->execute([
                    'badge_id' => $badgeId,
                    'user_id' => $userId
                ]);
                $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
                
                // Log the action
                logAdminAction(
                    $adminId,
                    'assign_badge',
                    $userId,
                    "Assigned badge '{$info['badge_name']}' to user {$info['username']}"
                );
            }
            
            return $success;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            error_log("Error assigning badge: " . $e->getMessage());
            return false;
        }
    }

    public function removeBadge($userId, $badgeId, $adminId) {
        try {
            // Get badge and user info for logging before removal
            $infoStmt = $this->pdo->prepare("
                SELECT b.badge_name, u.username 
                FROM badges b, users u, user_badges ub
                WHERE b.badge_id = :badge_id 
                AND u.user_id = :user_id
                AND ub.user_id = u.user_id
                AND ub.badge_id = b.badge_id
            ");
            $infoStmt->execute([
                'badge_id' => $badgeId,
                'user_id' => $userId
            ]);
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            
            // Remove the badge
            $stmt = $this->pdo->prepare(
                "DELETE FROM user_badges 
                 WHERE user_id = :user_id AND badge_id = :badge_id"
            );
            $success = $stmt->execute([
                'user_id' => $userId,
                'badge_id' => $badgeId
            ]);
            
            if ($success && $info) {
                // Log the action
                logAdminAction(
                    $adminId,
                    'remove_badge',
                    $userId,
                    "Removed badge '{$info['badge_name']}' from user {$info['username']}"
                );
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Error removing badge: " . $e->getMessage());
            return false;
        }
    }
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Initialize services
$adminPanel = new AdminPanel($pdo, $_SESSION['user_id']);
$badgeManager = new BadgeManager($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'terminate_account':
                    $result = $adminPanel->terminateAccount(
                        $_POST['user_id'],
                        $_POST['reason'] ?? 'No reason provided'
                    );
                    $response = $result;
                    break;

                case 'process_appeal':
                    // Process an appeal (approve or reject)
                    if (!isset($_POST['appeal_id'], $_POST['status'], $_POST['response'])) {
                        $response['error'] = 'Missing required parameters';
                        break;
                    }
                    
                    $appealId = intval($_POST['appeal_id']);
                    $status = $_POST['status'];
                    $adminResponse = sanitizeInput($_POST['response']);
                    
                    $result = processAppeal($appealId, $status, $adminResponse, $_SESSION['user_id']);
                    $response = $result;
                    break;
                    
                case 'restore_account':
                    $result = $adminPanel->restoreAccount($_POST['user_id']);
                    $response = $result;
                    break;
                    
                case 'toggle_admin':
                    $result = $adminPanel->toggleAdminStatus($_POST['user_id']);
                    $response = $result;
                    break;
                    
                case 'toggle_verification':
                    $result = $adminPanel->toggleVerification($_POST['user_id']);
                    $response = $result;
                    break;
                    
                case 'get_user_data':
                    $result = $adminPanel->getUserData($_POST['user_id']);
                    $response = $result;
                    break;
                    
                default:
                    $response['error'] = 'Unknown action';
            }
        } elseif (isset($_POST['assign_badge'])) {
            // For backwards compatibility
            $success = $badgeManager->assignBadge(
                $_POST['user_id'],
                $_POST['badge_id'],
                $_SESSION['user_id']
            );
            
            if ($success) {
                $stmt = $pdo->prepare(
                    "SELECT GROUP_CONCAT(b.badge_name) as badges
                     FROM users u
                     LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                     LEFT JOIN badges b ON ub.badge_id = b.badge_id
                     WHERE u.user_id = :user_id
                     GROUP BY u.user_id"
                );
                $stmt->execute(['user_id' => $_POST['user_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $response = [
                    'success' => true,
                    'badges' => $result['badges'] ?? ''
                ];
            }
        } elseif (isset($_POST['remove_badge'])) {
            $success = $badgeManager->removeBadge(
                $_POST['user_id'],
                $_POST['badge_id'],
                $_SESSION['user_id']
            );
            
            if ($success) {
                $stmt = $pdo->prepare(
                    "SELECT GROUP_CONCAT(b.badge_name) as badges
                     FROM users u
                     LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                     LEFT JOIN badges b ON ub.badge_id = b.badge_id
                     WHERE u.user_id = :user_id
                     GROUP BY u.user_id"
                );
                $stmt->execute(['user_id' => $_POST['user_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $response = [
                    'success' => true,
                    'badges' => $result['badges'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        $response['error'] = 'Server error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Get site statistics
$siteStats = $adminPanel->getSiteStats();

// Get user list (paginated)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$userList = $adminPanel->getAllUsers($page, 20, $search);

// Get all badges for badge management
$allBadges = $badgeManager->getAllBadges();
$usersWithBadges = $badgeManager->getUsersWithBadges();

// Determine which view to show
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$validViews = ['dashboard', 'users', 'badges', 'user-detail', 'appeals', 'marked-for-deletion'];
if (!in_array($view, $validViews)) {
    $view = 'dashboard';
}

// Get user detail if needed
$userDetail = null;
if ($view === 'user-detail' && isset($_GET['user_id'])) {
    $userDetail = $adminPanel->getUserData($_GET['user_id']);
}