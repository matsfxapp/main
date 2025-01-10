<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'NotificationSystem.php';

try {
    $notificationSystem = new NotificationSystem($db);
    $notifications = $notificationSystem->getNotifications();
    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}