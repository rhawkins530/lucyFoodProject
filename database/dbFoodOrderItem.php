<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/FoodOrderItem.php');

/*
    Add a new food order item
    Parameters:
    - $orderId: The ID of the food order to which the item belongs.
    - $itemCategoryId: The ID of the item category.
    - $quantityRequested: The quantity of the item requested in the order.
    - $quantityDelivered: The quantity of the item delivered in the order.
    Returns:
    - true if the item was successfully added, false otherwise.
*/

function addFoodOrderItem($orderId, $itemCategoryId, $quantityRequested, $quantityDelivered) {
    $con = connect();
    $orderId = (int)$orderId;
    $itemCategoryId = (int)$itemCategoryId;
    $quantityRequested = (int)$quantityRequested;
    $quantityDelivered = (int)$quantityDelivered;

    $query = "INSERT INTO dbfoodorderitem
                (order_id, item_category_id, quantity_requested, quantity_delivered)
            VALUES ($orderId, $itemCategoryId, $quantityRequested, $quantityDelivered)";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

/*
    Get all food order items for a specific order
    Parameters:
    - $orderId: The ID of the food order for which to retrieve items.
    Returns:
    - An array of FoodOrderItem objects representing the items in the order.
*/
function getFoodOrderItemsByOrderId($orderId) {
    $con = connect();
    $orderId = (int)$orderId;
    $query = "SELECT * FROM dbfoodorderitem WHERE order_id = $orderId";
    $result = mysqli_query($con, $query);
    $items = [];
    
    while ($row = mysqli_fetch_assoc($result)) { // For each row in the result set, create a FoodOrderItem object and add it to the items array
        $items[] = new FoodOrderItem(
            (int)$row['id'],
            (int)$row['order_id'],
            (int)$row['item_category_id'],
            (int)$row['quantity_requested'],
            (int)$row['quantity_delivered']
        );
    }
    
    mysqli_close($con);
    return $items;
}

/*
    Get a food order item by its ID
    Parameters:
    - $id: The ID of the food order item to retrieve.
    Returns:
    - A FoodOrderItem object if found, null otherwise.
*/
function getFoodOrderItemById($id) {
    $con = connect();
    $id = (int)$id;
    $query = "SELECT * FROM dbfoodorderitem WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    
    if ($row) { // If a row is found, create and return a FoodOrderItem object
        return new FoodOrderItem(
            (int)$row['id'],
            (int)$row['order_id'],
            (int)$row['item_category_id'],
            (int)$row['quantity_requested'],
            (int)$row['quantity_delivered']
        );
    } else {
        return null;
    }
}

/*
    Update the quantity delivered for a specific food order item
    Parameters:
    - $id: The ID of the food order item to update.
    - $quantityDelivered: The new quantity delivered for the item.
    Returns:
    - True if the update was successful, false otherwise.
*/
function updateFoodOrderItemQuantityDelivered($id, $quantityDelivered) {
    $con = connect();
    $id = (int)$id;
    $quantityDelivered = (int)$quantityDelivered;
    $query = "UPDATE dbfoodorderitem SET quantity_delivered = $quantityDelivered WHERE id = $id";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}