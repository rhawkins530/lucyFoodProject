<?php
session_cache_expire(30);
session_start();
ini_set("display_errors",1);
error_reporting(E_ALL);

// Admin check
if(!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

$accessLevel = $_SESSION['access_level'] ?? 0;

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/database/dbInventoryEvent.php');
require_once(__DIR__ . '/database/dbItemCategory.php');
require_once(__DIR__ . '/database/dbItemCounts.php');
require_once(__DIR__ . '/database/dbInventory.php');
require_once(__DIR__ . '/database/dbUnsorted.php');



//Get sort parameters
$sortCol = isset($_GET['sortCol']) ? (int)$_GET['sortCol'] : -1; // Default to -1 (no sorting)
$sortDir = isset($_GET['sortDir']) && $_GET['sortDir'] === 'desc' ? 'DESC' : 'ASC';

//Build the items array for the selected inventory
//Currently this will not include snapshots of previous inventories

$items = [];
$columnNums = [
    1 => "category_name",
    2 => "current_quantity",
    3 => "allocated_quantity",
    4 => "current_quantity - allocated_quantity",
    5 => "minimum_quantity"
];

//Build the items array for the selected inventory
if ($sortCol >= 0 && $sortCol <= count($columnNums)) {
    $items = getAllInventoryItemsSorted($columnNums[$sortCol], $sortDir);
} else {
    $items = getAllInventoryItems();
}

//Build the unsorted items array for the selected inventory
$columnNumsUnsorted = [
    1 => "id",
    2 => "weight",
    3 => "crate_type",
    4 => "state"
];

$unsorted_inventories = [];
if ($sortCol >= 0 && $sortCol <= count($columnNumsUnsorted)) {
    $unsorted_inventories = getUnsortedInventoriesSorted($columnNumsUnsorted[$sortCol], $sortDir);
} else {
    $unsorted_inventories = getAllUnsortedInventories();
}

?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Inventory Log | CCDA</title>
    <link rel="stylesheet" href="css/base.css">
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
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--inactive-font-color);
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
        }
        .week-and-filter-section{
            display: flex;
            flex-direction: row;
            gap: 2rem;
        }
        .form-section {
            margin-bottom: 1.5rem;
        }
        .form-section label {
            display: block;
            color: var(--page-font-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-section select {
            width: 100%;
            max-width: 300px;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            cursor: pointer;
        }
        .form-section select:hover {
            background-color: rgba(0,0,0,0.3);
        }
        .inventory-selector-toolbar {
            display: flex;
            align-content: center;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            flex-direction: column;
        }
        .week-selector {
            margin-bottom: .5rem;
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .week-selector label {
            color: var(--page-font-color);
        }
        .week-selector select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            cursor: pointer;
            min-width: 200px;
        }
        .week-selector select:hover {
            background-color: rgba(0,0,0,0.3);
        }
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .toolbar-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            cursor: pointer;
            min-width: 180px;
        }
        .toolbar-select:hover {
            background-color: rgba(0,0,0,0.3);
        }
        .toolbar-search {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            min-width: 200px;
        }
        .toolbar-search::placeholder {
            color: var(--inactive-font-color);
        }
        .toolbar-btn-clear {
            padding: 0.5rem 1rem;
            border: 1px solid var(--shadow-and-border-color);
            border-radius: 0.25rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            cursor: pointer;
            font-weight: 500;
        }
        .toolbar-btn-clear:hover {
            background-color: rgba(0,0,0,0.3);
        }
        .modify-button {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: var(--button-font-color);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            max-width: 500px;
        }
        .modify-button:hover {
            opacity: 0.85;
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
                background-color: #D4E7F6;
                padding: 0;
            }
        }
    </style>
</head>
<pageheader>
		    <h1 class="title" style="margin-top: 0; margin-bottom: 0; padding-top: 0;">Inventory Log</h1>
</pageheader>
<body>
    <?php require_once('header.php'); ?>
    <main>
        <?php if($accessLevel >= 1): ?>
            <div class="report-container" style="margin-top: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0;">
                <a href="editInventory.php" style="text-decoration: none;">
                    <button class="modify-button" style="width: 140px; height: 45px;">
                        Modify
                    </button>
                </a>
            </div>
        <?php endif; ?>
