<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/UnsortedCategory.php');
include_once(dirname(__FILE__).'/../domain/UnsortedInventory.php');
include_once(dirname(__FILE__).'/dbItemCategory.php');
include_once(dirname(__FILE__).'/dbNotifications.php');

/*
    Adds a new unsorted inventory item with its associated unsorted item categories in one transaction
*/
function addUnsortedInventory($weight, $crate_type, $item_categories) {
    $con = connect();
    try {
        $con->begin_transaction();
        $stmt = $con->prepare("INSERT INTO dbunsorted (weight, crate_type, state) VALUES (?, ?, 'received')");
        $stmt->bind_param("ds", $weight, $crate_type);
        $stmt->execute();
        $unsorted_id = $stmt->insert_id;
        $stmt->close();

        foreach ($item_categories as $item_category_id) {
            $stmt = $con->prepare("INSERT INTO dbunsortedcategory (unsorted_id, item_category_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $unsorted_id, $item_category_id);
            $stmt->execute();
            $stmt->close();
        }

        $con->commit();
    }
    catch (Exception $e) {
        throw new Exception("Failed to add unsorted inventory: " . $e->getMessage());
    }
    finally {
        $con->close();
    }
}

function getAllUnsortedInventories() {
    $con = connect();
    $stmt = $con->prepare("SELECT * FROM dbunsorted");
    $stmt->execute();
    $result = $stmt->get_result();
    $unsorted_inventories = [];
    while ($row = $result->fetch_assoc()) {
        $unsorted_inventories[] = new UnsortedInventory($row['id'], $row['weight'], $row['crate_type'], $row['state']);
    }
    $stmt->close();
    $con->close();
    return $unsorted_inventories;
}

function getUnsortedInventoryById($id) {
    $con = connect();
    $stmt = $con->prepare("SELECT * FROM dbunsorted WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $unsorted_inventory = new UnsortedInventory($row['id'], $row['weight'], $row['crate_type'], $row['state']);
    } else {
        throw new Exception("Unsorted inventory with ID $id not found.");
    }
    $stmt->close();
    $con->close();
    return $unsorted_inventory;
}

function getItemCategoriesByUnsortedId($unsorted_id) {
    $con = connect();
    $stmt = $con->prepare("SELECT * FROM dbunsortedcategory WHERE unsorted_id = ?");
    $stmt->bind_param("i", $unsorted_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_categories = [];
    while ($row = $result->fetch_assoc()) {
        $item_categories[] = new UnsortedCategory($row['id'], $row['unsorted_id'], $row['item_category_id']);
    }
    $stmt->close();
    $con->close();
    return $item_categories;
}

function updateUnsortedInventoryState($id, $new_state) {
    $con = connect();
    $stmt = $con->prepare("UPDATE dbunsorted SET state = ? WHERE id = ?");
    $stmt->bind_param("si", $new_state, $id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update unsorted inventory state: " . $stmt->error);
    }
    $stmt->close();
    $con->close();
}