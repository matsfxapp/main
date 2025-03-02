<?php
function generateShareCode($length = 10) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $code;
}

function ensureUniqueShareCode($pdo, $songId) {
    $stmt = $pdo->prepare("SELECT share_code FROM songs WHERE song_id = :song_id");
    $stmt->bindParam(':song_id', $songId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['share_code'])) {
        return $result['share_code'];
    }
    

    $isUnique = false;
    $shareCode = '';
    
    while (!$isUnique) {
        $shareCode = generateShareCode();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM songs WHERE share_code = :share_code");
        $stmt->bindParam(':share_code', $shareCode, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $isUnique = true;
        }
    }

    $stmt = $pdo->prepare("UPDATE songs SET share_code = :share_code WHERE song_id = :song_id");
    $stmt->bindParam(':share_code', $shareCode, PDO::PARAM_STR);
    $stmt->bindParam(':song_id', $songId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $shareCode;
}

function getShareCode($pdo, $songId) {
    return ensureUniqueShareCode($pdo, $songId);
}
?>