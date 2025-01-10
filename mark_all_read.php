<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'NotificationSystem.php';

try {
    $notificationSystem = new NotificationSystem($db);
    $success = $notificationSystem->markAllRead();
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}