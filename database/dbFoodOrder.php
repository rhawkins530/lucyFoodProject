<?php

include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/FoodOrder.php');
include_once(dirname(__FILE__).'/dbInventory.php');
include_once(dirname(__FILE__).'/dbNotifications.php');
include_once(dirname(__FILE__).'/dbFoodOrderItem.php');
include_once(dirname(__FILE__).'/dbItemCategory.php');
require_once(dirname(__FILE__).'/../vendor/autoload.php');

use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfAcroFormReader;
use PhpPdf\Reader\PdfAcroFormFiller;

// Associative array of item category name => pdf field name for quantity requested
const QUANTITYREQUESTEDFIELDS = [
    'Canned Pasta' => 'box_request_canned_pasta',
    'Soup' => 'box_request_soup',
    'Pasta Sauce' => 'box_request_pasta_sauce',
    'Canned Fruit (Mixed)' => 'box_request_canned_fruit',
    'Baking Goods' => 'box_request_baking_good',
    'Meal Helpers' => 'box_request_meal_helpers',
    'Macaroni & Cheese' => 'box_request_mac_and_cheese',
    'Dried Pasta' => 'box_request_dry_pasta',
    'Condiments' => 'box_request_condiments',
    'Dark Beans' => 'box_request_dark_beans',
    'Snacks/Cookies' => 'box_request_snack_cookies',
    'Cereal/Oatmeal' => 'box_request_cereal_and_oatmeal',
    'Cooking Oil' => 'box_request_oil',
    'Jelly' => 'box_request_jelly',
    'Baked Beans' => 'box_request_baked_beans',
    'Peanut Butter' => 'box_request_peanut_butter',
    'Canned Meat' => 'box_request_canned_meat',
    'Juice' => 'box_request_juice_and_beverage',
    'Canned Tuna' => 'box_request_tuna',
    'Vegetable' => 'box_request_vegetable',
    'Tomato Product' => 'box_request_tomato_product',
    'Tortilla' => 'box_request_tortilla',
    'Rice' => 'box_request_bagged_rice',
    'Bagged Dry Bean' => 'box_request_bagged_dry_bean'
    ];
    // Associateve array of item category name => pdf field name for quantity delivered
const QUANTITYDELIVEREDFIELDS = [
    'Canned Pasta' => 'box_deliver_canned_pasta',
    'Soup' => 'box_deliver_soup',
    'Pasta Sauce' => 'box_deliver_pasta_sauce',
    'Canned Fruit (Mixed)' => 'box_deliver_canned_fruit',
    'Baking Goods' => 'box_deliver_baking_good',
    'Meal Helpers' => 'box_deliver_meal_helpers',
    'Macaroni & Cheese' => 'box_deliver_mac_and_cheese',
    'Dried Pasta' => 'box_deliver_dry_pasta',
    'Condiments' => 'box_deliver_condiments',
    'Dark Beans' => 'box_deliver_dark_beans',
    'Snacks/Cookies' => 'box_deliver_snack_cookies',
    'Cereal/Oatmeal' => 'box_deliver_cereal_and_oatmeal',
    'Cooking Oil' => 'box_deliver_oil',
    'Jelly' => 'box_deliver_jelly',
    'Baked Beans' => 'box_deliver_baked_beans',
    'Peanut Butter' => 'box_deliver_peanut_butter',
    'Canned Meat' => 'box_deliver_canned_meat',
    'Juice' => 'box_deliver_juice_and_beverage',
    'Canned Tuna' => 'box_deliver_tuna',
    'Vegetable' => 'box_deliver_vegetable',
    'Tomato Product' => 'box_deliver_tomato_product',
    'Tortilla' => 'box_deliver_tortilla',
    'Rice' => 'box_deliver_bagged_rice',
    'Bagged Dry Bean' => 'box_deliver_dry_bean'
];
    // Note: If food category names change in the database the associative arrays need to be changed.

