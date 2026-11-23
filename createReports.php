<?php
    session_cache_expire(30);
    session_start();

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])){
        $loggedIn = true;
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    require_once('database/dbinfo.php');
    require_once('database/dbInventory.php');
    require_once('database/dbReports.php');
    //add more databases when needed for connection.

    // Creating a report takes two steps:
    // 1. Clicking a report type button shows that report on this page, with text boxes so the
    //    user can fill in or change the entries. Nothing is saved yet.
    // 2. Clicking "Generate Report" under the report saves it to dbreports, then redirects back
    //    here with a success message, so refreshing the page doesn't save it again.
    // The text box values aren't saved anywhere yet; dbreports has no columns for report contents.
    $reportTypes = ['End of Year Report', 'Quarterly Report', 'Container Date Entered Report', 'Total Available Inventory Report'];
    $inventoryReportTypes = ['End of Year Report', 'Quarterly Report', 'Total Available Inventory Report'];
    // Report types that also show the From and To dates and the Total Inventory Weight line under the date
    $reportTypesWithTotalWeight = ['End of Year Report', 'Quarterly Report'];
    date_default_timezone_set("America/New_York");

    $reportType = null;
    if (isset($_POST['report-type']) && in_array($_POST['report-type'], $reportTypes, true)) {
        $reportType = $_POST['report-type'];
    }

    // From and To dates for End of Year and Quarterly Reports, as 'YYYY-MM-DD' (what a date box sends).
    // Anything that isn't a valid date is treated as empty.
    $postedDate = function ($field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
            return '';
        }
        $date = DateTime::createFromFormat('!Y-m-d', $_POST[$field]);
        return $date !== false && $date->format('Y-m-d') === $_POST[$field] ? $_POST[$field] : '';
    };
    $fromDate = $postedDate('from-date');
    $toDate = $postedDate('to-date');
    // The To date can't be before the From date
    $dateRangeInvalid = $fromDate !== '' && $toDate !== '' && $toDate < $fromDate;

    // Step 2: the user clicked "Generate Report"
    $reportSaveFailed = false;
    if ($reportType !== null && isset($_POST['generate-report']) && !$dateRangeInvalid) {
        // When the button was clicked, this is when the report was generated
        $dateCreated = date('Y-m-d H:i:s');

        $reportId = addReport($reportType, $userID, $dateCreated);
        if ($reportId !== false) {
            header('Location: createReports.php?generated=1');
            die();
        }
        // Saving failed, so the report is shown again below with what the user typed
        $reportSaveFailed = true;
    }

    // Step 1 (or a failed step 2): build the report to show. Text boxes are filled with what the
    // user typed if the form was sent back, and start empty otherwise.
    $postedRows = isset($_POST['rows']) && is_array($_POST['rows']) ? $_POST['rows'] : [];
    $postedValue = function ($index, $field) use ($postedRows) {
        return isset($postedRows[$index][$field]) && is_string($postedRows[$index][$field]) ? $postedRows[$index][$field] : '';
    };
    $reportDate = $reportType !== null ? date('m/d/Y') : null;
    $totalWeight = isset($_POST['total-weight']) && is_string($_POST['total-weight']) ? $_POST['total-weight'] : '';

    // End of Year, Quarterly, and Total Available Inventory Reports list the inventory items.
    // Category comes from dbinventory; the other fields are text boxes.
    $inventoryReportRows = null;
    if (in_array($reportType, $inventoryReportTypes, true)) {
        $inventoryReportRows = [];
        foreach (getAllInventoryItems() as $inventoryItem) {
            $index = count($inventoryReportRows);
            $inventoryReportRows[] = [
                'category' => $inventoryItem->getCategoryName(),
                'location' => $postedValue($index, 'location'),
                'container' => $postedValue($index, 'container'),
                'status' => $postedValue($index, 'status'),
                'weight' => $postedValue($index, 'weight')
            ];
        }
    }

    // Container Date Entered Report: every container's date entered and type, oldest date first.
    // Containers aren't stored in the database yet, so there is one empty row to fill in. Each
    // container from the database should be added as ['date_entered' => 'YYYY-MM-DD', 'type' => '...'].
    $containerReportRows = null;
    if ($reportType === 'Container Date Entered Report') {
        $containerReportRows = [];
        // Oldest date first; containers without a date entered go at the bottom
        usort($containerReportRows, function ($a, $b) {
            $aTime = empty($a['date_entered']) ? PHP_INT_MAX : strtotime($a['date_entered']);
            $bTime = empty($b['date_entered']) ? PHP_INT_MAX : strtotime($b['date_entered']);
            return $aTime <=> $bTime;
        });
        if (empty($containerReportRows)) {
            $containerReportRows[] = ['date_entered' => $postedValue(0, 'date_entered'), 'type' => $postedValue(0, 'type')];
        }
    }

    //may use later
    // $submit_success = false; 
    // $errors = [];

    // if(isset($_POST['report-type'])){
    //     $reportType = $_POST['report-type'];

    //     try{
    //         if($reportType === 'End of Year Report'){}

    //         elseif($reportType === 'Quarterly Report'){}

    //         elseif($reportType === 'Container Date Entered Report'){}

    //         else($reportType === 'Total Available Inventory Report'){}

    //     }
    // }


