<?php
    session_cache_expire(30);
    session_start();
    ini_set("display_errors",1);
    error_reporting(E_ALL);
    //$loggedIn = false;
    $accessLevel =  $_SESSION['access_level'];
    /*$userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    // Was an ID supplied?
    if ($_SERVER["REQUEST_METHOD"] == "GET" && !isset($_GET['id'])) {
        header('Location: index.php');
        die();
    }*/

    // Is user authorized to view this page?
    if ($accessLevel < 2) {
        header('Location: index.php');
        die();
    }
    
    require_once('include/input-validation.php');
    require_once('database/dbFoodOrder.php');

    // Does the person exist?
    /*$thePerson = retrieve_person_by_personId($_GET['id']);
    if (!$thePerson) {
        echo "That user does not exist";
        die();
    }*/

    // Categories from the St. Lucy order form, in the same order as the PDF.
    // categoryId is the item_category_id in dbinventory.
    $ORDER_CATEGORIES = [
        ['location' => 'A3', 'name' => 'Canned Pasta', 'categoryId' => 17, 'highDemand' => false],
        ['location' => 'A3/A4', 'name' => 'Soup', 'categoryId' => 26, 'highDemand' => false],
        ['location' => 'A4', 'name' => 'Pasta Sauce', 'categoryId' => 6, 'highDemand' => false],
        ['location' => 'A7', 'name' => 'Canned Fruit', 'categoryId' => 15, 'highDemand' => false],
        ['location' => 'A7/A8', 'name' => 'Baking Goods', 'categoryId' => 28, 'highDemand' => false],
        ['location' => 'A11', 'name' => 'Meal Helpers', 'categoryId' => 30, 'highDemand' => false],
        ['location' => 'A11', 'name' => 'Mac & Cheese', 'categoryId' => 29, 'highDemand' => true],
        ['location' => 'A12', 'name' => 'Dry Pasta', 'categoryId' => 5, 'highDemand' => false],
        ['location' => 'A12', 'name' => 'Condiments', 'categoryId' => 9, 'highDemand' => false],
        ['location' => 'A15', 'name' => 'Dark Beans', 'categoryId' => 31, 'highDemand' => false],
        ['location' => 'A15/A16', 'name' => 'Snacks/Cookies', 'categoryId' => 32, 'highDemand' => false],
        ['location' => 'B3/B4', 'name' => 'Cereal & Oatmeal', 'categoryId' => 1, 'highDemand' => false],
        ['location' => 'B4', 'name' => 'Oil', 'categoryId' => 2, 'highDemand' => false],
        ['location' => 'B7/B8', 'name' => 'Jelly', 'categoryId' => 4, 'highDemand' => false],
        ['location' => 'B7', 'name' => 'Baked Beans', 'categoryId' => 13, 'highDemand' => false],
        ['location' => 'B8', 'name' => 'Peanut Butter', 'categoryId' => 3, 'highDemand' => true],
        ['location' => 'C3', 'name' => 'Canned Meat', 'categoryId' => 33, 'highDemand' => true],
        ['location' => 'C4', 'name' => 'Juice & Beverage', 'categoryId' => 27, 'highDemand' => false],
        ['location' => 'C4', 'name' => 'Tuna', 'categoryId' => 8, 'highDemand' => true],
        ['location' => 'C7/C8', 'name' => 'Vegetable', 'categoryId' => 34, 'highDemand' => false],
        ['location' => 'C11/C12', 'name' => 'Tomato Product', 'categoryId' => 35, 'highDemand' => false],
        ['location' => 'Refrigerator', 'name' => 'Tortilla', 'categoryId' => 36, 'highDemand' => false],
        ['location' => 'W-BARREL', 'name' => 'Bagged Rice', 'categoryId' => 23, 'highDemand' => false],
        ['location' => 'R-BARREL', 'name' => 'Bagged Dry Bean', 'categoryId' => 37, 'highDemand' => false],
    ];

    /* 
    * _POST is empty when the page is first loaded.
    *  Submitting the form on this page reloads the page with data in _POST
    *  if _POST is not empty, save the order
    */

    $errors = [];
    if (!empty($_POST)) {
        // Pantry and contact fields aren't saved yet; dbfoodorder has no columns for them.
        $boxes = $_POST['boxes'] ?? [];
        $foodOrderItems = [];
        foreach ($ORDER_CATEGORIES as $category) {
            $quantity = trim($boxes[$category['categoryId']] ?? '');
            if ($quantity === '') {
                continue;
            }
            if (!ctype_digit($quantity)) {
                $errors[] = "Number of boxes for " . $category['name'] . " must be a whole number";
                continue;
            }
            if ((int)$quantity === 0) {
                continue;
            }
            $foodOrderItems[] = [$category['categoryId'], (int)$quantity];
        }

        if (empty($foodOrderItems) && empty($errors)) {
            $errors[] = "Request at least one box";
        }
        if (empty($errors)) {
            if (addFoodOrder($_SESSION['_personId'], $foodOrderItems)) {
                header('Location: createOrder.php?created=success');
                die();
            }
            else {
                $errors[] = "Unable to create food order";
            }
        }
    }
