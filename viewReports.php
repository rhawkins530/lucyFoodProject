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

// Show one report when its id is given (Create Reports redirects here after generating one).
// For now the report is only printed on this page. $reportDate is the date it was generated.
$report = null;
$reportDate = null;
$inventoryReportRows = null;
$containerReportRows = null;
// Report types that also show the Total Inventory Weight line under the date
$reportTypesWithTotalWeight = ['End of Year Report', 'Quarterly Report'];
if (isset($_GET['id'])) {
    $report = getReportById($_GET['id']);
}
if ($report !== null) {
    $reportDate = date('m/d/Y', strtotime($report['date_created']));

    // End of Year, Quarterly, and Total Available Inventory Reports list the inventory items.
    // Category comes from dbinventory; the other fields aren't tracked yet, so they show "--".
    if (in_array($report['report_type'], ['End of Year Report', 'Quarterly Report', 'Total Available Inventory Report'], true)) {
        $inventoryReportRows = [];
        foreach (getAllInventoryItems() as $inventoryItem) {
            $inventoryReportRows[] = [
                'category' => $inventoryItem->getCategoryName(),
                'location' => '--',
                'container' => '--',
                'status' => '--',
                'weight' => '--'
            ];
        }
    }

    // Container Date Entered Report: every container's date entered and type, oldest date first.
    // Containers aren't stored in the database yet, so the list is empty for now. Each container
    // should be added as ['date_entered' => 'YYYY-MM-DD', 'type' => '...'].
    if ($report['report_type'] === 'Container Date Entered Report') {
        $containerReportRows = [];
        // Oldest date first; containers without a date entered go at the bottom
        usort($containerReportRows, function ($a, $b) {
            $aTime = empty($a['date_entered']) ? PHP_INT_MAX : strtotime($a['date_entered']);
            $bTime = empty($b['date_entered']) ? PHP_INT_MAX : strtotime($b['date_entered']);
            return $aTime <=> $bTime;
        });
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>View Reports | CCDA</title>
    <style>
        pageheader {
            margin-top: 3rem;
            display: flex; justify-content: center; align-items: center;
        }
        .title {
            text-align: center;
            height: 3.5rem;
            width: auto;
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
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
            vertical-align: middle;
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
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--inactive-font-color);
        }
        .week-and-filter-section {
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
        .order-list-section {
            display: block;
            background-color: #D4E7F6;
            padding: 10px;
        }
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
        .report-message {
            display: flex;
            max-width: fit-content;
            margin: 0 auto 1rem auto;
            border-radius: 0.5rem;
            background-color: #D4E7F6;
            padding: 0.5rem;
            color: green;
            text-align: center;
        }
        .report-info {
            margin-bottom: 1rem;
        }
        .report-info p {
            margin: 0 0 0.25rem 0;
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
                padding: 4px;
                font-size: 0.8rem;
                position: static;
            }
            .report-container {
                padding: 0.5rem;
            }
            .report-section {
                padding: 0;
            }
        }
    </style>
</head>
<pageheader>
    <h1 class="title">View Reports</h1>
</pageheader>

<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <?php if ($report !== null && isset($_GET['generated'])): ?>
                <div class="report-message">Your report has been generated.</div>
            <?php endif; ?>
            <?php if (isset($_GET['id']) && $report === null): ?>
                <div class="report-message" style="color: red;">That report couldn't be found.</div>
            <?php endif; ?>

            <?php if ($inventoryReportRows !== null): ?>
            <div class="report-section">
                <h2><?= htmlspecialchars($report['report_type']) ?></h2>
                <div class="report-info">
                    <p><strong>Date:</strong> <?= htmlspecialchars($reportDate) ?></p>
                    <?php if (in_array($report['report_type'], $reportTypesWithTotalWeight, true)): ?>
                        <!-- Weights aren't tracked yet, so the total can't be calculated -->
                        <p><strong>Total Inventory Weight:</strong> --</p>
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
                            <?php foreach ($inventoryReportRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= htmlspecialchars($row['container']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['weight']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($containerReportRows !== null): ?>
            <div class="report-section">
                <h2><?= htmlspecialchars($report['report_type']) ?></h2>
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
                            <?php if (empty($containerReportRows)): ?>
                            <tr>
                                <td>--</td>
                                <td>--</td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($containerReportRows as $row): ?>
                            <tr>
                                <!-- Missing info shows as "--" -->
                                <td><?= empty($row['date_entered']) ? '--' : htmlspecialchars(date('m/d/Y', strtotime($row['date_entered']))) ?></td>
                                <td><?= empty($row['type']) ? '--' : htmlspecialchars($row['type']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="report-section">
                <h2>Reports</h2>
                <div class="week-and-filter-section">
                    <div class="form-section">
                        <label for="typeSelect">Select Report Type:</label>
                        <select id="typeSelect">
                            <option value="all">Show All</option>
                            <option value="End of Year Report">End of Year Reports</option>
                            <option value="Quarterly Report">Quarterly Reports</option>
                            <option value="Container Date Entered Report">Container Date Entered Reports</option>
                            <option value="Total Available Inventory Report">Total Available Inventory Reports</option>
                        </select>
                    </div>
                </div>
                <hr>
                <br>
                <?php
                    //placeholder data.
                    //dates are YYYY-MM-DD so they sort correctly.
                    $reports = [
                        ['name' => '2024 End of Year Report',          'file' => '', 'type' => 'End of Year Report',               'date' => '2025-01-06'],
                        ['name' => '2025 End of Year Report',          'file' => '', 'type' => 'End of Year Report',               'date' => '2026-01-05'],
                        ['name' => '2025 Q4 Quarterly Report',         'file' => '', 'type' => 'Quarterly Report',                 'date' => '2026-01-07'],
                        ['name' => '2026 Q1 Quarterly Report',         'file' => '', 'type' => 'Quarterly Report',                 'date' => '2026-04-03'],
                        ['name' => '2026 Q2 Quarterly Report',         'file' => '', 'type' => 'Quarterly Report',                 'date' => '2026-07-02'],
                        ['name' => 'Container Date Entered Report Mar 2026',    'file' => '', 'type' => 'Container Date Entered Report',    'date' => '2026-03-18'],
                        ['name' => 'Container Date Entered Report Sep 2026',    'file' => '', 'type' => 'Container Date Entered Report',    'date' => '2026-09-14'],
                        ['name' => 'Total Available Inventory Report Aug 2026', 'file' => '', 'type' => 'Total Available Inventory Report', 'date' => '2026-08-21'],
                        ['name' => 'Total Available Inventory Report Oct 2026', 'file' => '', 'type' => 'Total Available Inventory Report', 'date' => '2026-10-30'],
                    ];
                ?>
                <div class="order-list-section">
                    <table class="report-table" id="reportTable">
                        <thead>
                            <tr>
                                <th>Report</th>
                                <th class="sortable" data-sort="type">Type <span class="arrow"></span></th>
                                <th class="sortable" data-sort="date">Date Created <span class="arrow"></span></th>
                            </tr>
                        </thead>
                        <tbody id="reportBody">
                            <?php foreach ($reports as $r): ?>
                                <tr data-type="<?= htmlspecialchars($r['type']) ?>"
                                    data-date="<?= htmlspecialchars($r['date']) ?>">
                                    <td>
                                        <a href="<?= htmlspecialchars($r['file']) ?>" target="_blank" rel="noopener noreferrer">
                                            <?= htmlspecialchars($r['name']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($r['type']) ?></td>
                                    <td><?= date('M j, Y', strtotime($r['date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noReportsRow" style="display:none;">
                                <td colspan="3" class="empty-state">No reports match this selection.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const typeSelect = document.getElementById('typeSelect');
            const body = document.getElementById('reportBody');
            const noRow = document.getElementById('noReportsRow');
            const headers = document.querySelectorAll('th.sortable');
            let sortKey = 'date';
            let sortDir = 'desc'; //newest first

            function dataRows() {
                return Array.from(body.querySelectorAll('tr[data-type]'));
            }

            function applyFilter() {
                const selected = typeSelect.value;
                let visible = 0;
                dataRows().forEach(row => {
                    const show = selected === 'all' || row.dataset.type === selected;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                noRow.style.display = visible === 0 ? '' : 'none';
            }

            function applySort() {
                const rows = dataRows();
                rows.sort((a, b) => {
                    const cmp = a.dataset[sortKey].localeCompare(b.dataset[sortKey], undefined, { sensitivity: 'base' });
                    return sortDir === 'asc' ? cmp : -cmp;
                });
                rows.forEach(r => body.insertBefore(r, noRow));

                headers.forEach(h => {
                    h.querySelector('.arrow').textContent =
                        h.dataset.sort === sortKey ? (sortDir === 'asc' ? '▲' : '▼') : '';
                });
            }

            headers.forEach(h => h.addEventListener('click', () => {
                const key = h.dataset.sort;
                if (key === sortKey) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey = key;
                    sortDir = key === 'date' ? 'desc' : 'asc';
                }
                applySort();
            }));

            typeSelect.addEventListener('change', applyFilter);

            applySort();
            applyFilter();
        })();
    </script>
</body>
</html>