?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Create Reports | CCDA</title>
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
                margin-bottom: 10%;
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

    .report-type-options button {
        background-color: var(--main-color);
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 2%;
        max-width: 75%;
    }
    .report-type-options button:hover{
        background-color: var(--secondary-accent-color);
        border: solid 3px var(--accent-color);
    }
    .report-message {
        display: flex;
        max-width: fit-content;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 0.75%;
        border-radius: 0.5rem;
        background-color: #D4E7F6;
        padding: 0.5rem;
        color: green;
        text-align: center;
    }
    .report-error {
        color: red;
    }
    .inventory-report {
        max-width: 1100px;
        margin: 0 auto 2rem auto;
    }
    .report-info {
        margin-bottom: 1rem;
    }
    .report-info p {
        margin: 0 0 0.25rem 0;
    }
    .report-input {
        width: 100%;
        min-width: 6rem;
        box-sizing: border-box;
        padding: 0.4rem 0.6rem;
        border: 1px solid var(--shadow-and-border-color);
        border-radius: 0.25rem;
        font-size: 0.9rem;
    }
    .report-info .report-input {
        width: 10rem;
        display: inline-block;
        margin-right: 1rem;
    }
    .report-info label {
        display: inline;
    }
    .generate-report-btn {
        display: block;
        width: fit-content;
        margin: 1.5rem auto 0 auto;
        padding: 12px 20px;
    }
    .report-container {
        width: fit-content;
        background-color: #D4E7F6;
        /* border: 1px solid var(--shadow-and-border-color); */
        border-radius: 15px;
        padding: 15px 25px;
        margin-bottom: 2rem;
        margin-left: auto;
        margin-right: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .report-container h1,
    .report-container h2 {
        font-size: 1.5rem;
        font-weight: 500;
        margin-bottom: 1rem;
        color: var(--secondary-accent-color);
    }
    .button {
        width: fit-content;
        box-sizing: border-box;
        align-self: center;
        padding: 12px 20px;
        margin: 0;
        white-space: nowrap;
    }
    </style>
</head>
<pageheader>
    <h1 class="title">Choose a Report Type to Generate:</h1>
</pageheader>
<body>
    <?php require_once('header.php') ?>
    <div class="report-container">
        <?php if (isset($_GET['generated']) && $reportType === null): ?>
            <div class="report-message">Your report has been generated.</div>
        <?php endif; ?>
        <?php if ($reportSaveFailed): ?>
            <div class="report-message report-error">Your report couldn't be generated.</div>
        <?php endif; ?>
        <div class="report-type-options">
            <!-- The clicked button's value tells the page which report to show for editing -->
            <form method="post" action="createReports.php">
                <button type="submit" name="report-type" value="End of Year Report" class="report-btn">Generate End of Year Report</button>
                <button type="submit" name="report-type" value="Quarterly Report" class="report-btn">Generate Quarterly Report</button>
                <button type="submit" name="report-type" value="Container Date Entered Report" class="report-btn">Generate Container Date Entered Report</button>
                <button type="submit" name="report-type" value="Total Available Inventory Report" class="report-btn">Generate Total Available Inventory Report</button>
            </form>
        </div>
</div>

    <?php if ($inventoryReportRows !== null): ?>
    <!-- The report to edit. Clicking "Generate Report" sends the report type and text boxes back to this page to save it. -->
    <form method="post" action="createReports.php" class="report-section inventory-report">
        <input type="hidden" name="report-type" value="<?= htmlspecialchars($reportType) ?>">
        <h2><?= htmlspecialchars($reportType) ?></h2>
        <div class="report-info">
            <p><strong>Date:</strong> <?= htmlspecialchars($reportDate) ?></p>
            <?php if (in_array($reportType, $reportTypesWithTotalWeight, true)): ?>
                <!-- The dates the report covers. min and max keep To from being before From in the date picker. -->
                <p>
                    <label for="from-date"><strong>From:</strong></label>
                    <input type="date" id="from-date" name="from-date" class="report-input" value="<?= htmlspecialchars($fromDate) ?>" max="<?= htmlspecialchars($toDate) ?>">
                    <label for="to-date"><strong>To:</strong></label>
                    <input type="date" id="to-date" name="to-date" class="report-input" value="<?= htmlspecialchars($toDate) ?>" min="<?= htmlspecialchars($fromDate) ?>">
                </p>
                <?php if ($dateRangeInvalid): ?>
                    <p class="report-error">The To date can't be before the From date.</p>
                <?php endif; ?>
                <p><strong>Total Inventory Weight:</strong>
                    <input type="text" name="total-weight" class="report-input" placeholder="--" aria-label="Total Inventory Weight" value="<?= htmlspecialchars($totalWeight) ?>">
                </p>
            <?php endif; ?>
        </div>
        <?php if (empty($inventoryReportRows)): ?>
            <p class="empty-state">There are no inventory items.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Container</th>
                        <th>Status</th>
                        <th>Weight</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryReportRows as $index => $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <?php foreach (['location' => 'Location', 'container' => 'Container', 'status' => 'Status', 'weight' => 'Weight'] as $field => $label): ?>
                        <td>
                            <input type="text" name="rows[<?= $index ?>][<?= $field ?>]" class="report-input" placeholder="--"
                                   aria-label="<?= htmlspecialchars($row['category'] . ' ' . $label) ?>" value="<?= htmlspecialchars($row[$field]) ?>">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <button type="submit" name="generate-report" value="1" class="generate-report-btn">Generate Report</button>
    </form>
    <?php endif; ?>

    <?php if ($containerReportRows !== null): ?>
    <form method="post" action="createReports.php" class="report-section inventory-report">
        <input type="hidden" name="report-type" value="<?= htmlspecialchars($reportType) ?>">
        <h2><?= htmlspecialchars($reportType) ?></h2>
        <div class="report-info">
            <p><strong>Date:</strong> <?= htmlspecialchars($reportDate) ?></p>
        </div>
        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Container Date Entered</th>
                        <th>Container Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($containerReportRows as $index => $row): ?>
                    <tr>
                        <td>
                            <input type="text" name="rows[<?= $index ?>][date_entered]" class="report-input" placeholder="--"
                                   aria-label="Container Date Entered" value="<?= htmlspecialchars($row['date_entered']) ?>">
                        </td>
                        <td>
                            <input type="text" name="rows[<?= $index ?>][type]" class="report-input" placeholder="--"
                                   aria-label="Container Type" value="<?= htmlspecialchars($row['type']) ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" name="generate-report" value="1" class="generate-report-btn">Generate Report</button>
    </form>
    <?php endif; ?>

    <script>
        // Keep the To date from being before the From date while the user picks dates
        const fromDate = document.getElementById('from-date');
        const toDate = document.getElementById('to-date');
        if (fromDate && toDate) {
            fromDate.addEventListener('change', function () { toDate.min = fromDate.value; });
            toDate.addEventListener('change', function () { fromDate.max = toDate.value; });
        }
    </script>
</body>
</html>
