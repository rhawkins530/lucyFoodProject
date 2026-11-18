<?php

class UnsortedCategory {

    private $id;                //Primary Key
    private $unsorted_id;       //Foreign Key
    private $item_category_id;  //Foreign Key

    function __construct($id, $unsorted_id, $item_category_id) {
        $this->id = $id;
        $this->unsorted_id = $unsorted_id;
        $this->item_category_id = $item_category_id;
    }

    function getId() {
        return $this->id;
    }
    
    function getUnsortedId() {
        return $this->unsorted_id;
    }

    function getItemCategoryId() {
        return $this->item_category_id;
    }
}