?>
    
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Create Food Order | CCDA</title>
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
            max-width: 300px;
            margin-right: 30%;
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            font-size: 0.9rem;
        }
        .modify-role-select {
            max-width: 300px;
            margin-right: 30%;
            padding: 0.4rem 0.6rem;
        }
        .modify-status-label {
            max-width: 300px;
            margin-right: 30%;
            padding: 0.4rem 0.6rem;
        }
        .modify-save-btn,
        .modify-cancel-btn,
        .modify-delete-btn,
        .modify-activate-btn,
        .modify-deactivate-btn {
            padding: 0.5rem 1.5rem;
            background-color: var(--accent-color);
            color: var(--button-font-color);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            max-width: 500px;
        }
        .modify-delete-btn {
            background-color: darkred;
            color: var(--button-font-color);
        }
        .modify-deactivate-btn {
            color: red;
        }
        .modify-activate-btn {
            color: green;
        }
        .modify-delete-btn:hover{
            opacity: 0.75;
            background-color: darkred;
        }
        .modify-save-btn:hover,
        .modify-cancel-btn:hover,
        .modify-activate-btn:hover {
            opacity: 0.85;
        }
        .modifyUsers-formBtns{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
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
                position: static;

            }
            .report-container {
                padding: 0.5rem;
            }
            div.table-wrapper {
                overflow-x: auto;
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
                margin-right: 10%;
            }
        }

.order-form input[type="text"],
.order-form input[type="tel"],
.order-form input[type="date"],
.order-form input[type="time"],
.order-form textarea {
    width: 300px;
    max-width: 100%;
    margin-right: 30%;
    padding: 0.4rem 0.6rem;
}
.order-form textarea {
    min-height: 5rem;
}
.order-form .report-table input[type="number"] {
    width: 100px;
    padding: 0.4rem 0.6rem;
}
.order-form .high-demand {
    color: darkred;
    font-weight: 600;
}
.order-form .form-note {
    font-size: 0.85rem;
    margin-top: 1rem;
}
.report-container .error-toast {
    background-color: #D4E7F6;
    color: red;
}

    </style>
</head>
<pageheader>
    <h1 class="title">Create Individual Food Order</h1>
</pageheader>
<body>
    <?php require_once('header.php') ?>
    <div class="report-container">
        <?php foreach ($errors as $error): ?>
            <div class="error-toast"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        <form class="order-form" action="createIndivOrder.php" method="POST">
            <div class="report-section">
                <h2>Pantry Information</h2>
                <div class="updateInv-allRows">
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="submission_date">Submission Date</label>
                        <input type="date" id="submission_date" name="submission_date" value="<?php echo htmlspecialchars($_POST['submission_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="delivery_date">Requested Delivery Date</label>
                        <input type="date" id="delivery_date" name="delivery_date" value="<?php echo htmlspecialchars($_POST['delivery_date'] ?? ''); ?>">
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="delivery_time">Requested Delivery Time</label>
                        <input type="time" id="delivery_time" name="delivery_time" value="<?php echo htmlspecialchars($_POST['delivery_time'] ?? ''); ?>">
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="pantry_name">Pantry Name</label>
                        <input type="text" id="pantry_name" name="pantry_name" value="<?php echo htmlspecialchars($_POST['pantry_name'] ?? ''); ?>" required>
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="pantry_address">Pantry Address (Street, City)</label>
                        <input type="text" id="pantry_address" name="pantry_address" value="<?php echo htmlspecialchars($_POST['pantry_address'] ?? ''); ?>" required>
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="poc_name">Point of Contact (POC)</label>
                        <input type="text" id="poc_name" name="poc_name" value="<?php echo htmlspecialchars($_POST['poc_name'] ?? ''); ?>" required>
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="poc_phone">POC Phone Number</label>
                        <input type="tel" id="poc_phone" name="poc_phone" value="<?php echo htmlspecialchars($_POST['poc_phone'] ?? ''); ?>" required>
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="secondary_poc_name">Secondary POC</label>
                        <input type="text" id="secondary_poc_name" name="secondary_poc_name" value="<?php echo htmlspecialchars($_POST['secondary_poc_name'] ?? ''); ?>">
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="secondary_poc_phone">Secondary POC Phone Number</label>
                        <input type="tel" id="secondary_poc_phone" name="secondary_poc_phone" value="<?php echo htmlspecialchars($_POST['secondary_poc_phone'] ?? ''); ?>">
                    </div>
                    <div class="updateInv-row">
                        <label class="updateInv-label" for="special_instructions">Special Instructions (Optional)</label>
                        <textarea id="special_instructions" name="special_instructions"><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2>SLP Food Categories</h2>
                <div class="table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Warehouse Location</th>
                                <th>Category</th>
                                <th># of Boxes Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ORDER_CATEGORIES as $i => $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['location']); ?></td>
                                    <td>
                                        <label for="boxes_<?php echo $i; ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                                        <?php if ($category['highDemand']): ?><span class="high-demand">*</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" id="boxes_<?php echo $i; ?>" name="boxes[<?php echo $category['categoryId']; ?>]" min="0" step="1" value="<?php echo htmlspecialchars($_POST['boxes'][$category['categoryId']] ?? ''); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="form-note">Baking goods include flour, sugar, masa, and oil. "Vegetable" includes green beans, corn, dark beans, and mixed vegetables, based on availability.</p>
                <p class="form-note"><span class="high-demand">*</span> Items are in high demand, so there is a possibility of shortage.</p>
            </div>

            <div class="modifyUsers-formBtns">
                <button type="submit" class="modify-save-btn">Submit Order Form</button>
            </div>
        </form>
    </div>

</body>
</html>
