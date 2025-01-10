<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'NotificationSystem.php';

try {
    $notificationSystem = new NotificationSystem($db);
    $notificationId = $_POST['notification_id'] ?? null;
    
    if ($notificationId) {
        $success = $notificationSystem->markAsRead($notificationId);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No notification ID provided']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}