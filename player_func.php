<?php
class matSFXPlayer {
    private $db;
    private $userId;

    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }

    // Get current playing track
    public function getCurrentTrack() {
        $query = "SELECT 
            song.song_id, 
            songs.title, 
            songs.file_path, 
            songs.cover_art,
            users.name AS username,
            songs.album AS album_name
        FROM user_playback 
        JOIN songs ON user_playback.songs_id = songs.id
        JOIN artists ON songs.users_id = users.user_id
        JOIN songs ON songs.album = songs.album
        WHERE user_playback.user_id = ?
        LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    // Save current playback state
    public function savePlaybackState($trackId, $currentTime) {
        $query = "INSERT INTO user_playback 
            (user_id, track_id, last_played, current_time) 
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE 
            last_played = NOW(), 
            current_time = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iidi", $this->userId, $trackId, $currentTime, $currentTime);
        return $stmt->execute();
    }

    // Get next track in playlist or queue
    public function getNextTrack() {
        $query = "SELECT 
            song.song_id, 
            songs.title, 
            songs.file_path, 
            songs.cover_art,
            users.name AS username,
        FROM songs
        JOIN users ON songs.artist = users.username
        WHERE songs.song_id > (
            SELECT song_id FROM user_playback 
            WHERE user_id = ?
        )
        ORDER BY song_id ASC
        LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    // Get user's recently played tracks
    public function getRecentTracks($limit = 10) {
        $query = "SELECT 
            song_id, 
            songs.title, 
            songs.file_path, 
            songs.cover_art,
            artists.name AS artist_name
        FROM user_playback 
        JOIN songs ON user_playback.song_id = songs.song_id
        JOIN users ON songs.artist = users.username
        WHERE user_playback.user_id = ?
        ORDER BY user_playback.last_played DESC
        LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $this->userId, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
}