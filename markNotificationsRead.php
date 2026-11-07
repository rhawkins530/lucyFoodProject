<?php
    /*
     * Called by the bell in header.php when an admin opens the notifications dropdown.
     * Marks all of the logged-in admin's notifications as read, so the red badge goes away.
     */
    session_cache_expire(30);
    session_start();

    header('Content-Type: application/json');

    /* Only logged-in admins and superadmins have a bell, and only POST requests change anything */
    if (!isset($_SESSION['_id']) || ($_SESSION['access_level'] ?? 0) < 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        die();
    }

    require_once('database/dbNotifications.php');

    $ok = markAllNotificationsRead($_SESSION['_id']);
    echo json_encode(['ok' => (bool)$ok]);