/*
    Turn an optional value into something that can go straight into an SQL query
    Parameters:
    - $con: The database connection, used to escape the value.
    - $value: The value to save.
    Returns:
    - NULL (unquoted) if the value is null or empty, so date, time and number columns accept it.
    - Otherwise the escaped value in single quotes.
*/
function sqlValueOrNull($con, $value) {
    if ($value === null || $value === '') {
        return "NULL";
    }
    return "'" . mysqli_real_escape_string($con, $value) . "'";
}

/*
    Read and check the pantry, contact and delivery details from an order form.
    Used by both createIndivOrder.php and createMultOrder.php.
    Parameters:
    - $input: The submitted fields for one order ($_POST, or one entry of $_POST['orders']).
    - $errorPrefix: Text put in front of each error message, e.g. "Order 2: ". (Optional, defaults to "")
    Returns:
    - ['details' => ..., 'errors' => [...]]
      details uses the parameter names of addFoodOrder() and addIndividualFoodOrder(),
      so it can be passed straight to them with ...$details
*/
function readOrderDetails($input, $errorPrefix = '') {
    // Form field => parameter name, label for error messages,
    // whether the form marks it required, and the column size in dbfoodorder
    $fields = [
        'submission_date'      => ['parameter' => 'pdfSubmissionDate', 'label' => 'Submission date', 'required' => true, 'maxLength' => null],
        'delivery_date'        => ['parameter' => 'requested_delivery_date', 'label' => 'Requested delivery date', 'required' => false, 'maxLength' => null],
        'delivery_time'        => ['parameter' => 'requested_delivery_time', 'label' => 'Requested delivery time', 'required' => false, 'maxLength' => null],
        'pantry_name'          => ['parameter' => 'pantry_name', 'label' => 'Pantry name', 'required' => true, 'maxLength' => 100],
        'pantry_address'       => ['parameter' => 'pantry_address', 'label' => 'Pantry address', 'required' => true, 'maxLength' => 300],
        'poc_name'             => ['parameter' => 'point_of_contact', 'label' => 'Point of contact', 'required' => true, 'maxLength' => 100],
        'poc_phone'            => ['parameter' => 'poc_ph_number', 'label' => 'Point of contact phone', 'required' => true, 'maxLength' => 20],
        'secondary_poc_name'   => ['parameter' => 'secondary_poc', 'label' => 'Secondary point of contact', 'required' => false, 'maxLength' => 100],
        'secondary_poc_phone'  => ['parameter' => 'secondary_poc_ph_num', 'label' => 'Secondary point of contact phone', 'required' => false, 'maxLength' => 20],
        'special_instructions' => ['parameter' => 'special_instructions', 'label' => 'Special instructions', 'required' => false, 'maxLength' => 300],
    ];

    $details = [];
    $errors = [];
    foreach ($fields as $field => $rules) {
        $value = trim($input[$field] ?? '');
        if ($rules['required'] && $value === '') {
            $errors[] = $errorPrefix . $rules['label'] . " is required";
        }
        else if ($rules['maxLength'] !== null && strlen($value) > $rules['maxLength']) {
            $errors[] = $errorPrefix . $rules['label'] . " must be " . $rules['maxLength'] . " characters or fewer";
        }
        $details[$rules['parameter']] = $value;
    }
    return ['details' => $details, 'errors' => $errors];
}

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

        // Optional fields that weren't given are saved as NULL, and text is escaped
        $optionalFields = [$pdfSubmissionDate, $pantry_name, $pantry_address, $point_of_contact, $poc_ph_number,
            $secondary_poc, $secondary_poc_ph_num, $requested_delivery_date, $requested_delivery_time, $special_instructions, $non_pd_slp_box_wt,
            $non_pd_produce_wt, $non_pd_meat_wt, $non_pd_bakery, $non_pd_dairy, $non_pd_other, $pd_meat_wt, $pd_dairy_wt,
            $pd_deli_wt, $pd_bakery_wt, $pd_produce_wt, $pd_non_perish_wt, $pd_prep_deli_wt];
        $optionalValues = implode(', ', array_map(fn($value) => sqlValueOrNull($con, $value), $optionalFields));

        // Insert the food order
        $query = "INSERT INTO dbfoodorder
                    (requestor_person_id, order_status, submission_date, pdf_submission_date, pantry_name, pantry_address, point_of_contact, poc_ph_number,
                    secondary_poc, secondary_poc_ph_num, requested_delivery_date, requested_delivery_time, special_instructions, non_pd_slp_box_wt,
                    non_pd_produce_wt, non_pd_meat_wt, non_pd_bakery, non_pd_dairy, non_pd_other, pd_meat_wt, pd_dairy_wt,
                    pd_deli_wt, pd_bakery_wt, pd_produce_wt, pd_non_perish_wt, pd_prep_deli_wt)
                VALUES ($requestorId, '$order_status', NOW(), $optionalValues)";
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

        // Let every admin know there is a new order waiting for them to approve or reject
        if ($okCommit) {
            $from = $pantry_name ? ' from ' . $pantry_name : '';
            notifyAllAdmins('New food order #' . $orderId . $from . ' is waiting for approval', 'pending_order', $orderId);
        }

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
    - $pdfSubmissionDate through $special_instructions: The pantry, contact and delivery details,
      the same as addFoodOrder(). (Optional, default to null)
    Returns:
    - An empty array if the order was saved and the inventory was updated.
    - An array of item_category_id => quantity in inventory for each item without enough inventory.
      Nothing is saved in this case.
    - false if the order couldn't be saved.
