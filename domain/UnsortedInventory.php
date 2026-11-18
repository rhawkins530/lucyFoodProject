<?php

/*
 * Each UnsortedInventory represents an unsorted inventory crate with its details
 */

class UnsortedInventory {

    private $id; // Primary key
    private $weight;
    private $crate_type;
    private $status;

    function __construct($id, $weight, $crate_type, $status) {
        $this->id = $id;
        $this->weight = $weight;
        $this->crate_type = $crate_type;
        $this->status = $status;
    }

    function getId() {
        return $this->id;
    }

    function getWeight() {
        return $this->weight;
    }

    function getCrateType() {
        return $this->crate_type;
    }

    function getStatus() {
        return $this->status;
    }

    function setStatus($status) {
        $this->status = $status;
    }
}