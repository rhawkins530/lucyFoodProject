<?php

/*
 * Each FoodOrderItem represents an item in a food order with its details
 */

class FoodOrderItem {

    private $id; // Primary key
    private $order_id; // Foreign Key
    private $item_category_id; // Foreign Key
    private $quantity_requested; // Quantity of the item requested in the order
    private $quantity_delivered; // Quantity of the item delivered in the order

    /*
        Constructor for the FoodOrderItem class
        Parameters:
        - $id: The ID of the food order item.
        - $order_id: The ID of the food order to which this item belongs.
        - $item_category_id: The ID of the item category for this item.
        - $quantity_requested: The quantity of the item requested in the order.
        - $quantity_delivered: The quantity of the item delivered in the order.
    */
    function __construct($id, $order_id, $item_category_id, $quantity_requested, $quantity_delivered) {
        $this->id = $id;
        $this->order_id = $order_id;
        $this->item_category_id = $item_category_id;
        $this->quantity_requested = $quantity_requested;
        $this->quantity_delivered = $quantity_delivered;
    }

    /*
        Getters for the FoodOrderItem class properties
    */
    function getId() {
        return $this->id;
    }

    function getOrder_id() {
        return $this->order_id;
    }

    function getItem_category_id() {
        return $this->item_category_id;
    }

    function getQuantity_requested() {
        return $this->quantity_requested;
    }

    function getQuantity_delivered() {
        return $this->quantity_delivered;
    }
    
    function setQuantity_delivered($quantity_delivered) {
        $this->quantity_delivered = $quantity_delivered;
    }
}