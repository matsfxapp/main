<?php
class NotificationSystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getNotifications($limit = 20) {
        $query = "SELECT n.*, s.title as song_title, s.artist 
                 FROM notifications n 
                 LEFT JOIN songs s ON n.song_id = s.id 
                 ORDER BY n.created_at DESC 
                 LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUnreadCount() {
        $query = "SELECT COUNT(*) FROM notifications WHERE `read` = 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function markAsRead($notificationId) {
        $query = "UPDATE notifications SET `read` = 1 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$notificationId]);
    }
    
    public function markAllRead() {
        $query = "UPDATE notifications SET `read` = 1";
        $stmt = $this->db->prepare($query);
        return $stmt->execute();
    }
    
    public function addNotification($message, $type, $songId = null) {
        $query = "INSERT INTO notifications (message, type, song_id, created_at, `read`) 
                 VALUES (?, ?, ?, NOW(), 0)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$message, $type, $songId]);
    }
    
    public function checkNewSongs() {
        $lastCheckQuery = "SELECT last_check FROM notification_checks 
                          WHERE check_type = 'songs' LIMIT 1";
        $stmt = $this->db->prepare($lastCheckQuery);
        $stmt->execute();
        $lastCheck = $stmt->fetchColumn();
        
        if (!$lastCheck) {
            $this->initializeLastCheck();
            return;
        }
        
        $query = "SELECT * FROM songs 
                 WHERE upload_date > ?
                 ORDER BY upload_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lastCheck]);
        $newSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($newSongs as $song) {
            $message = "New song: {$song['title']} by {$song['artist']}";
            $this->addNotification($message, 'new_song', $song['id']);
        }
        
        $this->updateLastCheck();
    }
    
    private function initializeLastCheck() {
        $query = "INSERT INTO notification_checks (check_type, last_check) 
                 VALUES ('songs', NOW()) 
                 ON DUPLICATE KEY UPDATE last_check = NOW()";
        $stmt = $this->db->prepare($query);
        return $stmt->execute();
    }
    
    private function updateLastCheck() {
        $query = "UPDATE notification_checks SET last_check = NOW() 
                 WHERE check_type = 'songs'";
        $stmt = $this->db->prepare($query);
        return $stmt->execute();
    }
}