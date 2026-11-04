<?php

/*
 * Each Notification is one message shown in an admin's bell dropdown in the header
 */

class Notification {

    private $id; // Primary key
    private $recipient_id; // dbpersons.id (username) of the admin who receives it
    private $message; // Text shown in the dropdown
    private $type; // What it is about: 'pending_order' or 'low_stock' (can be null)
    private $reference_id; // id of the order or inventory item it is about (can be null)
    private $created_at; // When the notification was created
    private $is_read; // Whether the admin has opened the bell since it arrived

    /*
        Constructor for the Notification class
        Parameters:
        - $id: The ID of the notification.
        - $recipient_id: The username of the admin who receives it.
        - $message: The text shown in the dropdown.
        - $type: What it is about, e.g. 'pending_order' (can be null).
        - $reference_id: The id of the order or item it is about (can be null).
        - $created_at: When the notification was created.
        - $is_read: true if it has been read, false otherwise.
    */
    function __construct($id, $recipient_id, $message, $type, $reference_id, $created_at, $is_read) {
        $this->id = $id;
        $this->recipient_id = $recipient_id;
        $this->message = $message;
        $this->type = $type;
        $this->reference_id = $reference_id;
        $this->created_at = $created_at;
        $this->is_read = $is_read;
    }

    /*
        Getters for the Notification class properties
    */
    function getId() {
        return $this->id;
    }

    function getRecipientId() {
        return $this->recipient_id;
    }

    function getMessage() {
        return $this->message;
    }

    function getType() {
        return $this->type;
    }

    function getReferenceId() {
        return $this->reference_id;
    }

    function getCreatedAt() {
        return $this->created_at;
    }

    function isRead() {
        return $this->is_read;
    }

    /*
        The page to open when the notification is clicked, based on its type.
        Returns null for notifications without a type, so they aren't clickable.
    */
    function getLink() {
        if ($this->type === 'pending_order') {
            return 'reviewOrders.php';
        }
        if ($this->type === 'low_stock') {
            return 'inventory.php';
        }
        return null;
    }
}
