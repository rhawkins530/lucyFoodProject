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

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/database/dbReports.php');

$reports = getAllReports();
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
        .report-table tbody tr.report-row {
            cursor: pointer;
        }
        .report-table tbody tr.report-row:hover {
            background-color: rgba(255,255,255,0.6);
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
                <?php if (empty($reports)): ?>
                    <p class="empty-state">No Reports Available.</p>
                <?php else: ?>
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
                            <?php foreach ($reports as $r):
                                $reportType = $r['report_type'] ?? '';
                                $reportDate = $r['date_created'] ?? '';
                                $reportName = $r['name'] ?? 'Untitled Report';
                                $reportID = (int)($r['id'] ?? 0);
                            ?>
                                <tr class="report-row"
                                    data-type="<?= htmlspecialchars($reportType, ENT_QUOTES, 'UTF-8') ?>"
                                    data-date="<?= htmlspecialchars($reportDate, ENT_QUOTES, 'UTF-8') ?>"
                                    data-href="viewReportDetails.php?id=<?= $reportID ?>">
                                    <td>
                                        <a href="viewReportDetails.php?id=<?= $reportID ?>">
                                            <?= htmlspecialchars($reportName, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($reportType, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php
                                        $timestamp = $reportDate ? strtotime($reportDate) : false;
                                        echo $timestamp ? date('M j, Y', $timestamp) : '—';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noReportsRow" style="display:none;">
                                <td colspan="3" class="empty-state">No reports match this selection.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
                return Array.from(body.querySelectorAll('tr.report-row'));
            }

            dataRows().forEach(row => row.addEventListener('click', e => {
                if (e.target.closest('a')) return;
                window.location.href = row.dataset.href;
            }));

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