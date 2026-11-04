<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/Notification.php');

/*
    Notification types, saved in dbnotifications.type. Together with reference_id they say what a
    notification is about, so it can be deleted once it no longer applies.
    - 'pending_order': reference_id is the dbfoodorder id. Deleted when the order is approved or rejected.
    - 'low_stock': reference_id is the dbinventory id. Deleted when the item is back at or above its minimum.
    The page each type opens when clicked is set in Notification::getLink().
*/

/*
    Add a notification for one person
    Parameters:
    - $recipientId: The dbpersons.id (username) of the person to notify.
    - $message: The text shown in the bell dropdown.
    - $type: What the notification is about, e.g. 'pending_order'. (Optional, defaults to null)
    - $referenceId: The id of the order or item it is about. (Optional, defaults to null)
    Returns:
    - true if the notification was added, false otherwise.
*/
function addNotification($recipientId, $message, $type = null, $referenceId = null) {
    $con = connect();
    $recipientId = mysqli_real_escape_string($con, $recipientId);
    $message = mysqli_real_escape_string($con, $message);
    $type = $type === null ? "NULL" : "'" . mysqli_real_escape_string($con, $type) . "'";
    $referenceId = $referenceId === null ? "NULL" : (int)$referenceId;

    $query = "INSERT INTO dbnotifications (recipient_id, message, type, reference_id)
              VALUES ('$recipientId', '$message', $type, $referenceId)";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Give every active admin and superadmin their own copy of a notification
    Parameters:
    - $message: The text shown in the bell dropdown.
    - $type: What the notification is about, e.g. 'pending_order'. (Optional, defaults to null)
    - $referenceId: The id of the order or item it is about. (Optional, defaults to null)
    Returns:
    - true if the notifications were added, false otherwise.
*/
function notifyAllAdmins($message, $type = null, $referenceId = null) {
    $con = connect();
    $message = mysqli_real_escape_string($con, $message);
    $type = $type === null ? "NULL" : "'" . mysqli_real_escape_string($con, $type) . "'";
    $referenceId = $referenceId === null ? "NULL" : (int)$referenceId;

    // vmsroot is always an admin (its access level is hard coded in login.php)
    $query = "INSERT INTO dbnotifications (recipient_id, message, type, reference_id)
              SELECT id, '$message', $type, $referenceId FROM dbpersons
              WHERE (LOWER(type) IN ('admin', 'superadmin') AND status = 'Active') OR id = 'vmsroot'";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Delete every admin's copy of the notifications about one order or item, so they
    disappear from the bell.
    Parameters:
    - $type: What the notifications are about, e.g. 'pending_order'.
    - $referenceId: The id of the order or item.
    Returns:
    - true if the update worked, false otherwise.
*/
function deleteNotifications($type, $referenceId) {
    $con = connect();
    $type = mysqli_real_escape_string($con, $type);
    $referenceId = (int)$referenceId;

    $query = "DELETE FROM dbnotifications WHERE type = '$type' AND reference_id = $referenceId";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Get the most recent notifications for one person, newest first
    Parameters:
    - $recipientId: The dbpersons.id (username) of the person.
    - $limit: The most notifications to return. (Optional, defaults to 20)
    Returns:
    - An array of Notification objects.
*/
function getNotifications($recipientId, $limit = 20) {
    $con = connect();
    $recipientId = mysqli_real_escape_string($con, $recipientId);
    $limit = (int)$limit;

    $query = "SELECT * FROM dbnotifications WHERE recipient_id = '$recipientId'
              ORDER BY created_at DESC, id DESC LIMIT $limit";
    $result = mysqli_query($con, $query);
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) { // Create a Notification object for each row
        $notifications[] = new Notification(
            (int)$row['id'],
            $row['recipient_id'],
            $row['message'],
            $row['type'],
            $row['reference_id'] === null ? null : (int)$row['reference_id'],
            $row['created_at'],
            (bool)$row['is_read']
        );
    }

    mysqli_close($con);
    return $notifications;
}

/*
    Count one person's unread notifications
    Parameters:
    - $recipientId: The dbpersons.id (username) of the person.
    Returns:
    - The number of unread notifications.
*/
function getUnreadNotificationCount($recipientId) {
    $con = connect();
    $recipientId = mysqli_real_escape_string($con, $recipientId);

    $query = "SELECT COUNT(*) AS unread FROM dbnotifications
              WHERE recipient_id = '$recipientId' AND is_read = 0";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return (int)$row['unread'];
}

/*
    Build the bell button shown in header.php for admins: the bell icon, the red unread badge,
    and the dropdown listing the notifications, newest first.
    This is a function so its variables stay inside it. header.php runs in the same scope as
    the page that includes it, so plain variables there would overwrite the page's own variables.
    Parameters:
    - $recipientId: The dbpersons.id (username) of the logged-in admin.
    Returns:
    - The HTML for the bell.
*/
function notificationBellHtml($recipientId) {
    $notifications = getNotifications($recipientId);
    $unreadCount = getUnreadNotificationCount($recipientId);

    // Build the list shown in the dropdown, newest first
    $notificationList = '';
    foreach ($notifications as $notification) {
        $text = htmlspecialchars($notification->getMessage());
        $time = date('M j, g:i A', strtotime($notification->getCreatedAt()));
        $class = $notification->isRead() ? 'notification-item' : 'notification-item unread';
        $item = '<div class="' . $class . '">' . $text . '<span class="notification-time">' . $time . '</span></div>';
        if ($notification->getLink()) {
            $item = '<a href="' . htmlspecialchars($notification->getLink()) . '" class="dropdown-link">' . $item . '</a>';
        }
        $notificationList .= $item;
    }
    if ($notificationList === '') {
        $notificationList = '<p>No notifications</p>';
    }

    // Red badge with the number of unread notifications (9+ when there are more than 9)
    $badge = '';
    if ($unreadCount > 0) {
        $badge = '<span class="notification-badge">' . ($unreadCount > 9 ? '9+' : $unreadCount) . '</span>';
    }

    return '<div class="nav-links">
                <div class="nav-item" id="notificationBell">
                    <div class="icon">
                        <img src="images/bell.svg" alt="Notifications" title="Notifications" class="icon-img in-nav-img">
                        ' . $badge . '
                        <div class="dropdown notification-dropdown">
                            ' . $notificationList . '
                        </div>
                    </div>
                </div>
            </div>';
}

/*
    Mark all of one person's notifications as read
    Parameters:
    - $recipientId: The dbpersons.id (username) of the person.
    Returns:
    - true if the update worked, false otherwise.
*/
function markAllNotificationsRead($recipientId) {
    $con = connect();
    $recipientId = mysqli_real_escape_string($con, $recipientId);

    $query = "UPDATE dbnotifications SET is_read = 1 WHERE recipient_id = '$recipientId' AND is_read = 0";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}
