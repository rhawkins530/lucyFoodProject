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
require_once(__DIR__ . '/database/dbFoodOrder.php');
require_once(__DIR__ . '/database/dbFoodOrderItem.php');

$orders = getAllPendingOrders();

if(!empty($_GET)) {
    $id = $_GET['id'];

    if($_GET['verdict'] == "approve") {
        approveFoodOrder($id, $_SESSION['_personId']);
        $_SESSION['message'] = "Order Approved";
        
        $orderItems = getFoodOrderItemsByOrderId($id);

        foreach($orderItems as $item) {
            changeInventoryQuantity($item->getItem_category_id(),-$item->getQuantity_requested());
        }
    }
    else if($_GET['verdict'] == "reject") {
        rejectFoodOrder($id, $_SESSION['_personId']);
        $_SESSION['message'] = "Order Rejected";
    }
    header('Location: reviewOrders.php');
}

?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Review Food Orders | CCDA</title>
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
                padding: 0;
            }
        }
        .order-list-section{
            display: block; 
            background-color: #D4E7F6;
            padding: 10px; }

        th.sortable { 
            cursor: pointer; 
            user-select: none; 
            white-space: nowrap;
        }

        th.sortable:hover {
            opacity: 0.85;
        }
    th.sortable .arrow { 
        display: inline-block;
        width: 1em; 
    }

    .button {
        display: flex;
        margin-left: auto;
        text-align: center;
        width: 12%;
        height: 5%;
    }

    .verdict-buttons {
        display: flex;
        gap: 8px;
        justify-content: left;
    }

    .verdict-button {
        border: none;
        cursor: pointer;
        padding: 8px 14px;
        border-radius: 5px;
        font-size: 20px;
    }

    .accept {
        background-color: green;
        color: white;
    }
    .reject {
        background-color: red;
        color: white;
    }

    .verdict-button:hover{
        opacity: 0.75;
    }
    #orderBody tr {
        cursor: pointer;
    }
    #orderBody tr:hover {
        background-color: rgba(255,255,255,0.6);
    }


    </style>
</head>
<pageheader>
    <h1 class="title">Review Food Orders</h1>
</pageheader>

<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <?php
            if (isset($_SESSION['message'])) {
                echo '' . $_SESSION['message'] . '';
            }
            unset($_SESSION['message']);
            ?>
            <div class="report-section">
                <h2>Review Food Orders</h2>
                <div class="week-and-filter-section">
                    <div class="form-section">
                        <label for="typeSelect">Select Order Type:</label>
                        <select id="typeSelect">
                            <option value="all">Show All</option>
                            <option value="Individual">Individual Orders</option>
                            <option value="Multiple">Multiple Orders</option>
                        </select>
                    </div>
                    <a class="button" href="viewOrders.php">↵ Back</a>
                </div>
                <hr>
                <br>   
                    <div class="order-list-section">
                        <table class="report-table" id="orderTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Requestor ID</th>
                                    <th class="sortable" data-sort="date">Date Submitted <span class="arrow"></span></th>
                                    <th>Verdict</th>
                                </tr>
                            </thead>
                            <tbody id="orderBody">
                                <?php foreach ($orders as $order) {
                                    $id = $order->getId();
                                    $requestorId = $order->getRequestorPersonId();
                                    $date = date('M j, Y h:i A', strtotime($order->getSubmissionDate()));
                                    echo '<tr data-id="' . $id . '">
                                        <td>' . $id . '</td>
                                        <td>' . $requestorId . '</td>
                                        <td data-date="' . strtotime($order->getSubmissionDate()) . '">' . $date . '</td>';
                                    echo '<td>
                                        <div class="verdict-buttons"> 
                                                <a class="verdict-button accept" href="reviewOrders.php?id=' . $order->getId() . '&verdict=approve">✓</a>
                                                <a class="verdict-button reject" href="reviewOrders.php?id=' . $order->getId() . '&verdict=reject">✕</a>
                                        </div>
                                        </td>
                                    </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sortHeader = document.querySelector('[data-sort="date"]');
            const tableBody = document.getElementById("orderBody");
            let ascd = true;

            function sortDate() {
                const rows = Array.from(tableBody.querySelectorAll("tr"));

                rows.sort((a,b) => {
                    const dateA = Number(a.cells[2].dataset.date);
                    const dateB = Number(b.cells[2].dataset.date);
                    return ascd ? dateA - dateB : dateB - dateA;
                });

                rows.forEach(row => tableBody.appendChild(row));

                sortHeader.querySelector(".arrow").textContent = ascd ? " ▲" : " ▼";
            }

            sortDate();

            sortHeader.addEventListener("click", function (){
                ascd = !ascd;

                sortDate();
            });
        });

        document.querySelectorAll("#orderBody tr").forEach(row => {
            row.addEventListener("click", function(e) {
                if (e.target.closest(".verdict-buttons")) return;

                window.location.href = "viewOrderDetails.php?id=" + this.dataset.id;
            });
        });

        </script>
                                
</body>
</html>




