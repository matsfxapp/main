<?php
require_once 'handlers/share_utils.php';

$songId = $song['song_id'] ?? '';

if (!empty($songId)) {
    $shareCode = getShareCode($pdo, $songId);
}
?>
<div class="song-actions">
    <?php if (!empty($songId) && !empty($shareCode)): ?>
        <button class="share-btn" onclick="event.stopPropagation(); shareSong('<?php echo htmlspecialchars($shareCode, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($song['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($song['artist'], ENT_QUOTES); ?>')">
            <i class="fas fa-share-alt"></i>
        </button>
    <?php endif; ?>
</div>