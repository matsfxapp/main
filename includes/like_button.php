<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$songId = isset($song['song_id']) ? $song['song_id'] : null;
if ($songId === null) {
    echo "Error: Song ID is not defined.";
    exit;
}

// Always get the like count regardless of login status
try {
    $stmt = $pdo->prepare("SELECT likes_count FROM song_likes_count WHERE song_id = ?");
    $stmt->execute([$songId]);
    $result = $stmt->fetch();
    $likeCount = $result ? $result['likes_count'] : 0;
    
    $isLiked = false;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$userId, $songId]);
        $isLiked = $stmt->rowCount() > 0;
    }
} catch (PDOException $e) {
    $isLiked = false;
    $likeCount = 0;
}
?>

<div class="like-button-container" data-song-id="<?php echo htmlspecialchars($songId ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <button class="like-button <?php echo $isLiked ? 'liked' : ''; ?>" 
            <?php if (!isset($_SESSION['user_id'])): ?><?php endif; ?>
            onclick="toggleLike(event, this, <?php echo htmlspecialchars($songId ?? '', ENT_QUOTES, 'UTF-8'); ?>)">
        <i class="fas fa-heart"></i>
    </button>
    <span class="like-count"><?php echo $likeCount; ?></span>
</div>

<style>

.like-button-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.like-button {
    background: none;
    border: none;
    cursor: pointer;
    color: #94A3B8;
    transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 8px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    position: relative;
}

.like-button:hover {
    background-color: rgba(45, 127, 249, 0.1);
    color: #2D7FF9;
}

.like-button.liked {
    color: #2D7FF9;
}

.like-count {
    font-size: 0.9em;
    color: #94A3B8;
    min-width: 20px;
}

.like-button[disabled] {
    opacity: 0.8;
    cursor: pointer;
}
</style>

<script>
document.body.insertAdjacentHTML('beforeend', `
<div id="globalLoginModal" class="login-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Join the matSFX Community!</h3>
            <button class="close-modal" aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-body">
            <i class="fas fa-heart modal-icon"></i>
            <p>Sign in to like songs upload them and add them to your playlist :D</p>
            <div class="modal-buttons">
                <button onclick="window.location.href='login'" class="modal-button login-btn">Log In</button>
                <button onclick="window.location.href='signup'" class="modal-button signup-btn">Sign Up</button>
            </div>
        </div>
    </div>
</div>

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
	--error-color: #FF4B4B;
}


.login-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.login-modal.show {
    opacity: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-modal .modal-content {
    background: #111827;
    width: 90%;
    max-width: 400px;
    border-radius: 16px;
    border: 1px solid #1F2937;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.login-modal .modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #1F2937;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.login-modal .modal-header h3 {
    color: #FFFFFF;
    margin: 0;
    font-size: 1.25rem;
}

.login-modal .close-modal {
    background: none;
    border: none;
    font-size: 24px;
    color: #94A3B8;
    cursor: pointer;
    padding: 4px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-modal .close-modal:hover {
    background-color: #1F2937;
    color: #FFFFFF;
}

.login-modal .modal-body {
    padding: 24px;
    text-align: center;
}

.login-modal .modal-icon {
    font-size: 48px;
    color: #2D7FF9;
    margin-bottom: 16px;
}

.login-modal .modal-body p {
    color: #94A3B8;
    margin: 0 0 24px 0;
    font-size: 1rem;
    line-height: 1.5;
}

.login-modal .modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.login-modal .modal-button {
    padding: 10px 24px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.login-modal .login-btn {
    background-color: #2D7FF9;
    color: white;
}

.login-modal .login-btn:hover {
    background-color: #1E6AD4;
}

.login-modal .signup-btn {
    background-color: transparent;
    color: #FFFFFF;
    border: 1px solid #1F2937;
}

.login-modal .signup-btn:hover {
    background-color: #1F2937;
}
</style>
`);

// Simple modal controls
const modal = {
    element: document.getElementById('globalLoginModal'),
    isOpen: false,
    
    show() {
        if (this.isOpen) return;
        this.isOpen = true;
        this.element.classList.add('show');
    },
    
    hide() {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.element.classList.remove('show');
    }
};

// Setup event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Close button
    modal.element.querySelector('.close-modal').addEventListener('click', () => modal.hide());
    
    // Click outside
    modal.element.addEventListener('click', (e) => {
        if (e.target === modal.element) modal.hide();
    });
    
    // ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.isOpen) modal.hide();
    });
});

// Modified toggleLike function
function toggleLike(event, button, songId) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!button || button.disabled) {
        modal.show();
        return;
    }

    fetch('includes/process_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ song_id: songId })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            button.classList.toggle('liked');
            const countElement = button.nextElementSibling;
            if (countElement) {
                countElement.textContent = data.likes_count;
            }
        } else if (data.error === 'not_logged_in') {
            modal.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>
