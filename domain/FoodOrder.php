<?php

/*
 * Each FoodOrder represents a food order with its details
 */

class FoodOrder {

    private $id; // Primary key
    private $requestor_person_id; // Foreign Key
    private $approver_person_id; // Foreign Key
    private $order_status; // Status of the order (e.g., pending, approved, rejected, completed)
    private $submission_date; // Date when the order was submitted
    private $approval_date; // Date when the order was approved or rejected
    private $fulfillment_date; // Date when the order was fulfilled

    /*
        Constructor for the FoodOrder class
        Parameters:
        - $id: The ID of the food order.
        - $requestor_person_id: The ID of the person who requested the order.
        - $approver_person_id: The ID of the person who approved or rejected the order (can be null).
        - $order_status: The status of the order (e.g., pending, approved, rejected, completed).
        - $submission_date: The date when the order was submitted.
        - $approval_date: The date when the order was approved or rejected (can be null).
        - $fulfillment_date: The date when the order was fulfilled (can be null).
    */
    function __construct($id, $requestor_person_id, $approver_person_id, $order_status, $submission_date, $approval_date = null, $fulfillment_date = null) {
        $this->id = $id;
        $this->requestor_person_id = $requestor_person_id;
        $this->approver_person_id = $approver_person_id;
        $this->order_status = $order_status;
        $this->submission_date = $submission_date;
        $this->approval_date = $approval_date;
        $this->fulfillment_date = $fulfillment_date;
    }

    /*
        Getters for the FoodOrder class properties
    */
    function getId() {
        return $this->id;
    }

    function getRequestorPersonId() {
        return $this->requestor_person_id;
    }

    function getApproverPersonId() {
        return $this->approver_person_id;
    }

    function getStatus() {
        return $this->order_status;
    }

    function getSubmissionDate() {
        return $this->submission_date;
    }

    function getApprovalDate() {
        return $this->approval_date;
    }

    function getFulfillmentDate() {
        return $this->fulfillment_date;
    }

    /*
        Setters for the FoodOrder class properties
    */
    function setStatus($order_status) {
        $this->order_status = $order_status;
    }

    function setApprovalDate($approval_date) {
        $this->approval_date = $approval_date;
    }

    function setFulfillmentDate($fulfillment_date) {
        $this->fulfillment_date = $fulfillment_date;
    }
}