<div class="report-container">
            <div class="report-section">
                <h2>Food Items</h2>
                <select id="food-status" name=food-status">
                    <option value="Sorted">Sorted</option>
                    <option value="Unsorted">Unsorted</option>
                </select>
                <hr>
                <br>
                <div id="sorted-table">
                    <div class="table-wrapper">
                        <table class="report-table" id="inventoryTable">
                            <thead>
                                    <tr>
                                        <th>
                                            <?php if ($sortCol === 1 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=1&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">Item Name ↑</span></a>
                                                <a href='?sortCol=1&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Item Name ↑</span></a>
                                            <?php elseif ($sortCol === 1 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Item Name ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Item Name ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=1&sortDir=asc' style="color: white;"><span class="desktop-text">Item Name ⇅</span></a>
                                                <a href='?sortCol=1&sortDir=asc' style="color: white;"><span class="mobile-text">Item Name ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 2 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=2&sortDir=desc' style="font-weight: bold;color: white;"><span class="desktop-text">Total Boxes ↑</span></a>
                                                <a href='?sortCol=2&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Total Boxes ↑</span></a>
                                            <?php elseif ($sortCol === 2 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Total Boxes ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Total Boxes ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=2&sortDir=asc' style="color: white;"><span class="desktop-text">Total Boxes ⇅</span></a>
                                                <a href='?sortCol=2&sortDir=asc' style="color: white;"><span class="mobile-text">Total Boxes ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 3 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=3&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">Allocated Items ↑</span></a>
                                                <a href='?sortCol=3&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Allocated Items ↑</span></a>
                                            <?php elseif ($sortCol === 3 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Allocated Items ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Allocated Items ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=3&sortDir=asc' style="color: white;"><span class="desktop-text">Allocated Items ⇅</span></a>
                                                <a href='?sortCol=3&sortDir=asc' style="color: white;"><span class="mobile-text">Allocated Items ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 4 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=4&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">Over Allocated ↑</span></a>
                                                <a href='?sortCol=4&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Over Allocated ↑</span></a>
                                            <?php elseif ($sortCol === 4 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Over Allocated ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Over Allocated ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=4&sortDir=asc' style="color: white;"><span class="desktop-text">Over Allocated ⇅</span></a>
                                                <a href='?sortCol=4&sortDir=asc' style="color: white;"><span class="mobile-text">Over Allocated ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 5 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=5&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">Minimum Quantity ↑</span></a>
                                                <a href='?sortCol=5&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Minimum Quantity ↑</span></a>
                                            <?php elseif ($sortCol === 5 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Minimum Quantity ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Minimum Quantity ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=5&sortDir=asc' style="color: white;"><span class="desktop-text">Minimum Quantity ⇅</span></a>
                                                <a href='?sortCol=5&sortDir=asc' style="color: white;"><span class="mobile-text">Minimum Quantity ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                    </tr>
                                </thead>
                            <tbody>
                                <?php if (count($items) > 0): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item->getCategoryName()) ?></td>
                                            <td><?= htmlspecialchars($item->getCurrentQuantity()) ?></td>
                                            <td><?= htmlspecialchars($item->getAllocatedQuantity()) ?></td>
                                            <td><?= $item->getAllocatedQuantity() > $item->getCurrentQuantity() ? '✓' : '' ?></td>
                                            <td><?= htmlspecialchars($item->getMinimumQuantity()) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">No items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="unsorted-table" style="display:none;">
                    <div class="table-wrapper">
                        <table class="report-table" id="inventoryTable">
                            <thead>
                                    <tr>
                                        <th>
                                            <?php if ($sortCol === 1 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=1&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">ID ↑</span></a>
                                                <a href='?sortCol=1&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">ID ↑</span></a>
                                            <?php elseif ($sortCol === 1 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">ID ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">ID ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=1&sortDir=asc' style="color: white;"><span class="desktop-text">ID ⇅</span></a>
                                                <a href='?sortCol=1&sortDir=asc' style="color: white;"><span class="mobile-text">ID ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 2 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=2&sortDir=desc' style="font-weight: bold;color: white;"><span class="desktop-text">Weight ↑</span></a>
                                                <a href='?sortCol=2&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Weight ↑</span></a>
                                            <?php elseif ($sortCol === 2 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Weight ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Weight ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=2&sortDir=asc' style="color: white;"><span class="desktop-text">Weight ⇅</span></a>
                                                <a href='?sortCol=2&sortDir=asc' style="color: white;"><span class="mobile-text">Weight ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 3 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=3&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">Crate Type ↑</span></a>
                                                <a href='?sortCol=3&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">Crate Type ↑</span></a>
                                            <?php elseif ($sortCol === 3 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">Crate Type ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">Crate Type ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=3&sortDir=asc' style="color: white;"><span class="desktop-text">Crate Type ⇅</span></a>
                                                <a href='?sortCol=3&sortDir=asc' style="color: white;"><span class="mobile-text">Crate Type ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                        <th>
                                            <?php if ($sortCol === 4 && $sortDir === 'ASC'): ?>
                                                <a href='?sortCol=4&sortDir=desc' style="font-weight: bold; color: white;"><span class="desktop-text">State ↑</span></a>
                                                <a href='?sortCol=4&sortDir=desc' style="font-weight: bold; color: white;"><span class="mobile-text">State ↑</span></a>
                                            <?php elseif ($sortCol === 4 && $sortDir === 'DESC'): ?>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="desktop-text">State ↓</span></a>
                                                <a href='?' style="font-weight: bold; color: white;"><span class="mobile-text">State ↓</span></a>
                                            <?php else: ?>
                                                <a href='?sortCol=4&sortDir=asc' style="color: white;"><span class="desktop-text">State ⇅</span></a>
                                                <a href='?sortCol=4&sortDir=asc' style="color: white;"><span class="mobile-text">State ⇅</span></a>
                                            <?php endif; ?>
                                        </th>
                                    </tr>
                                </thead>
                            <tbody>
                                <?php if (count($unsorted_inventories) > 0): ?>
                                    <?php foreach ($unsorted_inventories as $unsorted): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($unsorted->getId()) ?></td>
                                            <td><?= htmlspecialchars($unsorted->getWeight()) ?></td>
                                            <td><?= htmlspecialchars($unsorted->getCrateType()) ?></td>
                                            <td><?= htmlspecialchars($unsorted->getStatus())?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">No items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
		</div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

        const status      = document.getElementById('food-status');
        const firstFields = document.getElementById('sorted-table');
        const secondFields = document.getElementById('unsorted-table');

        const secondOption = 'Unsorted';

        function setDisabled(container, disabled) {
            container.querySelectorAll('input, select').forEach(el => {
                el.disabled = disabled;
            });
        }

        function updateFields() {
            const showSecond = status.value === secondOption;

            // Control visibility
            firstFields.style.display  = showSecond ? 'none' : 'block';
            secondFields.style.display = showSecond ? 'block' : 'none';

            // Enable/disable inputs
            setDisabled(firstFields, showSecond);
            setDisabled(secondFields, !showSecond);
        }

        // Update whenever the dropdown selection changes
        status.addEventListener('change', updateFields);

        // Set the correct visibility when the page initially loads
        updateFields();

        });
    </script>

</body>
</html>