*/
function addIndividualFoodOrder($requestorId, $foodOrderItems = [], $pdfSubmissionDate = null, $pantry_name = null, $pantry_address = null,
$point_of_contact = null, $poc_ph_number = null, $secondary_poc = null, $secondary_poc_ph_num = null, $requested_delivery_date = null,
$requested_delivery_time = null, $special_instructions = null) {
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
        // Optional fields that weren't given are saved as NULL, and text is escaped
        $optionalFields = [$pdfSubmissionDate, $pantry_name, $pantry_address, $point_of_contact, $poc_ph_number,
            $secondary_poc, $secondary_poc_ph_num, $requested_delivery_date, $requested_delivery_time, $special_instructions];
        $optionalValues = implode(', ', array_map(fn($value) => sqlValueOrNull($con, $value), $optionalFields));

        $query = "INSERT INTO dbfoodorder
                    (requestor_person_id, order_status, submission_date, fulfillment_date, pdf_submission_date, pantry_name, pantry_address,
                    point_of_contact, poc_ph_number, secondary_poc, secondary_poc_ph_num, requested_delivery_date, requested_delivery_time,
                    special_instructions)
                VALUES ($requestorId, '$order_status', NOW(), NOW(), $optionalValues)";
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

function addIndividualFoodOrderFromPDF($requestorId, $pdfpath) {
    //Open the PDF document
    $doc = PdfDocumentReader::open($pdfpath);

    //Set up Acroform reader for the document
    $reader = new PdfAcroFormReader($doc);

    //Get the fields in the PDF form
    $fields = $reader->getFieldsByName();

    $pdfSubmissionDate = date('Y-m-d', strtotime($fields['pdf_submission_date_af_date']->value)) ?? null;
    $pantry_name = $fields['pantry_name']->value ?? null;
    $pantry_address = $fields['pantry_address']->value ?? null;
    $point_of_contact = $fields['point_of_contact']->value ?? null;
    $poc_ph_number = $fields['poc_ph_number']->value ?? null;
    $secondary_poc = $fields['secondary_poc']->value ?? null;
    $secondary_poc_ph_number = $fields['secondary_poc_ph_number']->value ?? null;
    $requested_delivery_date = date('Y-m-d', strtotime($fields['requested_delivery_date_af_date']->value)) ?? null;
    $requested_delivery_time = date('H:i:s', strtotime($fields['requested_delivery_time']->value)) ?? null;
    $special_instructions = $fields['special_instructions']->value ?? null;

    $foodOrderItems = [];
    foreach ($fields as $field) {
        if (in_array($field->fullName, QUANTITYREQUESTEDFIELDS)) {
            if ((int)$field->value > 0) {
                $name = array_search($field->fullName, QUANTITYREQUESTEDFIELDS);
                $itemCategoryId = getItemCategoryIdByName($name);
                $foodOrderItems[] = [$itemCategoryId, (int)$field->value];
            }
        }
    }

    // If no items in food order return false
    if (empty($foodOrderItems)) {
        return false;
    }

    $returnValues = addIndividualFoodOrder($requestorId, $foodOrderItems, $pdfSubmissionDate, $pantry_name, $pantry_address,
        $point_of_contact, $poc_ph_number, $secondary_poc, $secondary_poc_ph_number, $requested_delivery_date,
        $requested_delivery_time, $special_instructions);

    // Add requested quantity to return values if needed
    $insufficient = [];
    if (!empty($returnValues) && $returnValues !== false) {
        foreach ($returnValues as $id => $stock) {
            $requested = $foodOrderItems[array_search(array_column($foodOrderItems, 0), $foodOrderItems)][1];
            array_push($insufficient, [getItemCategoryNameById($id), $requested, $stock]);
        }
        return $insufficient;
    }
    
    return $returnValues;
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

    // The order no longer needs an admin, so remove its alert from every admin's bell
    if ($ok) {
        deleteNotifications('pending_order', $id);
    }
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

    // The order no longer needs an admin, so remove its alert from every admin's bell
    if ($ok) {
        deleteNotifications('pending_order', $id);
    }
    return $ok;
}

function finalizeFoodOrder($id) {
    $con = connect();
    $id = (int)$id;

    // Update the order status to 'rejected', set the approver ID, and set the approval date to current timestamp
    $query = "UPDATE dbfoodorder SET order_status = 'rejected', fulfillment_date = NOW() WHERE id = $id";

    $ok = mysqli_query($con, $query);
    mysqli_close($con);

    // The order no longer needs an admin, so remove its alert from every admin's bell
    if ($ok) {
        deleteNotifications('pending_order', $id);
    }
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

function getAllPendingOrders() {
    $con = connect();
    $query = "SELECT * FROM dbfoodorder WHERE order_status = 'pending'";
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

/*
    Generate a PDF for a food order by its ID
    Parameters:
    - $id: The ID of the food order for which to generate the PDF.
    Returns:
    - None. The PDF is generated and output directly to the browser.
*/
function generateFoodOrderPDF($id) {
    $foodOrder = getFoodOrderById($id);
    if (!$foodOrder) {
        throw new Exception("Food order not found.");
    }
    
    // Load the PDF template
    $doc = PdfDocumentReader::open(dirname(__FILE__).'/../images/St. Lucy Blank Order Form-2026-Fillable.pdf');
    $reader = new PdfAcroFormReader($doc);
    $fields = $reader->getFieldsByName();
    

    $original_bytes = file_get_contents(dirname(__FILE__).'/../images/St. Lucy Blank Order Form-2026-Fillable.pdf');
    $filler = new PdfAcroFormFiller($doc, $original_bytes);

    // Fill in the fields with the food order data if null, use empty string to avoid errors
    $filler->setText($fields['pantry_name'], $foodOrder->getPantryName() ?? '');
    $filler->setText($fields['pantry_address'], $foodOrder->getPantryAddress() ?? '');
    $filler->setText($fields['point_of_contact'], $foodOrder->getPointOfContact() ?? '');
    $filler->setText($fields['secondary_poc'], $foodOrder->getSecondaryPoc() ?? '');
    $filler->setText($fields['special_instructions'], $foodOrder->getSpecialInstructions() ?? '');
    $filler->setText($fields['non_pd_slp_box_wt'], (string)$foodOrder->getNonPdSlpBoxWt() ?? '');
    $filler->setText($fields['non_pd_produce_wt'], (string)$foodOrder->getNonPdProduceWt() ?? '');
    $filler->setText($fields['non_pd_meat_wt'], (string)$foodOrder->getNonPdMeatWt() ?? '');
    $filler->setText($fields['non_pd_bakery'], (string)$foodOrder->getNonPdBakery() ?? '');
    $filler->setText($fields['non_pd_dairy'], (string)$foodOrder->getNonPdDairy() ?? '');
    $filler->setText($fields['non_pd_other'], (string)$foodOrder->getNonPdOther() ?? '');
    $filler->setText($fields['pd_meat_wt'], (string)$foodOrder->getPdMeatWt() ?? '');
    $filler->setText($fields['pd_dairy_wt.'], (string)$foodOrder->getPdDairyWt() ?? ''); // I'm not sure why this one has a period at the end; it doesn't have one in the PDF
    $filler->setText($fields['pd_deli_wt'], (string)$foodOrder->getPdDeliWt() ?? '');
    $filler->setText($fields['pd_bakery_wt'], (string)$foodOrder->getPdBakeryWt() ?? '');
    $filler->setText($fields['pd_produce_wt'], (string)$foodOrder->getPdProduceWt() ?? '');
    $filler->setText($fields['pd_non_perish_wt'], (string)$foodOrder->getPdNonPerishWt() ?? '');
    $filler->setText($fields['pd_prep_deli_wt'], (string)$foodOrder->getPdPrepDeliWt() ?? '');

    // Fill in the food order items in the PDF and leave item fields blank if ther is no item for that row
    $items = getFoodOrderItemsByOrderId($id); // Get all food order items for the order

    // Loop through each food order item and fill in the corresponding fields in the PDF
    foreach ($items as $item) {
        $itemCategoryId = $item->getItem_category_id();
        $itemCategory = retrieve_ItemCategory($itemCategoryId);
        $quantityRequested = $item->getQuantity_requested();
        $quantityDelivered = $item->getQuantity_delivered();
        $orderStatus = $foodOrder->getStatus();

        // Get the item category name from the item category ID
        $itemCategoryName = $itemCategory->getName();

        if (isset(QUANTITYREQUESTEDFIELDS[$itemCategoryName])) {
            $filler->setText($fields[QUANTITYREQUESTEDFIELDS[$itemCategoryName]], (string)$quantityRequested);
        }
        if ($orderStatus === 'fulfilled') {
            if (isset(QUANTITYDELIVEREDFIELDS[$itemCategoryName])) {
                $filler->setText($fields[QUANTITYDELIVEREDFIELDS[$itemCategoryName]], is_null($quantityDelivered) ? '' : (string)$quantityDelivered);
            }
        }
    }

    
    //Dates
    $filler->setText($fields['requested_delivery_date_af_date'], $foodOrder->getRequestedDeliveryDate() ?? '');
    $filler->setText($fields['pdf_submission_date_af_date'], $foodOrder->getPdfSubmissionDate() ?? '');

    //Times
    $filler->setText($fields['requested_delivery_time'], date('g:i A', strtotime($foodOrder->getRequestedDeliveryTime() ?? '')));
    
    //Phone Numbers
    $filler->setText($fields['poc_ph_number'], $foodOrder->getPocPhNumber() ?? '');
    $filler->setText($fields['secondary_poc_ph_number'], $foodOrder->getSecondaryPocPhNum() ?? '');

    // Use forward slashes so the path works on Mac/Linux as well as Windows
    $path = '/pdfs/FoodOrder_'.$foodOrder->getPantryName().Date('Y-m-d_H-i-s').'.pdf';
    $savePath = __DIR__.'/..'.$path;
    // Create the pdfs folder if it doesn't exist yet
    if (!is_dir(dirname($savePath))) {
        mkdir(dirname($savePath), 0755, true);
    }
    $filler->save($savePath);

    return ".".$path; // Return path to the generated PDF for use as a URL
    
}