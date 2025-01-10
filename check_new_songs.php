<?php
require_once 'config.php';
require_once 'NotificationSystem.php';

try {
    $notificationSystem = new NotificationSystem($db);
    $notificationSystem->checkNewSongs();
    echo "Success: Checked for new songs\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}