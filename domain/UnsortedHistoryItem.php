<?php
    class UnsortedHistoryItem {
        private $id; // primary key
        private $unsorted_id; // foreign key
        private $change_amount;
        private $change_date;
        private $changer_id; // foreign key

        function __construct($id, $unsorted_id, $change_amount, $change_date, $changer_id)
        {
            $this->id = $id;
            $this->unsorted_id = $unsorted_id;
            $this->change_amount = $change_amount;
            $this->change_date = $change_date;
            $this->changer_id = $changer_id;
        }

        function getId() {return $this->id;}

        function getUnsortedId() {return $this->unsorted_id;}
        
        function getChangeAmount() {return $this->change_amount;}
        
        function getChangeDate() {return $this->change_date;}
        
        function getChangerId() {return $this->changer_id;}
    }
?>