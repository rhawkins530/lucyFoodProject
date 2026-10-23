<?php
    session_cache_expire(30);
    session_start();
    ini_set("display_errors",1);
    error_reporting(E_ALL);
    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    // Was an ID supplied?
    if ($_SERVER["REQUEST_METHOD"] == "GET" && !isset($_GET['id'])) {
        header('Location: viewItemCategories.php');
        die();
    } 

    // Is user authorized to view this page?
    if ($accessLevel < 2) {
        header('Location: index.php');
        die();
    }
    
    require_once('include/input-validation.php');
    require_once('database/dbinventory.php');
    require_once('database/dbPalletCounts.php');
    require_once('database/dbShoppingCount.php');

    // Does the category exist?
    $item = getItem($_GET['id']);
    if (!$item) {
        echo "That Category does not exist";
        die();
    }

    /* 
    * _POST is empty when the page is first loaded.
    *  Submitting the form on this page reloads the page with data in _POST
    *  if _POST is not empty, process data from form
    */
    $submit_success = false;
    $errors = [];
    if (!empty($_POST)) {
        if(isset($_POST["cancel_button"])){
            header('Location: viewItemCategories.php');
            die();
        }
        else if(isset($_POST["save_button"])){
            /* check that Name is set */
            if(isset($_POST["name"])){
                $name = $_POST["name"];
                if($item->getCategoryName() != $name && getItemByName($name)){
                    $errors[] = "Category name already exists";
                }
            }
            else{
                $errors[] = 'Name is required';
            }
            if(isset($_POST["minimum"])){
                $minimum = $_POST["minimum"];
            }
            else{
                $errors[] = 'Minimum boxes is required';
            }
            if($_POST["minimum"] < 0){
                    $errors[] = 'Minimum boxes cannot be negative';
                }
            if(isset($_POST["allocated"])){
                $allocated = $_POST["allocated"];
            }
            else{
                $errors[] = 'Allocated boxes is required';
            }
            if($_POST["allocated"] < 0){
                    $errors[] = 'Allocated boxes cannot be negative';
                }
            
            
            if(empty($errors)){
                setCategoryName($item->getId(), $name);
                setMinimumQuantity($item->getId(), $minimum);
                setAllocatedQuantity($item->getId(), $allocated);
                header('Location: viewItemCategories.php');
            }
        }
    }
?>
    
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Modify Item Category | CCDA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        pageheader {
            margin-top: 3rem;
            display: flex; 
            justify-content: center; 
            align-items: center;
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
            white-space: nowrap;
            overflow: hidden;
        }
        .report-container {
            max-width: 1100px;
            margin: 0 auto 4rem auto;
            padding: 1rem;
        }
        .report-section {
            background-color: white;
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
        .modify-table {
            width: 100%;
            border-collapse: collapse;
        }
        .modify-table-label {
            padding: 0.75rem 1rem;
            text-align: right;
            color: var(--page-font-color);
        }
        .modify-table-input{
            padding: 0.75rem 1rem;
            text-align: left;
            color: var(--page-font-color);
        }
        .modify-table th {
            background-color: var(--main-color);
            color: var(--button-font-color);
            font-weight: 500;
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
        .modify-activate-btn:hover,
        .modify-deactivate-btn:hover,
        .generate-btn:hover {
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
            .modify-table th,
            .modify-table td {
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
    </style>
    <script>
    function confirmDelete(categoryId, categoryName) {
        // Clear any existing error messages
        var errorList = document.getElementById('errorList');
        if (errorList) {
            errorList.style.display = 'none';
        }

        fetch('viewModifyItemCategory.php?action=checkInUse&id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                if (data.inUse) {
                    var msg = 'Cannot delete - this item is currently in use:';
                    if (data.pallets.length) {
                        msg += '\nPallet(s): ' + data.pallets.join(', ') + '.';
                    }
                    if (data.families.length) {
                        msg += '\nShopping list(s): ' + data.families.join(', ') + '.';
                    }
                    alert(msg);
                } else {
                    if (confirm('Are you sure you want to\nDELETE Category: ' + categoryName + '?')) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'delete_button';
                        input.value = '1';
                        document.forms['invForm'].appendChild(input);
                        document.forms['invForm'].submit();
                    }
                }
            });
        return false;
    }
    </script>
</head>
<pageheader>
    <h1  class="title">Modify Item Category</h1>
</pageheader>
<body>
    <?php require_once('header.php') ?>
    <main>
        <div class="report-container">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <?php echo "<p style='color: red;'>$error</p>" ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Update Inventory -->
            <div class="report-section">
                <h2>Category: <?php echo(getCategoryName($item->getId()))?></h2>              
                <form name="invForm" onsubmit="return validateFormDate()" method="POST" action="viewModifyItemCategory.php?id=<?php echo $item->getId();?>">
                    <div class="updateInv-row">
                            <table class="modify-table">
                                <tbody>
                                    <tr>
                                        <td class="modify-table-label"><label class="updateInv-label" for="name">Name: </label></td>
                                        <td class="modify-table-input"><input type="text" class="updateInv-qty" 
                                                value="<?php echo(getCategoryName($item->getId()))?>"
                                                name="name"
                                                required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="modify-table-label"><label class="updateInv-label" for="minimum">Minimum boxes: </label></td>
                                        <td class="modify-table-input"><input type="number" class="updateInv-qty" 
                                                value="<?php echo(getMinimumQuantity($item->getId()))?>"
                                                name="minimum"
                                                required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="modify-table-label"><label class="updateInv-label" for="allocated">Allocated boxes: </label></td>
                                        <td class="modify-table-input"><input type="number" class="updateInv-qty" 
                                                value="<?php echo(getAllocatedQuantity($item->getId()))?>"
                                                name="allocated"
                                                required>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-bottom: 4rem;"></div>
                    <div class="modifyUsers-formBtns">
                        <button name="save_button" class="modify-save-btn">Save Changes</button>
                        <button name="cancel_button" class="modify-cancel-btn" formnovalidate>Cancel</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
    

</body>
</html>
