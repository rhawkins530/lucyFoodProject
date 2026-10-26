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

    /* Access control - Inventory Counters and Managers */
    if($accessLevel < 1) {
        header('Location: index.php');
        die();
    }

    require_once('database/dbinfo.php');
    require_once('database/dbInventoryEvent.php');
    require_once('database/dbItemCounts.php');
    require_once('database/dbItemCategory.php');
    require_once('database/dbInventory.php');

    /* Get all inventory items (same rows as the inventory.php table) */
    $items = getAllInventoryItems();

    /* Handle form submission */
    $errors = [];
    $success = false;
    $deleted = false;
    
    if(isset($_POST['cancel_button'])) {
        header('Location: inventory.php');
        die();
    }
    else if(isset($_POST['save_button'])) {
        /* Update total boxes for each inventory item */
        foreach($items as $item) {
            $fieldName = 'total_boxes_' . $item->getId();
            if(isset($_POST[$fieldName])) {
                try{
                    $newQty = +$_POST[$fieldName];
                }
                catch(TypeError $e){
                    $newQty = " ";
                }
                if(!is_int($newQty)){
                    $errors[] = $item->getCategoryName() . ' total boxes must be in whole numbers';
                    continue;
                }
                else if($newQty < 0){
                    $errors[] = $item->getCategoryName() . ' total boxes cannot be negative';
                    continue;
                }
                if($item->getCurrentQuantity() != $newQty) {
                    setInventoryQuantity($item->getId(), $newQty);
                }
            }
        }
        if(empty($errors)) {
            header('Location: inventory.php');
            exit;
        }
    }

?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Edit Inventory | CCDA</title>
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
        }
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #D4E7F6;
            border-radius: 15px;
        }
        .event-info {
            background-color: rgba(0,0,0,0.05);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .event-info p {
            margin: 0.5rem 0;
            color: var(--page-font-color);
            font-weight: 500;
        }
        .modify-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        .modify-table th {
            background-color: var(--main-color);
            color: var(--button-font-color);
            padding: 0.75rem;
            text-align: left;
            font-weight: 500;
            position: sticky;
            top: 100px; /* height of page header */
        }
        .modify-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--shadow-and-border-color);
            color: var(--page-font-color);
        }
        .updateInv-qty {
            width: 80px;
            box-sizing: border-box;
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.05);
            color: var(--page-font-color);
        }
        .updateInv-qty:disabled {
            background-color: rgba(0,0,0,0.02);
            color: var(--inactive-font-color);
            cursor: not-allowed;
        }
        .modifyUsers-formBtns {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .modify-save-btn,
        .modify-cancel-btn,
        .modify-delete-btn {
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
        .modify-delete-btn:hover {
            opacity: 0.75;
            background-color: darkred;
        }
        .modify-save-btn:hover,
        .modify-cancel-btn:hover {
            opacity: 0.85;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            text-decoration: none;
            border-radius: 0.25rem;
        }
        .back-btn:hover {
            background-color: rgba(0,0,0,0.3);
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
            div.table-wrapper {
                overflow-x: auto;
            }
            .edit-container {
                padding: 1rem;
            }
            .updateInv-qty {
                width: 80px;
            }
        }
    </style>
</head>
<pageheader>
    <h1 class="title" style="margin-bottom: 0;">Edit Inventory</h1>
</pageheader>
<body>
    <?php require_once('header.php') ?>
    <main class="edit-container">
        <a href="inventory.php" class="back-btn" style="margin-top: 0;">← Back</a>
        <!-- Error Messages -->
        <?php if(!empty($errors)): ?>
            <ul>
                <?php foreach($errors AS $error): ?>
                    <li><?php echo("<h4 style=\"color:red;\"><i>".$error."</i></h4>"); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST">
            <table class="modify-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Total Boxes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item->getCategoryName()) ?></td>

                            <!-- Total Boxes Input -->
                            <td>
                                <input type="number"
                                       name="total_boxes_<?= $item->getId() ?>"
                                       value="<?= htmlspecialchars($item->getCurrentQuantity()) ?>"
                                       min="0"
                                       class="updateInv-qty">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="modifyUsers-formBtns">
                <button type="submit" name="save_button" class="modify-save-btn">Save Changes</button>
                <button type="submit" name="cancel_button" class="modify-cancel-btn" formnovalidate>Cancel</button>
            </div>
        </form>
    </main>
</body>
</html>
