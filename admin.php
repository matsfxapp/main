<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

function isAdmin($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['is_admin'] == 1;
}

// Get statistics using PDO
$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM songs");
$totalSongs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM playlists");
$totalPlaylists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$recentActivities = [];

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'deleteUser' && isset($_GET['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_GET['user_id']]);
        header('Location: admin_dashboard.php');
        exit();
    }

    if ($_GET['action'] == 'deleteSong' && isset($_GET['song_id'])) {
        $stmt = $conn->prepare("DELETE FROM songs WHERE song_id = :song_id");
        $stmt->execute(['song_id' => $_GET['song_id']]);
        header('Location: admin.php');
        exit();
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'updateUser') {
        $userId = $_POST['userId'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $isVerified = isset($_POST['is_verified']) ? 1 : 0;
        $isHelper = isset($_POST['is_helper']) ? 1 : 0;
        $isDonator = isset($_POST['is_donator']) ? 1 : 0;
        $isDeveloper = isset($_POST['is_developer']) ? 1 : 0;
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET 
            username = :username,
            email = :email,
            is_verified = :is_verified,
            is_helper = :is_helper, 
            is_donator = :is_donator,
            is_developer = :is_developer,
            is_admin = :is_admin
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'is_verified' => $isVerified,
            'is_helper' => $isHelper,
            'is_donator' => $isDonator,
            'is_developer' => $isDeveloper,
            'is_admin' => $isAdmin,
            'user_id' => $userId
        ]);
        echo json_encode(['success' => true]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
	<meta property="og:title" content="matSFX - Listen with Joy!" />
	<meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
	<meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx-logo-squared.png" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://matsfx.com/" />
    <title>Admin Dashboard</title>
	<link rel="icon" type="image/png" sizes="32x32" href="https://matsfx.com/app_logos/matsfx-logo-squared.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at top right, var(--darker-bg) 0%, var(--dark-bg) 100%);
            color: var(--light-text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            line-height: 1.5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-nav {
            width: 260px;
            background: var(--card-bg);
            padding: 2rem;
            border-right: 1px solid var(--border-color);
        }

        .nav-header h1 {
            color: var(--light-text);
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--gray-text);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .admin-main {
            flex: 1;
            padding: 2rem;
            background: var(--dark-bg);
            overflow-y: auto;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .stat-card h3 {
            color: var(--gray-text);
            font-size: 0.875rem;
            margin: 0 0 0.5rem 0;
        }

        .stat-card p {
            color: var(--light-text);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .admin-section {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .search-bar {
            margin-bottom: 1.5rem;
        }

        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            color: var(--light-text);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            color: var(--gray-text);
            font-weight: 500;
        }

        .activity-log {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .log-entry {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--darker-bg);
            border-radius: var(--border-radius);
        }

        .log-time {
            color: var(--gray-text);
            font-size: 0.875rem;
        }

        .log-message {
            color: var(--light-text);
        }

        .button {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .button-primary {
            background: var(--primary-color);
            color: var(--light-text);
        }

        .button-primary:hover {
            background: var(--primary-hover);
        }

        .button-danger {
            background: #DC2626;
            color: var(--light-text);
        }

        .button-danger:hover {
            background: #B91C1C;
        }
        
        .admin-section {
            display: none;
        }

        .admin-section.active {
            display: block;
        }
		
		.badge-icon {
			width: 30px;
            height: 30px;
			gap: 5rem;
		}
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <div class="nav-header">
                <h1>Admin Panel</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#dashboard" class="active">Dashboard</a></li>
                <li><a href="#users">User Management</a></li>
                <li><a href="#songs">Song Management</a></li>
                <li><a href="#settings">Settings</a></li>
            </ul>
        </nav>

        <main class="admin-main">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Songs</h3>
                    <p><?php echo $totalSongs; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Playlists</h3>
                    <p><?php echo $totalPlaylists; ?></p>
                </div>
            </div>

			<section id="users" class="admin-section active">
				<h2>User Management</h2>
				<div class="search-bar">
					<input type="text" id="userSearch" placeholder="Search users...">
				</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>User ID</th>
								<th>Name</th>
								<th>Email</th>
								<th>Badges</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$stmt = $conn->query("SELECT * FROM users");
							while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
								echo '<tr>';
								echo '<td>' . $user['user_id'] . '</td>';
								echo '<td>' . htmlspecialchars($user['username']) . '</td>';
								echo '<td>' . htmlspecialchars($user['email']) . '</td>';
								echo '<td>';
								if ($user['is_verified']) {
									echo '<img src="app-images/verified-badge.png" alt="Verified" class="badge-icon">';
								}
								if ($user['is_helper']) {
									echo '<img src="app-images/helper-badge.png" alt="Helper" class="badge-icon">';
								}
								if ($user['is_donator']) {
									echo '<img src="app-images/donator-badge.png" alt="Donator" class="badge-icon">';
								}
								if ($user['is_developer']) {
									echo '<img src="app-images/developer-badge.png" alt="Developer" class="badge-icon">';
								}
								if ($user['is_admin']) {
									echo '<img src="app-images/admin-badge.png" alt="Admin" class="badge-icon">';
								}
								echo '</td>';
								echo '<td><button onclick="editUser(' . $user['user_id'] . ')" class="button button-primary">Edit</button></td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
			</section>

            <section id="songs" class="admin-section">
                <h2>Song Management</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Song ID</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->query("SELECT * FROM songs");
                            while ($song = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>';
                                echo '<td>' . $song['song_id'] . '</td>';
                                echo '<td>' . htmlspecialchars($song['title']) . '</td>';
                                echo '<td>' . htmlspecialchars($song['artist']) . '</td>';
                                echo '<td><a href="?action=deleteSong&song_id=' . $song['song_id'] . '" class="button button-danger">Delete</a></td>';
                             
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="settings" class="admin-section">
                <h2>Settings</h2>
                <p>Settings content goes here</p>
            </section>
        </main>
    </div>

	<script src="js/admin.js"></script>
    <script>
        // Toggle sections based on navigation
        const navLinks = document.querySelectorAll('.nav-links a');
        const sections = document.querySelectorAll('.admin-section');

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                navLinks.forEach(link => link.classList.remove('active'));
                e.target.classList.add('active');
                sections.forEach(section => section.classList.remove('active'));
                document.querySelector(e.target.getAttribute('href')).classList.add('active');
            });
        });

        // User search functionality
        const userSearch = document.getElementById('userSearch');
        userSearch.addEventListener('input', function() {
            const filter = userSearch.value.toLowerCase();
            const rows = document.querySelectorAll('#users tbody tr');
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                if (name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
	<script>
		function editUser(userId) {
			fetch(`admin_handlers.php?action=getUserDetails&id=${userId}`)
				.then(response => {
					if (!response.ok) {
						throw new Error('Network response was not ok');
					}
					return response.json();
				})
				.then(user => {
					showEditUserModal(user);
				})
				.catch(error => {
					console.error('Error loading user details:', error);
					// Add more detailed error handling
					console.error('Full error response:', error.message);
					showNotification('Error loading user details. Check the console for more information.', 'error');
				});
		}
		
		function showEditUserModal(user) {
			// Remove any existing modals first
			const existingModal = document.querySelector('.modal');
			if (existingModal) {
				existingModal.remove();
			}

			const modal = document.createElement('div');
			modal.className = 'modal';
			modal.innerHTML = `
				<div class="modal-content">
					<h2>Edit User</h2>
					<form id="editUserForm">
						<input type="hidden" name="userId" value="${user.user_id}">
						<div class="form-group">
							<label>Username</label>
							<input type="text" name="username" value="${user.username}" required>
						</div>
						<div class="form-group">
							<label>Email</label>
							<input type="email" name="email" value="${user.email}" required>
						</div>
						<div class="form-group">
							<label>Badges</label>
							<div>
								<label>
									<input type="checkbox" name="is_verified" ${user.is_verified ? 'checked' : ''}>
									Verified
								</label>
								<label>
									<input type="checkbox" name="is_helper" ${user.is_helper ? 'checked' : ''}>
									Helper
								</label>
								<label>
									<input type="checkbox" name="is_donator" ${user.is_donator ? 'checked' : ''}>
									Donator
								</label>
								<label>
									<input type="checkbox" name="is_developer" ${user.is_developer ? 'checked' : ''}>
									Developer
								</label>
								<label>
									<input type="checkbox" name="is_admin" ${user.is_admin ? 'checked' : ''}>
									Admin
								</label>
							</div>
						</div>
						<div class="form-actions">
							<button type="submit" class="button button-primary">Save Changes</button>
							<button type="button" onclick="closeModal()" class="button">Cancel</button>
						</div>
					</form>
				</div>
			`;

			document.body.appendChild(modal);

			// Add event listener to form submission
			const editUserForm = document.getElementById('editUserForm');
			editUserForm.addEventListener('submit', function(e) {
				e.preventDefault();
				updateUser(new FormData(this));
			});

			// Add click event to close modal when clicking outside
			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					closeModal();
				}
			});
		}

		function closeModal() {
			const modal = document.querySelector('.modal');
			if (modal) {
				modal.remove();
			}
		}

		function updateUser(formData) {
			fetch('admin_handlers.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					closeModal();
					location.reload(); // Reload page to refresh user list
					showNotification('User updated successfully');
				} else {
					showNotification(result.message || 'Error updating user', 'error');
				}
			})
			.catch(error => {
				console.error('Error updating user:', error);
				showNotification('Error updating user', 'error');
			});
		}

		function showNotification(message, type = 'success') {
			const notification = document.createElement('div');
			notification.className = `notification notification-${type}`;
			notification.textContent = message;
			notification.style.position = 'fixed';
			notification.style.top = '20px';
			notification.style.right = '20px';
			notification.style.backgroundColor = type === 'success' ? 'green' : 'red';
			notification.style.color = 'white';
			notification.style.padding = '10px';
			notification.style.borderRadius = '5px';
			document.body.appendChild(notification);

			setTimeout(() => {
				notification.remove();
			}, 3000);
		}
	</script>
</body>
</html>
