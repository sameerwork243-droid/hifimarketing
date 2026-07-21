<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/notifications.php';

requireAdmin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    markNotificationRead($id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>