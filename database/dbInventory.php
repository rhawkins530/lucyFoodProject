<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/InventoryItem.php');
include_once(dirname(__FILE__).'/dbNotifications.php');

function addInventoryItem($categoryId, $categoryName, $quantity, $minimum = 0, $allocated = 0) {
    $con = connect();
    $categoryId   = (int)$categoryId;
    $quantity     = (int)$quantity;
    $minimum      = (int)$minimum;
    $allocated    = (int)$allocated;
    $categoryName = mysqli_real_escape_string($con, $categoryName);

    $query = "INSERT INTO dbinventory
                (item_category_id, category_name, current_quantity, minimum_quantity, allocated_quantity)
            VALUES ($categoryId, '$categoryName', $quantity, $minimum, $allocated)";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

function addToInventory($categoryId, $quantity) {
    $con = connect();
    $categoryId   = (int)$categoryId;
    $quantity     = (int)$quantity;

    $query = "INSERT INTO dbinventory
                (id, current_quantity)
            VALUES ($categoryId, $quantity))";
    $ok = mysqli_query($con, $query);
    mysqli_close($con);
    return $ok;
}

function getInventoryIdByCategory($categoryId) {
    $con = connect();
    $categoryId = (int)$categoryId;
    $query = "SELECT id FROM dbinventory WHERE item_category_id = $categoryId ORDER BY id LIMIT 1";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row ? (int)$row['id'] : null;
}

/*
    Add a new item
*/
function addCategory($name, $minimum, $allocated) {
    $con=connect();
    mysqli_query($con,'INSERT INTO dbinventory (category_name, current_quantity, minimum_quantity, allocated_quantity) VALUES("' .
            $name . '", 
            0 ,"' . 
            $minimum . '","' . 
            $allocated . '");');
    
    $id = mysqli_insert_id($con);
    mysqli_close($con);
    
    return $id;
}

/*
    get item category name
*/
function getCategoryName($id) {
    $con = connect();
    $query = "SELECT category_name FROM dbinventory WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['category_name'];
}

/*
    set item category name
*/
function setCategoryName($id, $name) {
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET category_name = '" . $name . "' WHERE id = $id");
    mysqli_close($con);
    return;
}

/*
    get a row per id
*/
function getItem($id) {
    $con = connect();
    $query = "SELECT * FROM dbinventory WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row  = mysqli_fetch_assoc($result);
    $inventoryItem = new InventoryItem($row['id'], $row['item_category_id'], $row['category_name'], $row['current_quantity'], $row['minimum_quantity'], $row['allocated_quantity']);
    mysqli_close($con);
    return $inventoryItem;
}

/*
    get an item per name
*/
function getItemByName($name) {
    $con = connect();
    $query = "SELECT * FROM dbinventory WHERE category_name = '" . $name . "' ";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return false;
    }
    $row  = mysqli_fetch_assoc($result);
    $inventoryItem = new InventoryItem($row['id'], $row['item_category_id'], $row['category_name'], $row['current_quantity'], $row['minimum_quantity'], $row['allocated_quantity']);
    mysqli_close($con);
    return $inventoryItem;
}


/*
 * get quantity for the inventory item with the given id
 */

function getInventoryQuantity($id) {
    $con = connect();
    $query = "SELECT current_quantity FROM dbinventory WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['current_quantity'];
}

/*
 * add or subtract quantity from the inventory item with the given id
 */

function changeInventoryQuantity($id, $quantity) {
    $before = getStockLevel($id);
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET current_quantity = current_quantity + $quantity WHERE id = $id");
    mysqli_close($con);
    updateLowStockNotifications($id, $before);
    return true;
}

/*
 * set quantity for the inventory item with the given id
 */

function setInventoryQuantity($id, $quantity) {
    $before = getStockLevel($id);
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET current_quantity = $quantity WHERE id = $id");
    mysqli_close($con);
    updateLowStockNotifications($id, $before);
    return true;
}

/*
 * get the name, current quantity and minimum quantity for the inventory item with the given id
 * returns null if there is no item with that id
 */

