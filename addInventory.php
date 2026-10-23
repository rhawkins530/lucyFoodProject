<?php
    session_cache_expire(30);
    session_start();

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    // Add database includes here

    require_once('database/dbinfo.php');
    require_once('database/dbInventory.php');

    /*
    require_once('database/dbPersons.php');
    require_once('database/dbInventoryEvent.php');
    require_once('database/dbItemCategory.php');
    require_once('database/dbItemCounts.php');
    require_once('database/dbPalletEvent.php');
   */
    // Check if inventory exists for a date
   /* if (isset($_GET['action']) && $_GET['action'] === 'checkDate') {
        $date = $_GET['date'] ?? '';
        $events = get_all_inventoryEvents_by_date($date);
        header('Content-Type: application/json');
        echo json_encode(['exists' => !empty($events)]);
        exit;
    } */

    /* get the total pallet inventory for each category to show in the pallet column */
    /* Only include active categories - inactive/deleted items on pallets should not transfer to new inventories */
  /*  $pallet_totals = array();
    $pallets = get_all_palletEvents();
    $categories = get_all_active_ItemCategory();
    foreach($categories as $category){
        if ($category->getShopOnly() == 1) continue;
        $pallet_totals[$category->getId()] = 0;
    }
    foreach($pallets as $pallet){
        $palletCounts = get_palletCounts_by_palletEvent($pallet->getId());
        foreach($palletCounts as $count){
            $categoryId = $count->getItemCategory();
            $category = retrieve_ItemCategory($categoryId);
            if ($category && $category->getStatus() == 'Active') {
                $pallet_totals[$categoryId] += $count->getQuantity();
            }
        }
    } */

    /* 
    * _POST is empty when the page is first loaded.
    *  Submitting the form on this page reloads the page with data in _POST
    *  if _POST is not empty, process data from form
    */
    $submit_success = false;
    // $include_pallets = false; 
    $errors = [];

    // Categories with IDs
    $CATEGORIES = [ 
        1 => 'Cereal/Oatmeal', 2 => 'Cooking Oil', 3 => 'Peanut Butter',
        4 => 'Jelly', 5 => 'Dry Pasta Box', 6 => 'Pasta Sauce',
        7 => 'Canned Chicken', 8 => 'Canned Tuna', 9 => 'Condiments',
        10 => 'Green Beans', 11 => 'Corn', 12 => 'Mixed Vegetables',
        13 => 'Baked Beans', 14 => 'Canned/Dry Beans', 15 => 'Canned Fruit (Mixed)',
        16 => 'Canned Peaches', 17 => 'Canned Pasta', 18 => 'Carrots',
        19 => 'Peas', 20 => 'Ramen', 21 => 'Milk',
        22 => 'Diced Tomatoes', 23 => 'Rice', 24 => 'Flour (Masa)',
        25 => 'Flour (White)', 26 => 'Soup', 27 => 'Juice',
        28 => 'Baking Goods', 29 => 'Mac & Cheese',
    ];
    
    //$LOCATIONS = ['Main'];

    //Crate types for unsorted inventory
    $CRATE_TYPES = ['Gaylord (M)', 'Blue Boy (L)', 'X-Large'];


    if(isset($_POST['food-status'])) {
        $foodStatus = $_POST['food-status'];
        // $location = $_POST['location'] ?? '';

        try {
            /*if (!in_array($location, $LOCATIONS, true)) {
                throw new InvalidArgumentException('Invalid location.');
            }*/

            if ($foodStatus === 'Sorted') {
                $qty = filter_var($_POST['box-quantity'] ?? '', FILTER_VALIDATE_INT,
                                  ['options' => ['min_range' => 1]]);
                $sortedCategory = $_POST['category'] ?? '';
                $catId = array_search($sortedCategory, $CATEGORIES, true);
                if ($qty === false || $catId === false) {
                    throw new InvalidArgumentException('Please choose a category and a box quantity of 1 or more.');
                }

                $existingId = getInventoryIdByCategory($catId);

                if ($existingId !== null) {
                    changeInventoryQuantity($existingId, $qty);
                }
                else{
                    if (!addInventoryItem($catId, $sortedCategory, $qty)){
                        throw new InvalidArgumentException('Could not save to the database.');
                    }
                }

            } elseif ($foodStatus === 'Unsorted') {
                $weight = filter_var($_POST['unsorted-weight'] ?? '', FILTER_VALIDATE_FLOAT);
                $crate  = $_POST['crate-type'] ?? '';
                if ($weight === false || $weight < 0 || !in_array($crate, $CRATE_TYPES, true)) {
                    throw new InvalidArgumentException('Please enter a weight and choose a crate type.');
                }

                $unsortedCats = array_values(array_unique(array_intersect($_POST['categories'] ?? [], $CATEGORIES)));

            } else {
                throw new InvalidArgumentException('Unknown food status.');
            }

            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?saved=1');
            exit;

        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

//    if (!isset($_POST['food-status']) && !empty($_POST)) {
//         $warehouseItems = array();
//         $pantryItems = array();
//         $date = null;

//         foreach($_POST as $name => $value){
//             if($name == "date"){
//                 $date = $value;
//             }
//             else if($name == "checkIncludePallets"){
//                 $include_pallets = true;
//             }
//             /* warehouse quantities have prefix "warehouse_" */
//             else if(strpos($name, 'warehouse_') === 0){
//                 $categoryId = str_replace('warehouse_', '', $name);

//                 /* only add items that have values to array */
//                 if(!empty($value)){

//                     /* try to convert value to a number. If it cannot convert, leave it as a string */
//                     try{
//                         $value = +$value;
//                     }
//                     catch(TypeError $e){
//                         $value = " ";
//                     }

//                     /* if error is found, empty the arrays and stop checking */
//                     if(!is_int($value)){
//                         $errors[] = 'Warehouse quantities must be in whole numbers';
//                         $warehouseItems = array();
//                         $pantryItems = array();
//                         break;
//                     }
//                     else if($value < 0){
//                         $errors[] = 'Warehouse quantities cannot be negative';
//                         $warehouseItems = array();
//                         $pantryItems = array();
//                         break;
//                     }
//                     /* accept items with 0 or greater quantity */
//                     else if($value >= 0){
//                         $warehouseItems[$categoryId] = $value;
//                     }
//                 }
//             }
//             /* pantry quantities have prefix "pantry_" */
//             else if(strpos($name, 'pantry_') === 0){
//                 $categoryId = str_replace('pantry_', '', $name);

//                 /* only add items that have values to array */
//                 if(!empty($value)){

//                     /* try to convert value to a number. If it cannot convert, leave it as a string */
//                     try{
//                         $value = +$value;
//                     }
//                     catch(TypeError $e){
//                         $value = " ";
//                     }

//                     /* if error is found, empty the arrays and stop checking */
//                     if(!is_int($value)){
//                         $errors[] = 'Pantry quantities must be in whole numbers';
//                         $warehouseItems = array();
//                         $pantryItems = array();
//                         break;
//                     }
//                     else if($value < 0){
//                         $errors[] = 'Pantry quantities cannot be negative';
//                         $warehouseItems = array();
//                         $pantryItems = array();
//                         break;
//                     }
//                     /* accept items with 0 or greater quantity */
//                     else if($value >= 0){
//                         $pantryItems[$categoryId] = $value;
//                     }
//                 }
//             }
//         } 

//         /* build pallet items array (from pallet_totals if checkbox checked, zeros if not) */
//         $palletItems = array();
//         if($include_pallets){
//             foreach($pallet_totals as $categoryId => $quantity){
//                 $palletItems[$categoryId] = $quantity;
//             }
//         }

//         // OLD CODE - add pallet totals to Warehouse only
//         // if($include_pallets){
//         //     foreach($pallet_totals as $categoryId => $quantity){
//         //         if(isset($warehouseItems[$categoryId])){
//         //             $warehouseItems[$categoryId] += $quantity;
//         //         }
//         //         else{
//         //             $warehouseItems[$categoryId] = $quantity;
//         //         }
//         //     }
//         // }

//         /* if at least 1 item was entered, create inventory events and add items to database */
//         if(empty($errors) && (count($warehouseItems) > 0 || count($pantryItems) > 0 || count($palletItems) > 0)){

//             /* auto-fill missing items with 0 for complete analytics data */
//             $allCategories = get_all_active_ItemCategory();
//             foreach($allCategories as $category){
//                 // Skip shopping list only items
//                 if ($category->getShopOnly() == 1) {
//                     continue;
//                 }
//                 $categoryId = $category->getId();
//                 if(!isset($warehouseItems[$categoryId])){
//                     $warehouseItems[$categoryId] = 0;
//                 }
//                 if(!isset($pantryItems[$categoryId])){
//                     $pantryItems[$categoryId] = 0;
//                 }
//                 if(!isset($palletItems[$categoryId])){
//                     $palletItems[$categoryId] = 0;
//                 }
//             }

//             /* create or update warehouse, pantry, and pallet events */
//             $personId = retrieve_person($userID)->get_personId();
//             $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === '1';
//             $existingEvents = get_all_inventoryEvents_by_date($date);

//             if ($overwrite && !empty($existingEvents)) {
//                 /* Overwrite - Delete existing events first */
//                 foreach ($existingEvents as $event) {
//                     if ($event->getLocation() == 'Warehouse') {
//                         $matches = get_matching_inventoryEvent($event);
//                         remove_inventoryEvent($event->getId());
//                         if (isset($matches['Pantry'])) {
//                             remove_inventoryEvent($matches['Pantry']->getId());
//                         }
//                         if (isset($matches['Pallet'])) {
//                             remove_inventoryEvent($matches['Pallet']->getId());
//                         }
//                         break;
//                     }
//                 }
//             }

//             /* Create inventory events */
//             $warehouseEventId = add_inventoryEvent($personId, 'Warehouse', $date);
//             foreach($warehouseItems as $categoryId => $quantity){
//                 add_itemCount($warehouseEventId, $categoryId, $quantity);
//             }

//             $pantryEventId = add_inventoryEvent($personId, 'Pantry', $date);
//             foreach($pantryItems as $categoryId => $quantity){
//                 add_itemCount($pantryEventId, $categoryId, $quantity);
//             }

//             $palletEventId = add_inventoryEvent($personId, 'Pallet', $date);
//             foreach($palletItems as $categoryId => $quantity){
//                 add_itemCount($palletEventId, $categoryId, $quantity);
//             }

//             $submit_success = true;
            
//             // Redirect to inventory page to view the newly created inventory
//             header('Location: inventory.php?week=' . $warehouseEventId);
//             exit;
//         }
//         else{
//             /* if errors have already been detected array was emptied. Do not show error for missing data */
//             if(empty($errors)){
//                 $errors[] = 'Enter quantity for at least 1 item';
//             }
//         }
//     }


    /* Previous Inventory column removed
    $previous_event_pair = get_previous_inventoryEvent_pair(new InventoryEvent(0, 0, 0, date('Y-m-d')));

    $prev_item_counts = array();
    if($previous_event_pair[0]){
        $prev_item_counts = get_itemCounts_by_inventoryEvent($previous_event_pair[0]->getId());
    }
    if($previous_event_pair[1]){
        $prev_item_counts = array_merge($prev_item_counts, get_itemCounts_by_inventoryEvent($previous_event_pair[1]->getId()));
    }

    $prev_totals = array();
    foreach($prev_item_counts as $item){
        if(isset($prev_totals[$item->getItemCategory()]))
            $prev_totals[$item->getItemCategory()] += $item->getQuantity();
        else
            $prev_totals[$item->getItemCategory()] = $item->getQuantity();
    }
    */
    

?>
    
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Add Inventory | CCDA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        pageheader {
            margin-top: 3rem;
            display: flex; justify-content: center; align-items: center;
        }
        .title {
            text-align: center;
            height: 3.5rem;
            width:auto;
            font-size: 2rem;
            font-weight: 600;
            color: var(--secondary-accent-color);
            padding-top: .4rem;
            border-radius: 10px;
            background-color: #ffffffee;
        }
        .report-container {
            max-width: 1100px;
            margin: 0 auto 4rem auto;
            padding: 1rem;
        }
        .report-section {
            background-color: #D4E7F6;
            /* border: 1px solid var(--shadow-and-border-color); */
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            margin-left: 15%;
            margin-right: 15%;
        }
        .report-section h1 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--secondary-accent-color);
        }
        .report-section h2 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--secondary-accent-color);
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th,
        .report-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--shadow-and-border-color);
            color: var(--page-font-color);
            text-align: left;
            vertical-align: middle;
        }
        .report-table th {
            background-color: var(--main-color);
            color: var(--button-font-color);
            font-weight: 500;
            position: sticky;
            top: 100px; /* height of page header */
        }
        .report-table tr:hover {
            background-color: rgba(255,255,255,0.05);
        }
        .low-stock-badge {
            display: inline-block;
            background-color: var(--error-toast-background-color);
            color: var(--error-toast-font-color);
            padding: 0.2rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .expired-text {
            color: var(--error-toast-background-color);
            font-weight: 600;
        }
        .chart-wrapper {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .chart-controls {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .chart-controls button {
            padding: 0.4rem 1rem;
            border: 2px solid var(--accent-color);
            border-radius: 0.25rem;
            background-color: transparent;
            color: var(--page-font-color);
            cursor: pointer;
            font-weight: 500;
            width: auto;
            font-size: 0.85rem;
        }
        .chart-controls button.active,
        .chart-controls button:hover {
            background-color: var(--accent-color);
            color: var(--button-font-color);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--inactive-font-color);
        }
        .updateInv-optionRow {
            display: flex;
            align-items: center;
            flex-direction: row;
            justify-content: left;
            gap: 1rem;
        }
        .updateInv-option {
            display: flex;
            align-items: center;
            flex-direction: row;
            width: 45%;
            gap: 1rem;
        }
        .updateInv-optionLabel {
            text-align: right;
        }
        .updateInv-allRows {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding: 2rem 1rem;
        }
        .updateInv-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            display: table-row !important;
        }
        .updateInv-label {
            color: var(--page-font-color);
            width: 200px;
            max-width: 400px;
            min-width: 6rem;
            flex-grow: 1;
            flex-grow: 1;
            text-align: right;
            padding: 0rem  .5rem 0rem 0rem;
        }
        .updateInv-qty {
            width: 100px;
            max-width: 100px;
            margin-bottom: 0rem !important;
            padding: 0.4rem 0.6rem !important;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            font-size: 0.9rem;
        }
        .generate-btn {
            padding: 0.5rem 1.5rem;
            background-color: var(--accent-color);
            color: var(--button-font-color);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            width: auto;
        }
        .generate-btn:hover {
            opacity: 0.85;
        }
        .modify-btn {
            padding: 0.5rem 1.5rem;
            background-color: var(--accent-color);
            color: var(--button-font-color);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            border: 1px solid black;
        }
        .modify-btn:hover,
        .manage-pallet-lnk:hover {
            opacity: 0.85;
        }
        .manage-pallet-lnk {
            white-space: nowrap;
            width: 100%;
            padding: 0.5rem 1rem;
        }
        .manage-pallet-lnk button {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: var(--button-font-color);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            margin: 0.25rem;
            text-align: center;
        }
        .pallet-column {
            text-align: left !important;
            display: inline-block;
            width: 100%;
        }
        .mobile-text {
            display: none;
        }
        @media only screen and (max-width: 768px) {
            pageheader {
                top: 100px;
            }
            .title {
                border-radius: 0;
                background-color: #ffffff;
                width: 100%;
            }
            .report-table th,
            .report-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            .report-table th {
                position: sticky;
                top: 100px;
            }
            .report-container {
                padding: 0.5rem;
            }
            div.table-wrapper {
                overflow-x: visible;
            }
            .updateInv-optionRow {
                display: flex;
                align-items: right;
                flex-direction: column;
                justify-content: left;
                gap: 1rem;
            }
            .updateInv-option {
                display: flex;
                align-items: center;
                flex-direction: row;
                width: auto;
                gap: 1rem;
            }
            .updateInv-qty {
                max-width: 7rem;
                margin-right: 2rem !important;
                
            }
            .desktop-text {
                display: none;
            }
            .mobile-text {
                display: inline;
            }
            .report-table th {
                font-size: 0.85rem;
                padding: 4px;
            }
            .report-table td {
                padding: 4px;
            }
            .report-section{
                padding: 15px;
            }
        }
        .form-column{
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 300px;
        }
        .input-group{
            display: flex;
            flex-direction: column;
            padding: 10px;
            margin-bottom: 5%;
        }
        datalist {
            position: absolute;
            background-color: white;
            border: 1px solid blue;
            border-radius: 0 0 5px 5px;
            border-top: none;
            font-family: sans-serif;
            width: 350px;
            padding: 5px;
        }
        #category-columns {
            column-count: 2;
            column-gap: 10%;
        }

        #category-columns label {
            display: block;
            break-inside: avoid;
            margin-bottom: 0.25rem;
        }
        @media (max-width: 480px){
            #category-columns { column-count: 1;}
        }
    </style>
