<?php

/*
 * Each InventoryItem represents an item type in the inventory with its details
 */

class InventoryItem {

    private $id; // Primary key
    private $item_category_id; // Foreign Key
    private $category_name;
    private $current_quantity;
    private $minimum_quantity;
    private $allocated_quantity;

    function __construct($id, $item_category_id, $category_name, $current_quantity, $minimum_quantity, $allocated_quantity) {
        $this->id = $id;
        $this->item_category_id = $item_category_id;
        $this->category_name = $category_name;
        $this->current_quantity = $current_quantity;
        $this->minimum_quantity = $minimum_quantity;
        $this->allocated_quantity = $allocated_quantity;
    }

    function getId() {
        return $this->id;
    }

    function getItemCategoryId() {
        return $this->item_category_id;
    }

    function getCategoryName() {
        return $this->category_name;
    }

    function getCurrentQuantity() {
        return $this->current_quantity;
    }

    function getMinimumQuantity() {
        return $this->minimum_quantity;
    }

    function getAllocatedQuantity() {
        return $this->allocated_quantity;
    }
}