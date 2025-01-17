<?php
session_start();
require_once 'config/config.php';

function isAdmin($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['is_admin'] == 1;
}

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

class BadgeManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllBadges() {
        $stmt = $this->pdo->query("SELECT * FROM badges ORDER BY badge_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUsersWithBadges() {
        $query = "SELECT u.user_id, u.username, GROUP_CONCAT(b.badge_name) as badges
                 FROM users u
                 LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                 LEFT JOIN badges b ON ub.badge_id = b.badge_id
                 GROUP BY u.user_id, u.username
                 ORDER BY u.username";
        
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function assignBadge($userId, $badgeId, $adminId) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO user_badges (user_id, badge_id, assigned_by) 
                 VALUES (:user_id, :badge_id, :assigned_by)"
            );
            return $stmt->execute([
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'assigned_by' => $adminId
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function removeBadge($userId, $badgeId) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM user_badges 
             WHERE user_id = :user_id AND badge_id = :badge_id"
        );
        return $stmt->execute([
            'user_id' => $userId,
            'badge_id' => $badgeId
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $badgeManager = new BadgeManager($pdo);
    $response = ['success' => false];
    
    try {
        if (isset($_POST['assign_badge'])) {
            $success = $badgeManager->assignBadge(
                $_POST['user_id'],
                $_POST['badge_id'],
                $_SESSION['user_id']
            );
        } elseif (isset($_POST['remove_badge'])) {
            $success = $badgeManager->removeBadge(
                $_POST['user_id'],
                $_POST['badge_id']
            );
        }
        
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
    } catch (Exception $e) {
        $response['error'] = 'Operation failed';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$badgeManager = new BadgeManager($pdo);
$allBadges = $badgeManager->getAllBadges();
$users = $badgeManager->getUsersWithBadges();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Badge Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --primary-light: rgba(45, 127, 249, 0.1);
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --darker-bg: #060912;
            --card-bg: #111827;
            --card-hover: #1F2937;
            --nav-bg: rgba(17, 24, 39, 0.95);
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-color: #1F2937;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            color: var(--light-text);
            margin: 0;
            padding: 20px;
        }

        .badge-management {
            max-width: 1200px;
            margin: 10vh auto 0;
            padding: 20px;
            background: var(--darker-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
        }

        .user-item {
            background: var(--card-bg);
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .user-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: var(--card-hover);
        }

        .badge-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .badge-form {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            min-width: 250px;
        }

        select {
            flex: 1;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            background: var(--dark-bg);
            color: var(--light-text);
            font-size: 0.9rem;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        button {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--border-radius);
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        button:hover {
            background: var(--primary-hover);
        }

        button[name="remove_badge"] {
            background: transparent;
            border: 1px solid var(--border-color);
        }

        button[name="remove_badge"]:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .badge-list {
            margin: 0.75rem 0;
            color: var(--gray-text);
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .badge-actions {
                flex-direction: column;
            }

            .badge-form {
                min-width: 100%;
            }

            button {
                width: 100%;
            }
        }

        .badge-update-animation {
            animation: updateFlash 1s ease;
        }

        @keyframes updateFlash {
            0% { background-color: var(--primary-light); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="badge-management">
        <h2>Badge Management</h2>
        
        <div class="user-list">
            <?php foreach ($users as $user): ?>
            <div class="user-item" data-user-id="<?php echo $user['user_id']; ?>">
                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                <div class="badge-list" id="badge-list-<?php echo $user['user_id']; ?>">
                    Current badges: <?php echo htmlspecialchars($user['badges'] ?? 'None'); ?>
                </div>
                
                <div class="badge-actions">
                    <form class="badge-form">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <select name="badge_id">
                            <?php foreach ($allBadges as $badge): ?>
                            <option value="<?php echo $badge['badge_id']; ?>">
                                <?php echo htmlspecialchars($badge['badge_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_badge">Assign</button>
                    </form>
                    
                    <form class="badge-form">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <select name="badge_id">
                            <?php foreach ($allBadges as $badge): ?>
                            <option value="<?php echo $badge['badge_id']; ?>">
                                <?php echo htmlspecialchars($badge['badge_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="remove_badge">Remove</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.querySelectorAll('.badge-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const userId = form.querySelector('[name="user_id"]').value;
            const badgeId = form.querySelector('[name="badge_id"]').value;
            const action = form.querySelector('[name="assign_badge"]') ? 'assign_badge' : 'remove_badge';
            const badgeList = document.getElementById(`badge-list-${userId}`);

            // Add loading state
            form.classList.add('loading');

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('user_id', userId);
                formData.append('badge_id', badgeId);
                formData.append(action, '1');

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    badgeList.textContent = `Current badges: ${result.badges || 'None'}`;
                    badgeList.classList.add('badge-update-animation');
                    setTimeout(() => {
                        badgeList.classList.remove('badge-update-animation');
                    }, 1000);
                } else {
                    throw new Error(result.error || 'Operation failed');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                form.classList.remove('loading');
            }
        });
    });
    </script>
</body>
</html>