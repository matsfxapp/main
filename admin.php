<?php
require 'handlers/admin.php';
require 'config/config.php'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - matSFX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/adminMobile.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="app-logo">
                    <img src="/app_logos/matsfx_logo.png" alt="matSFX Logo">
                    <span>matSFX</span>
                </div>
                <div class="admin-label">Logged in as Admin</div>
                <div class="admin-info">
                    <img src="<?php echo isset($_SESSION['profile_picture']) ? htmlspecialchars($_SESSION['profile_picture']) : '/defaults/default-profile.jpg'; ?>" alt="Admin" class="admin-avatar">
                    <div class="admin-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="?view=dashboard" class="nav-link <?php echo $view === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?view=users" class="nav-link <?php echo $view === 'users' || $view === 'user-detail' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?view=badges" class="nav-link <?php echo $view === 'badges' ? 'active' : ''; ?>">
                        <i class="fas fa-award"></i>
                        <span>Badge Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?view=appeals" class="nav-link <?php echo $view === 'appeals' ? 'active' : ''; ?>">
                        <i class="fas fa-gavel"></i>
                        <span>Appeals</span>
                        <?php
                        // Show count of pending appeals
                        $pendingAppealCount = 0;
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM account_appeals WHERE status = 'pending'");
                            $pendingAppealCount = $stmt->fetchColumn();
                        } catch (PDOException $e) {
                            // Table might not exist yet
                        }
                        
                        if ($pendingAppealCount > 0): 
                        ?>
                        <span class="badge badge-warning"><?php echo $pendingAppealCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?view=marked-for-deletion" class="nav-link <?php echo $view === 'marked-for-deletion' ? 'active' : ''; ?>">
                        <i class="fas fa-trash-restore"></i>
                        <span>Deletion Queue</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Back to Site</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php if ($view === 'dashboard'): ?>
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <div class="page-actions">
                        <a href="?view=users" class="btn btn-primary">
                            <i class="fas fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($siteStats['user_count']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-green">
                            <i class="fas fa-music"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($siteStats['song_count']); ?></div>
                        <div class="stat-label">Total Songs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-yellow">
                            <i class="fas fa-play"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($siteStats['play_count']); ?></div>
                        <div class="stat-label">Total Plays</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Recent Users</div>
                    <div class="card-body">
                        <?php if (empty($siteStats['recent_users'])): ?>
                            <div style="text-align: center; padding: 20px;">
                                <p>No recent users found.</p>
                            </div>
                        <?php else: ?>
                            <div class="recent-users">
                                <?php foreach ($siteStats['recent_users'] as $user): ?>
                                    <div class="user-card">
                                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                            alt="User" class="user-avatar" 
                                            onerror="this.src='/defaults/default-profile.jpg'">
                                        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <div class="user-joined">Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                        <a href="?view=user-detail&user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline" style="margin-top: 1rem;">View Profile</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Recent Admin Actions</div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($siteStats['recent_logs'])): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center;">No admin actions logged yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($siteStats['recent_logs'] as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        $actionLabel = str_replace('_', ' ', $log['action']);
                                                        echo ucwords($actionLabel);
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($log['action_time'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($view === 'users'): ?>
                <div class="page-header">
                    <h1 class="page-title">User Management</h1>
                    <form action="" method="GET" class="search-form">
                        <input type="hidden" name="view" value="users">
                        <input type="text" name="search" placeholder="Search users..." class="search-input" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        All Users
                        <?php if ($search): ?>
                            <span>(Search results for "<?php echo htmlspecialchars($search); ?>")</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Joined</th>
                                        <th>Songs</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($userList['users'])): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                                No users found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($userList['users'] as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/defaults/default-profile.jpg'); ?>" alt="User">
                                                        <div class="user-cell-info">
                                                            <div class="cell-main"><?php echo htmlspecialchars($user['username']); ?></div>
                                                            <div class="cell-secondary"><?php echo htmlspecialchars($user['email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td><?php echo $user['song_count']; ?></td>
                                                <td>
                                                    <?php if (isset($user['is_active']) && !$user['is_active']): ?>
                                                        <span class="status-badge status-inactive">
                                                            <i class="fas fa-ban"></i> Terminated
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-active">
                                                            <i class="fas fa-check-circle"></i> Active
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['is_admin']): ?>
                                                        <span class="status-badge status-admin">
                                                            <i class="fas fa-shield-alt"></i> Admin
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['is_verified']): ?>
                                                        <span class="status-badge status-verified">
                                                            <i class="fas fa-check"></i> Verified
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-cell">
                                                        <a href="?view=user-detail&user_id=<?php echo $user['user_id']; ?>" class="action-btn view" title="View User">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (!isset($user['is_active']) || $user['is_active']): ?>
                                                            <button class="action-btn delete terminate-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" title="Terminate Account">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="action-btn edit restore-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" title="Restore Account">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($userList['total_pages'] > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?view=users&page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for($i = max(1, $page - 2); $i <= min($userList['total_pages'], $page + 2); $i++): ?>
                                    <a href="?view=users&page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline <?php echo $i == $page ? 'current' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $userList['total_pages']): ?>
                                    <a href="?view=users&page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            
            <?php elseif ($view === 'user-detail' && $userDetail && !isset($userDetail['error'])): ?>
                <?php $user = $userDetail['user']; ?>
                <div class="page-header">
                    <h1 class="page-title">User Profile: <?php echo htmlspecialchars($user['username']); ?></h1>
                    <div class="page-actions">
                        <a href="?view=users" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Users</span>
                        </a>
                    </div>
                </div>

                <div class="user-profile">
                    <div class="profile-sidebar">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/defaults/default-profile.jpg'); ?>" alt="User" class="profile-avatar">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        
                        <div class="profile-badge-list">
                            <?php foreach ($userDetail['badges'] as $badge): ?>
                                <span class="profile-badge">
                                    <?php echo htmlspecialchars($badge['badge_name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $user['song_count']; ?></div>
                                <div class="profile-stat-label">Songs</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $user['likes_given']; ?></div>
                                <div class="profile-stat-label">Likes Given</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $user['followers']; ?></div>
                                <div class="profile-stat-label">Followers</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $user['following']; ?></div>
                                <div class="profile-stat-label">Following</div>
                            </div>
                        </div>
                        
                        <div class="profile-actions">
                            <?php if (!isset($user['is_active']) || $user['is_active']): ?>
                                <button class="btn btn-danger terminate-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                    <i class="fas fa-ban"></i> Terminate Account
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success restore-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                    <i class="fas fa-undo"></i> Restore Account
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn <?php echo $user['is_admin'] ? 'btn-warning' : 'btn-primary'; ?> toggle-admin-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-is-admin="<?php echo $user['is_admin'] ? '1' : '0'; ?>">
                                <i class="fas <?php echo $user['is_admin'] ? 'fa-user-minus' : 'fa-user-shield'; ?>"></i> 
                                <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                            </button>
                            
                            <button class="btn <?php echo $user['is_verified'] ? 'btn-warning' : 'btn-primary'; ?> toggle-verify-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-is-verified="<?php echo $user['is_verified'] ? '1' : '0'; ?>">
                                <i class="fas <?php echo $user['is_verified'] ? 'fa-times' : 'fa-check'; ?>"></i> 
                                <?php echo $user['is_verified'] ? 'Remove Verification' : 'Verify User'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="profile-content">
                        <?php if (isset($user['is_active']) && !$user['is_active']): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Account Terminated</strong>
                                    <div>Reason: <?php echo htmlspecialchars($user['termination_reason'] ?? 'No reason provided'); ?></div>
                                    <div>Terminated on: <?php echo date('M j, Y g:i A', strtotime($user['terminated_at'])); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">User Information</div>
                            <div class="card-body">
                                <div class="user-info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                    <div>
                                        <div style="color: var(--gray-text); margin-bottom: 0.25rem;">User ID</div>
                                        <div style="font-weight: 500;"><?php echo $user['user_id']; ?></div>
                                    </div>
                                    <div>
                                        <div style="color: var(--gray-text); margin-bottom: 0.25rem;">Joined</div>
                                        <div style="font-weight: 500;"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                                    </div>
                                    <div>
                                        <div style="color: var(--gray-text); margin-bottom: 0.25rem;">Email Verified</div>
                                        <div style="font-weight: 500;">
                                            <?php if ($user['email_verified']): ?>
                                                <span style="color: var(--success-color);"><i class="fas fa-check-circle"></i> Yes</span>
                                            <?php else: ?>
                                                <span style="color: var(--danger-color);"><i class="fas fa-times-circle"></i> No</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($user['bio'])): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <div style="color: var(--gray-text); margin-bottom: 0.5rem;">Bio</div>
                                        <div style="background-color: var(--darker-bg); padding: 1rem; border-radius: var(--border-radius); white-space: pre-line;">
                                            <?php echo htmlspecialchars($user['bio']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">Uploaded Songs</div>
                            <div class="card-body">
                                <?php if (empty($userDetail['songs'])): ?>
                                    <div style="text-align: center; padding: 2rem; color: var(--gray-text);">
                                        <i class="fas fa-music" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                        <p>This user hasn't uploaded any songs yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-container">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Album</th>
                                                    <th>Uploaded</th>
                                                    <th>Plays</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($userDetail['songs'] as $song): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($song['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($song['album'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($song['upload_date'])); ?></td>
                                                        <td><?php echo $song['play_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($view === 'badges'): ?>
                <div class="page-header">
                    <h1 class="page-title">Badge Management</h1>
                </div>

                <div class="card">
                    <div class="card-header">All Badges</div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Badge</th>
                                        <th>ID</th>
                                        <th>Image Path</th>
                                        <th>CSS Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allBadges as $badge): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($badge['badge_name']); ?></td>
                                            <td><?php echo $badge['badge_id']; ?></td>
                                            <td><?php echo htmlspecialchars($badge['image_path']); ?></td>
                                            <td><?php echo htmlspecialchars($badge['css_class']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Assign Badges to Users</div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Current Badges</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersWithBadges as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td id="badge-list-<?php echo $user['user_id']; ?>">
                                                <?php echo htmlspecialchars($user['badges'] ?? 'None'); ?>
                                            </td>
                                            <td>
                                                <div class="badge-actions" style="display: flex; gap: 0.5rem;">
                                                    <form class="badge-form" style="display: flex; gap: 0.5rem;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <select name="badge_id" style="padding: 0.5rem; border-radius: var(--border-radius); background: var(--darker-bg); color: var(--light-text); border: 1px solid var(--border-color);">
                                                            <?php foreach ($allBadges as $badge): ?>
                                                                <option value="<?php echo $badge['badge_id']; ?>">
                                                                    <?php echo htmlspecialchars($badge['badge_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" name="assign_badge" class="btn btn-primary btn-sm">Assign</button>
                                                    </form>
                                                    
                                                    <form class="badge-form" style="display: flex; gap: 0.5rem;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <select name="badge_id" style="padding: 0.5rem; border-radius: var(--border-radius); background: var(--darker-bg); color: var(--light-text); border: 1px solid var(--border-color);">
                                                            <?php foreach ($allBadges as $badge): ?>
                                                                <option value="<?php echo $badge['badge_id']; ?>">
                                                                    <?php echo htmlspecialchars($badge['badge_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" name="remove_badge" class="btn btn-danger btn-sm">Remove</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php elseif ($view === 'appeals'): ?>
                <div class="page-header">
                    <h1 class="page-title">Appeals Management</h1>
                    <div class="status-filters">
                        <a href="?view=appeals<?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                        class="status-filter <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">
                            All
                        </a>
                        <a href="?view=appeals&status=pending<?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                        class="status-filter <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : ''; ?>">
                            Pending
                        </a>
                        <a href="?view=appeals&status=approved<?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                        class="status-filter <?php echo isset($_GET['status']) && $_GET['status'] === 'approved' ? 'active' : ''; ?>">
                            Approved
                        </a>
                        <a href="?view=appeals&status=rejected<?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                        class="status-filter <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'active' : ''; ?>">
                            Rejected
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Account Termination Appeals
                        <?php if (isset($_GET['status'])): ?>
                            <span>(<?php echo ucfirst(htmlspecialchars($_GET['status'])); ?> appeals)</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php
                        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                        $status = isset($_GET['status']) ? $_GET['status'] : null;
                        $appeals = getAccountAppeals(10, $page, $status);
                        ?>
                        
                        <?php if (empty($appeals['appeals'])): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <p>No appeals found</p>
                                <?php if (isset($_GET['status'])): ?>
                                    <a href="?view=appeals" class="btn btn-outline">View all appeals</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Appeal Date</th>
                                            <th>Status</th>
                                            <th>Termination Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appeals['appeals'] as $appeal): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <img src="<?php echo htmlspecialchars($appeal['profile_picture'] ?? '/defaults/default-profile.jpg'); ?>" alt="User">
                                                        <div class="user-cell-info">
                                                            <div class="cell-main"><?php echo htmlspecialchars($appeal['username']); ?></div>
                                                            <div class="cell-secondary"><?php echo htmlspecialchars($appeal['email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($appeal['appeal_date'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $appeal['status']; ?>">
                                                        <?php echo ucfirst($appeal['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="termination-reason-cell">
                                                        <?php echo htmlspecialchars(substr($appeal['termination_reason'], 0, 50)); ?>
                                                        <?php if (strlen($appeal['termination_reason']) > 50): ?>
                                                            <span class="reason-tooltip" title="<?php echo htmlspecialchars($appeal['termination_reason']); ?>">...</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="action-cell">
                                                        <button class="action-btn view view-appeal-btn" 
                                                                data-appeal-id="<?php echo $appeal['appeal_id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($appeal['username']); ?>"
                                                                data-appeal-date="<?php echo date('M j, Y g:i A', strtotime($appeal['appeal_date'])); ?>"
                                                                data-appeal-reason="<?php echo htmlspecialchars($appeal['appeal_reason']); ?>"
                                                                data-termination-reason="<?php echo htmlspecialchars($appeal['termination_reason']); ?>"
                                                                data-terminated-at="<?php echo date('M j, Y', strtotime($appeal['terminated_at'])); ?>"
                                                                data-status="<?php echo $appeal['status']; ?>"
                                                                data-admin-response="<?php echo htmlspecialchars($appeal['admin_response'] ?? ''); ?>"
                                                                title="View Appeal">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($appeal['status'] === 'pending'): ?>
                                                            <button class="action-btn edit approve-appeal-btn" 
                                                                    data-appeal-id="<?php echo $appeal['appeal_id']; ?>"
                                                                    data-username="<?php echo htmlspecialchars($appeal['username']); ?>"
                                                                    title="Approve Appeal">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            
                                                            <button class="action-btn delete reject-appeal-btn" 
                                                                    data-appeal-id="<?php echo $appeal['appeal_id']; ?>"
                                                                    data-username="<?php echo htmlspecialchars($appeal['username']); ?>"
                                                                    title="Reject Appeal">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($appeals['total_pages'] > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?view=appeals&page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="btn btn-outline">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for($i = max(1, $page - 2); $i <= min($appeals['total_pages'], $page + 2); $i++): ?>
                                        <a href="?view=appeals&page=<?php echo $i; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="btn btn-outline <?php echo $i == $page ? 'current' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $appeals['total_pages']): ?>
                                        <a href="?view=appeals&page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="btn btn-outline">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($view === 'marked-for-deletion'): ?>
                <div class="page-header">
                    <h1 class="page-title">Accounts Marked for Deletion</h1>
                </div>

                <div class="card">
                    <div class="card-header">Users Pending Deletion</div>
                    <div class="card-body">
                        <?php 
                        $markedUsers = getMarkedForDeletionUsers();
                        if (empty($markedUsers)): 
                        ?>
                            <div class="no-users-message">
                                <p>No accounts are currently marked for deletion.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Created At</th>
                                            <th>Marked for Deletion</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($markedUsers as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/defaults/default-profile.jpg'); ?>" alt="User">
                                                        <div class="user-cell-info">
                                                            <div class="cell-main"><?php echo htmlspecialchars($user['username']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($user['deletion_requested_at'])); ?></td>
                                                <td>
                                                    <div class="action-cell">
                                                    <button class="action-btn restore-deletion-btn" 
                                                                data-user-id="<?php echo $user['user_id']; ?>" 
                                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                                title="Cancel Deletion">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>The requested page could not be found. <a href="?view=dashboard">Return to Dashboard</a></span>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Termination Modal -->
    <div class="modal-backdrop" id="terminateModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Terminate Account</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to terminate the account for <strong id="terminateUsername"></strong>?</p>
                <p>This will prevent the user from logging in and hide their content from the site.</p>
                
                <div class="form-group">
                    <label for="terminationReason" class="form-label">Reason for termination:</label>
                    <textarea id="terminationReason" class="form-input" placeholder="Provide a reason for this termination..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                <button type="button" class="btn btn-danger confirm-terminate-btn">Terminate Account</button>
            </div>
        </div>
    </div>

    <!-- Restore Modal -->
    <div class="modal-backdrop" id="restoreModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Restore Account</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore the account for <strong id="restoreUsername"></strong>?</p>
                <p>This will allow the user to log in again and make their content visible on the site.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                <button type="button" class="btn btn-success confirm-restore-btn">Restore Account</button>
            </div>
        </div>
    </div>

    <!-- View Appeal Modal -->
    <div class="modal-backdrop" id="viewAppealModal">
        <div class="modal-appeal">
            <div class="modal-header">
                <h3 class="modal-title">Appeal Details</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="appeal-user">
                    <h4 id="appealUsername"></h4>
                    <span id="appealDate" class="appeal-date"></span>
                    <span id="appealStatus" class="appeal-status"></span>
                </div>
                
                <div class="appeal-section">
                    <h5>Termination Details</h5>
                    <div class="termination-info">
                        <div class="termination-date" id="terminationDate"></div>
                        <div class="termination-reason" id="terminationReason"></div>
                    </div>
                </div>
                
                <div class="appeal-section">
                    <h5>Appeal Reason</h5>
                    <div class="appeal-text" id="appealReason"></div>
                </div>
                
                <div class="appeal-section" id="adminResponseSection">
                    <h5>Admin Response</h5>
                    <div class="admin-response" id="adminResponse"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline cancel-btn">Close</button>
            </div>
        </div>
    </div>

    <!-- Approve Appeal Modal -->
    <div class="modal-backdrop" id="approveAppealModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Approve Appeal</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve the appeal for <strong id="approveUsername"></strong>?</p>
                <p>This will restore the user's account and remove the termination status.</p>
                
                <div class="form-group">
                    <label for="approveResponse" class="form-label">Response to User:</label>
                    <textarea id="approveResponse" class="form-input" placeholder="Explain why you're approving this appeal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                <button type="button" class="btn btn-success confirm-approve-btn">Approve Appeal</button>
            </div>
        </div>
    </div>

    <!-- Reject Appeal Modal -->
    <div class="modal-backdrop" id="rejectAppealModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Reject Appeal</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject the appeal for <strong id="rejectUsername"></strong>?</p>
                <p>The user's account will remain terminated.</p>
                
                <div class="form-group">
                    <label for="rejectResponse" class="form-label">Response to User:</label>
                    <textarea id="rejectResponse" class="form-input" placeholder="Explain why you're rejecting this appeal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                <button type="button" class="btn btn-danger confirm-reject-btn">Reject Appeal</button>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <script src="js/adminMobile.js"></script>
</body>
</html>