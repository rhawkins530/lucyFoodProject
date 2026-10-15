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

//Build the items array for the selected inventory
//Currently this will not include snapshots of previous inventories
$items = [];
$headers = [];
if (($file = fopen("MOCK_ORDERS.csv", "r")) !== FALSE) {
    $headers = fgetcsv($file); 

    while (($row = fgetcsv($file)) !== FALSE) { // Add over_allocated value based on allocated_boxes and total_boxes
        $items[] = array_combine($headers, $row);
    }

    fclose($file);
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Finalize Food Orders | CCDA</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        pageheader {
            margin-top: 3rem;
            display: flex; justify-content: center; align-items: center;
            position: sticky;
            top: 1rem;
            z-index: 6;
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
                padding: 0;
            }
        }
        .verdict-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .approve-button, .reject-button {
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.1s;
        }

        .approve-button {
            background-color: green;
            color: white;
        }

        .reject-button {
            background-color: red;
            color: white;
        }

    </style>
</head>
<pageheader>
    <h1 class="title">Finalize Orders</h1>
</pageheader>
<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <div class="report-section">
                <h2>Food Orders</h2>
                <hr><br>
                <div class="table-wrapper">
                    <table class="report-table" id="inventoryTable">
                        <thead>
                                <tr>
                                    <th>
                                        <a style="color: white;"><span class="desktop-text">Order Id</span></a>
                                        <a style="color: white;"><span class="mobile-text">Order Id</span></a>
                                    </th>
                                    <th>
                                        <a style="color: white;"><span class="desktop-text">Date</span></a>
                                        <a style="color: white;"><span class="mobile-text">Date</span></a>
                                    </th>
                                    <th>
                                        <a style="color: white;"><span class="desktop-text">Verdict</span></a>
                                        <a style="color: white;"><span class="mobile-text">Verdict</span></a>
                                    </th>

                                </tr>
                            </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['date']) ?></td>
                                        <td class="verdict-buttons">
                                            <button type="button" class="approve-button" title="Approve" aria-label="Approve Order">✓</button>
                                            <button type="button" class="reject-button" title="Reject" aria-label="Reject Order">X</button>
                                        </td>
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
    </main>
</body>
</html>