function getStockLevel($id) {
    $con = connect();
    $id = (int)$id;
    $result = mysqli_query($con, "SELECT category_name, current_quantity, minimum_quantity FROM dbinventory WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row ?: null;
}

/*
 * keep the low stock alerts for the inventory item with the given id up to date, after its
 * quantity or minimum changed. $before is getStockLevel() from before the change.
 * - if the item just dropped below its minimum, notify all admins (only once, not again while it stays low)
 * - if the item is just back at or above its minimum, delete its low stock alert from every admin's bell
 */

function updateLowStockNotifications($id, $before) {
    $after = getStockLevel($id);
    if ($before === null || $after === null) {
        return;
    }
    $minimum = (int)$after['minimum_quantity'];
    $wasLow = (int)$before['current_quantity'] < (int)$before['minimum_quantity'];
    $isLow = (int)$after['current_quantity'] < $minimum;
    if (!$wasLow && $isLow) {
        notifyAllAdmins('Low stock: ' . $after['category_name'] . ' has ' . (int)$after['current_quantity']
            . ' boxes left (minimum ' . $minimum . ')', 'low_stock', $id);
    }
    else if ($wasLow && !$isLow) {
        deleteNotifications('low_stock', $id);
    }
}

/*
 * get minimum quantity for the inventory item with the given id
 */

function getMinimumQuantity($id) {
    $con = connect();
    $query = "SELECT minimum_quantity FROM dbinventory WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['minimum_quantity'];
}


/*
 * set minimum quantity for the inventory item with the given id
 */

function setMinimumQuantity($id, $quantity) {
    $before = getStockLevel($id);
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET minimum_quantity = $quantity WHERE id = $id");
    mysqli_close($con);
    updateLowStockNotifications($id, $before);
    return true;
}

/*
 * get allocated quantity for the inventory item with the given id
 */

function getAllocatedQuantity($id) {
    $con = connect();
    $query = "SELECT allocated_quantity FROM dbinventory WHERE id = $id";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['allocated_quantity'];
}

/*
 * add or subtract from the allocated quantity for the inventory item with the given id
 */

function changeAllocatedQuantity($id, $quantity) {
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET allocated_quantity = allocated_quantity + $quantity WHERE id = $id");
    mysqli_close($con);
    return true;
}

/*
 * set allocated quantity for the inventory item with the given id
 */

function setAllocatedQuantity($id, $quantity) {
    $con = connect();
    mysqli_query($con, "UPDATE dbinventory SET allocated_quantity = $quantity WHERE id = $id");
    mysqli_close($con);
    return true;
}

/*
 * get all inventory items from the database and return them as an array of InventoryItem objects
 */

function getAllInventoryItems() {
    $con = connect();
    $query = "SELECT * FROM dbinventory ORDER BY id";
    $result = mysqli_query($con, $query);
    $inventoryItems = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $inventoryItem = new InventoryItem($row['id'], $row['item_category_id'], $row['category_name'], $row['current_quantity'], $row['minimum_quantity'], $row['allocated_quantity']);
        $inventoryItems[] = $inventoryItem;
    }
    mysqli_close($con);
    return $inventoryItems;
}

/*
 * get all inventory items from the database and return them as an array of InventoryItem objects sorted by user-defined order
 */

function getAllInventoryItemsSorted($sqlOrder, $sqlDirection = 'ASC') {

    $allowed_orders = array('id', 'item_category_id', 'category_name', 'current_quantity', 'minimum_quantity', 'allocated_quantity', "current_quantity - allocated_quantity");
    if (!in_array($sqlOrder, $allowed_orders)) {
        throw new Exception("Invalid order parameter");
    }

    $sqlDirection = strtoupper($sqlDirection);
    if ($sqlDirection !== 'ASC' && $sqlDirection !== 'DESC') {
        throw new Exception("Invalid direction parameter");
    }

    $con = connect();
    $query = "SELECT * FROM dbinventory ORDER BY $sqlOrder $sqlDirection";
    $result = mysqli_query($con, $query);
    $inventoryItems = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $inventoryItem = new InventoryItem($row['id'], $row['item_category_id'], $row['category_name'], $row['current_quantity'], $row['minimum_quantity'], $row['allocated_quantity']);
        $inventoryItems[] = $inventoryItem;
    }
    mysqli_close($con);
    return $inventoryItems;
}

/*
 * get inventory items under minimum quantity from the database and return them as an array of InventoryItem objects
 */

function getInventoryItemsUnderMinimum() {
    $con = connect();
    $query = "SELECT * FROM dbinventory WHERE current_quantity < minimum_quantity ORDER BY category_name";
    $result = mysqli_query($con, $query);
    $inventoryItems = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $inventoryItem = new InventoryItem($row['id'], $row['item_category_id'], $row['category_name'], $row['current_quantity'], $row['minimum_quantity'], $row['allocated_quantity']);
        $inventoryItems[] = $inventoryItem;
    }
    mysqli_close($con);
    return $inventoryItems;
}