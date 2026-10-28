<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/FoodOrder.php');


/*
    Add a new food order
    Parameters:
    - $requestorId: The ID of the person requesting the food order.
    - $foodOrderItems: An array of tuples, where each tuple contains (item_category_id, quantity_requested) for each item in the order.
    - $pdfSubmissionDate: The date when the PDF was submitted. (Optional, defaults to null)
    - $pantry_name: The name of the pantry. (Optional, defaults to null)
    - $pantry_address: The address of the pantry. (Optional, defaults to null)
    - $point_of_contact: The name of the point of contact. (Optional, defaults to null)
    - $poc_ph_number: The phone number of the point of contact. (Optional, defaults to null)
    - $secondary_poc: The name of the secondary point of contact. (Optional, defaults to null)
    - $secondary_poc_ph_num: The phone number of the secondary point of contact. (Optional, defaults to null)
    - $requested_delivery_date: The requested delivery date. (Optional, defaults to null)
    - $requested_delivery_time: The requested delivery time. (Optional, defaults to null)
    - $special_instructions: Any special instructions for the order. (Optional, defaults to null)
    - $non_pd_slp_box_wt: The weight of non-perishable SLP boxes. (Optional, defaults to null)
    - $non_pd_produce_wt: The weight of non-perishable produce. (Optional, defaults to null)
    - $non_pd_meat_wt: The weight of non-perishable meat. (Optional, defaults to null)
    - $non_pd_bakery: The weight of non-perishable bakery items. (Optional, defaults to null)
    - $non_pd_dairy: The weight of non-perishable dairy items. (Optional, defaults to null)
    - $non_pd_other: The weight of other non-perishable items. (Optional, defaults to null)
    - $pd_meat_wt: The weight of perishable meat. (Optional, defaults to null)
    - $pd_dairy_wt: The weight of perishable dairy items. (Optional, defaults to null)
    - $pd_deli_wt: The weight of perishable deli items. (Optional, defaults to null)
    - $pd_bakery_wt: The weight of perishable bakery items. (Optional, defaults to null)
    - $pd_produce_wt: The weight of perishable produce. (Optional, defaults to null)
    - $pd_non_perish_wt: The weight of perishable non-perishable items. (Optional, defaults to null)
    - $pd_prep_deli_wt: The weight of perishable prep/deli items. (Optional, defaults to null)
    Returns:
    - true if the order and all its items were successfully added, false otherwise.
*/
function addFoodOrder($requestorId, $pdfSubmissionDate = null, $pantry_name = null, $pantry_address = null, $point_of_contact = null, $poc_ph_number = null,
$secondary_poc = null, $secondary_poc_ph_num = null, $requested_delivery_date = null, $requested_delivery_time = null, $special_instructions = null, $non_pd_slp_box_wt = null,
$non_pd_produce_wt = null, $non_pd_meat_wt = null, $non_pd_bakery = null, $non_pd_dairy = null, $non_pd_other = null, $pd_meat_wt = null, $pd_dairy_wt = null,
$pd_deli_wt = null, $pd_bakery_wt = null, $pd_produce_wt = null, $pd_non_perish_wt = null, $pd_prep_deli_wt = null, $foodOrderItems = []) {
    try {
        $con = connect();
        $requestorId = (int)$requestorId;
        $order_status = "pending"; // Default status for a new order

        // Start a transaction
        $con->begin_transaction();

        // Insert the food order
        $query = "INSERT INTO dbfoodorder
                    (requestor_person_id, order_status, submission_date, pdf_submission_date, pantry_name, pantry_address, point_of_contact, poc_ph_number,
                    secondary_poc, secondary_poc_ph_num, requested_delivery_date, requested_delivery_time, special_instructions, non_pd_slp_box_wt,
                    non_pd_produce_wt, non_pd_meat_wt, non_pd_bakery, non_pd_dairy, non_pd_other, pd_meat_wt, pd_dairy_wt,
                    pd_deli_wt, pd_bakery_wt, pd_produce_wt, pd_non_perish_wt, pd_prep_deli_wt)
                VALUES ($requestorId, '$order_status', NOW(), '$pdfSubmissionDate', '$pantry_name', '$pantry_address', '$point_of_contact', '$poc_ph_number',
                    '$secondary_poc', '$secondary_poc_ph_num', '$requested_delivery_date', '$requested_delivery_time', '$special_instructions', '$non_pd_slp_box_wt',
                    '$non_pd_produce_wt', '$non_pd_meat_wt', '$non_pd_bakery', '$non_pd_dairy', '$non_pd_other', '$pd_meat_wt', '$pd_dairy_wt',
                    '$pd_deli_wt', '$pd_bakery_wt', '$pd_produce_wt', '$pd_non_perish_wt', '$pd_prep_deli_wt')";
        $okOrder = mysqli_query($con, $query);

        if (!$okOrder) { // If inserting the order fails, rollback the transaction and return false
            $con->rollback();
            mysqli_close($con);
            return false;
        }

        $orderId = mysqli_insert_id($con); // Get the ID of the newly inserted order

        // Insert food order items
        foreach ($foodOrderItems as $item) {
            $itemCategoryId = (int)$item[0];
            $quantityRequested = (int)$item[1];

            $queryItem = "INSERT INTO dbfoodorderitem
                            (order_id, item_category_id, quantity_requested, quantity_delivered)
                        VALUES ($orderId, $itemCategoryId, $quantityRequested, null)";
            $okItem = mysqli_query($con, $queryItem);

            if (!$okItem) { // If inserting an item fails, rollback the transaction and return false
                $con->rollback();
                mysqli_close($con);
                return false;
            }
        }

        $okCommit = $con->commit();
        mysqli_close($con);

        return $okCommit; // Return true if the transaction was committed successfully, false otherwise
    }
    catch (Exception $e) {
        // Handle exception, rollback transaction if needed
        if (isset($con)) {
            $con->rollback();
            mysqli_close($con);
        }
        return false; // Return false in case of an exception
    }
}

