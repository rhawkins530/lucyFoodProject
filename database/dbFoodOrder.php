<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/FoodOrder.php');
include_once(dirname(__FILE__).'/dbInventory.php');


/*
    Add a new food order
    Parameters:
    - $requestorId: The ID of the person requesting the food order.
    - $foodOrderItems: An array of tuples, where each tuple contains (item_category_id, quantity_requested) for each item in the order.
    Returns:
    - true if the order and all its items were successfully added, false otherwise.
*/
function addFoodOrder($requestorId, $foodOrderItems = []) {
    try {
        $con = connect();
        $requestorId = (int)$requestorId;
        $order_status = "pending"; // Default status for a new order

        // Start a transaction
        $con->begin_transaction();

        // Insert the food order
        $query = "INSERT INTO dbfoodorder
                    (requestor_person_id, order_status, submission_date)
                VALUES ($requestorId, '$order_status', NOW())";
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
                            (order_id, item_category_id, quantity_requested)
                        VALUES ($orderId, $itemCategoryId, $quantityRequested)";
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
    Add a new individual food order. Individual orders don't need an admin to approve them,
    so if there is enough inventory the order is saved as approved and its boxes are removed from inventory.
    Parameters:
    - $requestorId: The ID of the person requesting the food order.
    - $foodOrderItems: An array of tuples, where each tuple contains (item_category_id, quantity_requested) for each item in the order.
    Returns:
    - An empty array if the order was saved and the inventory was updated.
    - An array of item_category_id => quantity in inventory for each item without enough inventory.
      Nothing is saved in this case.
    - false if the order couldn't be saved.
*/
function addIndividualFoodOrder($requestorId, $foodOrderItems = []) {
    // Orders use item_category_id, but the inventory functions use the dbinventory row id
    $inventoryIds = [];
    foreach (getAllInventoryItems() as $inventoryItem) {
        $inventoryIds[$inventoryItem->getItemCategoryId()] = $inventoryItem->getId();
    }

    // Check that there is enough inventory for every item before saving anything
    $insufficient = [];
    foreach ($foodOrderItems as $item) {
        $itemCategoryId = (int)$item[0];
        $quantityRequested = (int)$item[1];
        $inventoryId = $inventoryIds[$itemCategoryId] ?? null;
        $currentQuantity = $inventoryId === null ? 0 : (int)getInventoryQuantity($inventoryId);
        if ($quantityRequested > $currentQuantity) {
            $insufficient[$itemCategoryId] = $currentQuantity;
        }
    }
    if (!empty($insufficient)) {
        return $insufficient;
    }

    try {
        $con = connect();
        $requestorId = (int)$requestorId;
        $order_status = "approved"; // Individual orders are approved as soon as they're submitted

        // Start a transaction
        $con->begin_transaction();

        // Insert the food order
        $query = "INSERT INTO dbfoodorder
                    (requestor_person_id, order_status, submission_date, fulfillment_date)
                VALUES ($requestorId, '$order_status', NOW(), NOW())";
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
                            (order_id, item_category_id, quantity_requested)
                        VALUES ($orderId, $itemCategoryId, $quantityRequested)";
            $okItem = mysqli_query($con, $queryItem);

            if (!$okItem) { // If inserting an item fails, rollback the transaction and return false
                $con->rollback();
                mysqli_close($con);
                return false;
            }
        }

        $okCommit = $con->commit();
        mysqli_close($con);
        if (!$okCommit) {
            return false;
        }
    }
    catch (Exception $e) {
        // Handle exception, rollback transaction if needed
        if (isset($con)) {
            $con->rollback();
            mysqli_close($con);
        }
        return false; // Return false in case of an exception
    }

    // The order was saved, so take the boxes out of inventory
    foreach ($foodOrderItems as $item) {
        changeInventoryQuantity($inventoryIds[(int)$item[0]], -(int)$item[1]);
    }
    return [];
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
            $row['fulfillment_date']
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