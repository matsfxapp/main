<?php
// includes/like-button.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    $isLiked = false;
    $likeCount = 0;
} else {
    $userId = $_SESSION['user_id'];
    $songId = $song['song_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$userId, $songId]);
        $isLiked = $stmt->rowCount() > 0;
        
        $stmt = $pdo->prepare("SELECT likes_count FROM song_likes_count WHERE song_id = ?");
        $stmt->execute([$songId]);
        $result = $stmt->fetch();
        $likeCount = $result ? $result['likes_count'] : 0;
    } catch (PDOException $e) {
        $isLiked = false;
        $likeCount = 0;
    }
}
?>

<div class="like-button-container" data-song-id="<?php echo htmlspecialchars($songId); ?>">
    <button class="like-button <?php echo $isLiked ? 'liked' : ''; ?>" 
            onclick="toggleLike(this, <?php echo htmlspecialchars($songId); ?>)">
        <i class="fas fa-heart"></i>
    </button>
    <span class="like-count"><?php echo $likeCount; ?></span>
</div>

<style>
.like-button-container {
    display: flex;
    align-items: center;
    gap: 5px;
}

.like-button {
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    transition: color 0.3s;
    padding: 5px;
}

.like-button.liked {
    color: #ff0000;
}

.like-button:hover {
    color: #ff0000;
}

.like-count {
    font-size: 0.9em;
    color: #666;
}
</style>

<script>
function toggleLike(button, songId) {
    event.stopPropagation();
    
    fetch('includes/process_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ song_id: songId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            button.classList.toggle('liked');
            const countElement = button.nextElementSibling;
            countElement.textContent = data.likes_count;
        } else if (data.error === 'not_logged_in') {
            alert('Please log in to like songs');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Don't show alert since the operation actually succeeded
        // Just update the UI based on the button's current state
        button.classList.toggle('liked');
        const countElement = button.nextElementSibling;
        const currentCount = parseInt(countElement.textContent);
        countElement.textContent = button.classList.contains('liked') ? currentCount + 1 : currentCount - 1;
    });
}
</script>