/*
    Get a food order by its ID
    Parameters:
    - $id: The ID of the food order to retrieve.
    Returns:
    - A FoodOrder object if found, null otherwise.
*/
function getFoodOrderById($id) {
    $con = connect();
    $id = (int)$id;
    $query = "SELECT * FROM dbfoodorder WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    
    if ($row) { // If a row is found, create and return a FoodOrder object
        return new FoodOrder(
            (int)$row['id'],
            (int)$row['requestor_person_id'],
            is_null($row['approver_person_id']) ? null : (int)$row['approver_person_id'],
            $row['order_status'],
            $row['submission_date'],
            $row['approval_date'],
            $row['fulfillment_date'],
            $row['pdf_submission_date'],
            $row['pantry_name'],
            $row['pantry_address'],
            $row['point_of_contact'],
            $row['poc_ph_number'],
            $row['secondary_poc'],
            $row['secondary_poc_ph_num'],
            $row['requested_delivery_date'],
            $row['requested_delivery_time'],
            $row['special_instructions'],
            $row['non_pd_slp_box_wt'],
            $row['non_pd_produce_wt'],
            $row['non_pd_meat_wt'],
            $row['non_pd_bakery'],
            $row['non_pd_dairy'],
            $row['non_pd_other'],
            $row['pd_meat_wt'],
            $row['pd_dairy_wt'],
            $row['pd_deli_wt'],
            $row['pd_bakery_wt'],
            $row['pd_produce_wt'],
            $row['pd_non_perish_wt'],
            $row['pd_prep_deli_wt']
        );
    } else {
        return null;
    }
}

/*
    Approve a food order by its ID
    Parameters:
    - $id: The ID of the food order to approve.
    - $approverId: The ID of the person approving the order.
    Returns:
    - true if the order was successfully approved, false otherwise.
*/
function approveFoodOrder($id, $approverId) {
    $con = connect();
    $id = (int)$id;
    $approverId = (int)$approverId;

    // Update the order status to 'approved', set the approver ID, and set the approval date to current timestamp
    $query = "UPDATE dbfoodorder SET order_status = 'approved', approver_person_id = $approverId, approval_date = NOW() WHERE id = $id";

    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Reject a food order by its ID
    Parameters:
    - $id: The ID of the food order to reject.
    - $approverId: The ID of the person rejecting the order.
    Returns:
    - true if the order was successfully rejected, false otherwise.
*/
function rejectFoodOrder($id, $approverId) {
    $con = connect();
    $id = (int)$id;
    $approverId = (int)$approverId;

    // Update the order status to 'rejected', set the approver ID, and set the approval date to current timestamp
    $query = "UPDATE dbfoodorder SET order_status = 'rejected', approver_person_id = $approverId, approval_date = NOW() WHERE id = $id";

    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Fulfill a food order by its ID
    Parameters:
    - $id: The ID of the food order to fulfill.
    Returns:
    - true if the order was successfully fulfilled, false otherwise.
*/
function fulfillFoodOrder($id) {
    $con = connect();
    $id = (int)$id;

    // Update the order status to 'fulfilled' and set the fulfillment date to current timestamp
    $query = "UPDATE dbfoodorder SET order_status = 'fulfilled', fulfillment_date = NOW() WHERE id = $id";

    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Get all food orders
    Returns:
    - An array of FoodOrder objects representing all food orders in the database.
*/
function getAllFoodOrders() {
    $con = connect();
    $query = "SELECT * FROM dbfoodorder";
    $result = mysqli_query($con, $query);
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) { // Create a FoodOrder object for each row and add it to the orders array
        $orders[] = new FoodOrder(
            (int)$row['id'],
            (int)$row['requestor_person_id'],
            is_null($row['approver_person_id']) ? null : (int)$row['approver_person_id'],
            $row['order_status'],
            $row['submission_date'],
            $row['approval_date'],
            $row['fulfillment_date']
        );
    }
    
    mysqli_close($con);
    return $orders;
}