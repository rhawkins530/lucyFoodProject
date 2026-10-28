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
    private $pdf_submission_date; // Date but not time of submission written on order PDF
    private $pantry_name; // Name of the pantry associated with the order
    private $pantry_address; // Address of the pantry associated with the order
    private $point_of_contact; // Name of the point of contact for the order
    private $poc_ph_number; // Phone number of the point of contact for the order
    private $secondary_poc; // Name of the secondary point of contact for the order
    private $secondary_poc_ph_num; // Phone number of the secondary point of contact for the order
    private $requested_delivery_date; // Date when the delivery was requested
    private $requested_delivery_time; // Time when the delivery was requested
    private $special_instructions; // Special instructions for the order
    private $non_pd_slp_box_wt; // Weight of the non-PD SLP box for the order
    private $non_pd_produce_wt; // Weight of the non-PD produce for the order
    private $non_pd_meat_wt; // Weight of the non-PD meat for the order
    private $non_pd_bakery; // Weight of the non-PD bakery for the order
    private $non_pd_dairy; // Weight of the non-PD dairy for the order
    private $non_pd_other; // Weight of the non-PD other items for the order
    private $pd_meat_wt; // Weight of the PD meat for the order
    private $pd_dairy_wt; // Weight of the PD dairy for the order
    private $pd_deli_wt; // Weight of the PD deli for the order
    private $pd_bakery_wt; // Weight of the PD bakery for the order
    private $pd_produce_wt; // Weight of the PD produce for the order
    private $pd_non_perish_wt; // Weight of the PD non-perishable items for the order
    private $pd_prep_deli_wt; // Weight of the PD prep/deli for the order

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
    function __construct($id, $requestor_person_id, $approver_person_id, $order_status, $submission_date, $approval_date = null, $fulfillment_date = null,
    $pdf_submission_date = null, $pantry_name = null, $pantry_address = null, $point_of_contact = null, $poc_ph_number = null, $secondary_poc = null,
    $secondary_poc_ph_num = null, $requested_delivery_date = null, $requested_delivery_time = null, $special_instructions = null, $non_pd_slp_box_wt = null,
    $non_pd_produce_wt = null, $non_pd_meat_wt = null, $non_pd_bakery = null, $non_pd_dairy = null, $non_pd_other = null, $pd_meat_wt = null, $pd_dairy_wt = null,
    $pd_deli_wt = null, $pd_bakery_wt = null, $pd_produce_wt = null, $pd_non_perish_wt = null, $pd_prep_deli_wt = null) {
        $this->id = $id;
        $this->requestor_person_id = $requestor_person_id;
        $this->approver_person_id = $approver_person_id;
        $this->order_status = $order_status;
        $this->submission_date = $submission_date;
        $this->approval_date = $approval_date;
        $this->fulfillment_date = $fulfillment_date;
        $this->pdf_submission_date = $pdf_submission_date;
        $this->pantry_name = $pantry_name;
        $this->pantry_address = $pantry_address;
        $this->point_of_contact = $point_of_contact;
        $this->poc_ph_number = $poc_ph_number;
        $this->secondary_poc = $secondary_poc;
        $this->secondary_poc_ph_num = $secondary_poc_ph_num;
        $this->requested_delivery_date = $requested_delivery_date;
        $this->requested_delivery_time = $requested_delivery_time;
        $this->special_instructions = $special_instructions;
        $this->non_pd_slp_box_wt = $non_pd_slp_box_wt;
        $this->non_pd_produce_wt = $non_pd_produce_wt;
        $this->non_pd_meat_wt = $non_pd_meat_wt;
        $this->non_pd_bakery = $non_pd_bakery;
        $this->non_pd_dairy = $non_pd_dairy;
        $this->non_pd_other = $non_pd_other;
        $this->pd_meat_wt = $pd_meat_wt;
        $this->pd_dairy_wt = $pd_dairy_wt;
        $this->pd_deli_wt = $pd_deli_wt;
        $this->pd_bakery_wt = $pd_bakery_wt;
        $this->pd_produce_wt = $pd_produce_wt;
        $this->pd_non_perish_wt = $pd_non_perish_wt;
        $this->pd_prep_deli_wt = $pd_prep_deli_wt;
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

    function getPdfSubmissionDate() {
        return $this->pdf_submission_date;
    }

    function getPantryName() {
        return $this->pantry_name;
    }

    function getPantryAddress() {
        return $this->pantry_address;
    }

    function getPointOfContact() {
        return $this->point_of_contact;
    }

    function getPocPhNumber() {
        return $this->poc_ph_number;
    }

    function getSecondaryPoc() {
        return $this->secondary_poc;
    }

    function getSecondaryPocPhNum() {
        return $this->secondary_poc_ph_num;
    }

    function getRequestedDeliveryDate() {
        return $this->requested_delivery_date;
    }

    function getRequestedDeliveryTime() {
        return $this->requested_delivery_time;
    }

    function getSpecialInstructions() {
        return $this->special_instructions;
    }

    function getNonPdSlpBoxWt() {
        return $this->non_pd_slp_box_wt;
    }

    function getNonPdProduceWt() {
        return $this->non_pd_produce_wt;
    }

    function getNonPdMeatWt() {
        return $this->non_pd_meat_wt;
    }

    function getNonPdBakery() {
        return $this->non_pd_bakery;
    }

    function getNonPdDairy() {
        return $this->non_pd_dairy;
    }

    function getNonPdOther() {
        return $this->non_pd_other;
    }

    function getPdMeatWt() {
        return $this->pd_meat_wt;
    }

    function getPdDairyWt() {
        return $this->pd_dairy_wt;
    }

    function getPdDeliWt() {
        return $this->pd_deli_wt;
    }

    function getPdBakeryWt() {
        return $this->pd_bakery_wt;
    }

    function getPdProduceWt() {
        return $this->pd_produce_wt;
    }

    function getPdNonPerishWt() {
        return $this->pd_non_perish_wt;
    }

    function getPdPrepDeliWt() {
        return $this->pd_prep_deli_wt;
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

    function setPdfSubmissionDate($pdf_submission_date) {
        $this->pdf_submission_date = $pdf_submission_date;
    }

    function setPantryName($pantry_name) {
        $this->pantry_name = $pantry_name;
    }

    function setPantryAddress($pantry_address) {
        $this->pantry_address = $pantry_address;
    }

    function setPointOfContact($point_of_contact) {
        $this->point_of_contact = $point_of_contact;
    }

    function setPocPhNumber($poc_ph_number) {
        $this->poc_ph_number = $poc_ph_number;
    }

    function setSecondaryPoc($secondary_poc) {
        $this->secondary_poc = $secondary_poc;
    }

    function setSecondaryPocPhNum($secondary_poc_ph_num) {
        $this->secondary_poc_ph_num = $secondary_poc_ph_num;
    }

    function setRequestedDeliveryDate($requested_delivery_date) {
        $this->requested_delivery_date = $requested_delivery_date;
    }

    function setRequestedDeliveryTime($requested_delivery_time) {
        $this->requested_delivery_time = $requested_delivery_time;
    }

    function setSpecialInstructions($special_instructions) {
        $this->special_instructions = $special_instructions;
    }

    function setNonPdSlpBoxWt($non_pd_slp_box_wt) {
        $this->non_pd_slp_box_wt = $non_pd_slp_box_wt;
    }

    function setNonPdProduceWt($non_pd_produce_wt) {
        $this->non_pd_produce_wt = $non_pd_produce_wt;
    }

    function setNonPdMeatWt($non_pd_meat_wt) {
        $this->non_pd_meat_wt = $non_pd_meat_wt;
    }

    function setNonPdBakery($non_pd_bakery) {
        $this->non_pd_bakery = $non_pd_bakery;
    }

    function setNonPdDairy($non_pd_dairy) {
        $this->non_pd_dairy = $non_pd_dairy;
    }

    function setNonPdOther($non_pd_other) {
        $this->non_pd_other = $non_pd_other;
    }

    function setPdMeatWt($pd_meat_wt) {
        $this->pd_meat_wt = $pd_meat_wt;
    }

    function setPdDairyWt($pd_dairy_wt) {
        $this->pd_dairy_wt = $pd_dairy_wt;
    }

    function setPdDeliWt($pd_deli_wt) {
        $this->pd_deli_wt = $pd_deli_wt;
    }

    function setPdBakeryWt($pd_bakery_wt) {
        $this->pd_bakery_wt = $pd_bakery_wt;
    }

    function setPdProduceWt($pd_produce_wt) {
        $this->pd_produce_wt = $pd_produce_wt;
    }

    function setPdNonPerishWt($pd_non_perish_wt) {
        $this->pd_non_perish_wt = $pd_non_perish_wt;
    }

    function setPdPrepDeliWt($pd_prep_deli_wt) {
        $this->pd_prep_deli_wt = $pd_prep_deli_wt;
    }
}