</head>
<pageheader>
    <h1 class="title">Add Inventory</h1>
</pageheader>
<body>
    <?php require_once('header.php') ?>
    <main>
        <?php if (isset($_GET['saved'])): ?>
            <!-- Confirmation message -->
            <h4 id="saved-msg" style="color:black; margin-left:15%;" ><i>Inventory submitted.</i></h4>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <h4 style="color:red;"><i><?= htmlspecialchars($error) ?></i></h4>
        <?php endforeach; ?>
        <!-- Add Inventory -->
        <div class="report-section">
            <h2>Inventory Item Entry</h2>   
            <form method="POST" id="inv-form" novalidate>
                <div id="form-errors" role="alert" style="display:none; color:#b00020;"></div>
                
                <!-- Shared Fields -->
                <div class="input-group">
                    <label for="food-status">Food Status:</label>
                    <select id="food-status" name="food-status">
                        <?php $selStatus = $_POST['food-status'] ?? 'Sorted'; ?>
                        <option value="Sorted" <?= $selStatus === 'Sorted' ? 'selected' : '' ?>>Sorted</option>
                        <option value="Unsorted" <?= $selStatus === 'Unsorted' ? 'selected' : '' ?>>Unsorted</option>
                    </select>
                </div>

                <!--
                <div class="input-group">
                    <label for="location">Warehouse Location:</label>
                        <select id="location" name="location">
                        <option value="Main">Main</option> 
                        <option value="Annex">Annex</option> 
                    </select>
                </div> -->
                        

                <!-- Sorted Fields -->
                <div id="sorted-fields">
                    <div class="input-group">
                        <label for="category-options">Item Category:</label>
                        <select id="category-options" name="category">
                            <?php foreach ($CATEGORIES as $name): ?>
                                <option value="<?=  htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- <div class="input-group">
                        <label for="earliest-date-choice">Earliest Date On Container:</label>
                        <input type="date" id="earliest-date" name="earliest-date">
                    </div> -->

                    <div class="input-group">
                        <label for="box-quantity">Box Quantity:</label> <!-- Number of boxes (standardized container) -->
                        <input type="number" min="1" step="1" id="box-quantity" name="box-quantity" placeholder="Amount of boxes">
                    </div>
                </div>

                <!-- Unsorted Fields -->
                <div id="unsorted-fields" style="display:none;">
                    <div class="input-group">
                        <label for="unsorted-weight">Weight (lbs):</label>
                        <input type="number" id="unsorted-weight" name="unsorted-weight"
                            min="0" step="any" placeholder="Enter weight in lbs.">
                    </div>

                    <div class="input-group">
                        <label>Categories Inside Unsorted Crate:</label>
                        <div id="category-checklist">
                            <div style="display: flex; gap:10%; margin-bottom:0.5rem;">
                                <label>
                                    <input type="checkbox" id="cat-none" name="categories[]" value="None"> None
                                </label>
                                <label>
                                    <input type="checkbox" id="cat-all"> <strong>Select All</strong>
                                </label>
                            </div>

                            <div id="category-columns"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="crate-type">Crate Type:</label>
                        <select id="crate-type" name="crate-type">
                            <?php foreach ($CRATE_TYPES as $crate): ?>
                                <option value="<?= htmlspecialchars($crate) ?>"><?= htmlspecialchars($crate) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                    <!-- <div class="input-group">
                        <label for="weight-amount-lbs">Weight Marked on Each Box(lbs):</label>
                        <input type="number" id="weight-amount-lbs" name="weight-amount-lbs" placeholder="Enter weight in lbs." min="0" step="any">
                    </div> -->
                <div class="input-group">
                    <button type="submit">Submit to Inventory!</button>
                </div>
            </form>
        </div>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form        = document.getElementById('inv-form');
        const status      = document.getElementById('food-status');
        const sortedBox   = document.getElementById('sorted-fields');
        const unsortedBox = document.getElementById('unsorted-fields');
        const errorBox    = document.getElementById('form-errors');
        const checklist   = document.getElementById('category-checklist');
        const noneBox     = document.getElementById('cat-none');
        const categorySel = document.getElementById('category-options');
        const allBox      = document.getElementById('cat-all');
        const columns     = document.getElementById('category-columns');

        // Build the unsorted checklist from the category dropdown
        const catBoxes = [];
        Array.from(categorySel.options).forEach(function (opt) {
            const label = document.createElement('label');
            label.style.display = 'block';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'categories[]';
            cb.value = opt.value;
            label.appendChild(cb);
            label.appendChild(document.createTextNode(' ' + opt.text));
            columns.appendChild(label);
            catBoxes.push(cb);
        });

        function syncAll() {
            allBox.checked = catBoxes.length > 0 && catBoxes.every(cb => cb.checked);
        }

        allBox.addEventListener('change', function() {
            catBoxes.forEach(cb => cb.checked = allBox.checked);
            if (allBox.checked) noneBox.checked = false;
        });

        noneBox.addEventListener('change', function () {
            if (noneBox.checked) catBoxes.forEach(cb => cb.checked = false);
            allBox.checked = false;
        });

        catBoxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) noneBox.checked = false;
                syncAll();
            });
        });

        function setDisabled(container, disabled) {
            container.querySelectorAll('input, select').forEach(el => el.disabled = disabled);
        }

        function updateFields() {
            const isUnsorted = status.value === 'Unsorted';
            sortedBox.style.display   = isUnsorted ? 'none' : 'block';
            unsortedBox.style.display = isUnsorted ? 'block' : 'none';
            setDisabled(sortedBox, isUnsorted);
            setDisabled(unsortedBox, !isUnsorted);
            errorBox.style.display = 'none';

        }

        function validate() {
            const errors = [];
            if (status.value === 'Sorted') {
                const qty = Number(document.getElementById('box-quantity').value);
                if (!categorySel.value) errors.push('Please choose an item category.');
                if (!Number.isInteger(qty) || qty < 1)
                    errors.push('Box quantity must be a whole number of 1 or more.');
            } else {
                const w = document.getElementById('unsorted-weight').value;
                if (w === '' || isNaN(Number(w)) || Number(w) < 0)
                    errors.push('Please enter a weight of 0 or more.');
                if (!document.getElementById('crate-type').value)
                    errors.push('Please choose a crate type.');
                if (!noneBox.checked && !catBoxes.some(cb => cb.checked))
                    errors.push('Select "None" or at least one category.');
            }
            return errors;
        }

        form.addEventListener('submit', function (e) {
            const errors = validate();
            if (errors.length) {
                e.preventDefault();                       
                errorBox.innerHTML = errors.map(m => '<div>' + m + '</div>').join('');
                errorBox.style.display = 'block';
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }       
        });

        status.addEventListener('change', function () {
            updateFields();
            const savedMsg = document.getElementById('saved-msg');
            if (savedMsg) savedMsg.style.display = 'none';
        });
        updateFields();
    });
    if (window.location.search.includes('saved=1')) {
    history.replaceState(null, '', window.location.pathname);
    }
</script>
</body>
</html>