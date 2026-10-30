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

$orders = getAllFoodOrders();

// Calls the specific FoodOrder getter
function orderValue($o, $getter) {
    return $o->$getter();
}

//
$columns = [
    'id'                  => ['label' => 'Order',            'type' => 'num',    'getter' => 'getId'],
    'requestor_person_id' => ['label' => 'Requestor ID',     'type' => 'num',    'getter' => 'getRequestorPersonId'],
    'approver_person_id'  => ['label' => 'Approver ID',      'type' => 'num',    'getter' => 'getApproverPersonId'],
    'order_status'        => ['label' => 'Status',           'type' => 'status', 'getter' => 'getStatus'],
    'submission_date'     => ['label' => 'Date Submitted',   'type' => 'date',   'getter' => 'getSubmissionDate'],
    'approval_date'       => ['label' => 'Approval Date',    'type' => 'date',   'getter' => 'getApprovalDate'],
    'fulfillment_date'    => ['label' => 'Fulfillment Date', 'type' => 'date',   'getter' => 'getFulfillmentDate'],
];

// Keeps only the columns where at least one order has a non-null value
$visibleColumns = [];
foreach ($columns as $key => $col) {
    foreach ($orders as $o) {
        $val = orderValue($o, $col['getter']);
        if ($val !== null && $val !== '') {
            $visibleColumns[$key] = $col;
            break;
        }
    }
}

// Formats a cell for display
function displayCell($type, $val) {
    if ($val === null || $val === '') return '—';
    if ($type === 'date') {
        $t = strtotime($val);
        return $t ? date('M j, Y g:i A', $t) : $val;
    }
    if ($type === 'status') return ucfirst(strtolower($val));
    return $val;
}

// Value used for sorting/filtering (dates become sortable timestamps)
function sortCell($type, $val) {
    if ($val === null || $val === '') return '';
    if ($type === 'date') {
        $t = strtotime($val);
        return $t ? (string)$t : '';
    }
    if ($type === 'status') return strtolower($val);
    return (string)$val;
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>View Food Orders | CCDA</title>
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
            border-bottom: 1px solid var(--shadow-and-border-color);
            color: var(--page-font-color);
            vertical-align: middle;
        }
        .report-table th {
            background-color: var(--main-color);
            color: var(--button-font-color);
            font-weight: 500;
            position: sticky;
            top: 100px;
        }
        .report-table tbody tr.order-row { 
            cursor: pointer; 
        }
        .report-table tbody tr.order-row:hover { 
            background-color: rgba(255,255,255,0.6); 
        }
        .report-table td a { 
            color: inherit; 
            font-weight: 600; 
            text-decoration: underline; 
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #658A9C;
        }
        .week-and-filter-section {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            justify-content: space-between;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .form-section { margin-bottom: 1.5rem; }
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

        .button {
            display: flex;
            margin-left: auto;
            width: 25%;
            height: 5%;
            margin-bottom: auto;
        }
        .button:hover { 
            opacity: 0.85;
        }

        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
        }
        .status-badge.pending   {
            background: #fef3c7; 
            color: #92400e; 
        }
        .status-badge.approved  { 
            background: #d1fae5;
            color: #065f46; 
        }
        .status-badge.rejected  { 
            background: #fee2e2; 
            color: #991b1b; 
        }
        .status-badge.fulfilled { 
            background: #dbeafe; 
            color: #1e40af; 
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
            .report-container {
                padding: 0.5rem; 
            }
            .report-section { 
                padding: 0; 
            }
            .report-table th,
            .report-table td { 
                padding: 4px; 
                font-size: 0.8rem; 
                position: static; 
            }
        }
    </style>
</head>
<pageheader>
    <h1 class="title">Food Order View</h1> <!-- Page title header --> 
</pageheader>

<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <div class="report-section">
                <h2>Food Orders</h2>
                <div class="week-and-filter-section">
                    <div class="form-section">
                        <label for="statusSelect">Filter by Status:</label> 
                        <select id="statusSelect">
                            <option value="all">Show All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="fulfilled">Fulfilled</option>
                        </select>
                    </div>
                    <a class="button" href="reviewOrders.php">Approve/Reject Orders</a>
                </div>
                <hr>
                <br>
                <?php if (empty($orders)): ?>
                    <div class="empty-state">No Orders Available</div>
                <?php else: ?>
                <div class="order-list-section">
                    <table class="report-table" id="orderTable">
                        <thead>
                            <tr>
                                <?php foreach ($visibleColumns as $key => $col): ?>
                                    <th class="sortable" data-sort="<?= $key ?>" data-type="<?= $col['type'] ?>">
                                        <?= htmlspecialchars($col['label']) ?> <span class="arrow"></span>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody id="orderBody">
                            <?php foreach ($orders as $o):
                                $orderId = (int)orderValue($o, $columns['id']['getter']);
                                $status  = strtolower((string)orderValue($o, $columns['order_status']['getter']));
                                $url     = 'viewOrderDetails.php?id=' . $orderId;
                                $attrs = 'data-status="' . htmlspecialchars($status) . '"';
                                foreach ($visibleColumns as $key => $col) {
                                    $attrs .= ' data-' . $key . '="' . htmlspecialchars(sortCell($col['type'], orderValue($o, $col['getter']))) . '"';
                                }
                            ?>
                            <tr class="order-row" <?= $attrs ?> data-href="<?= htmlspecialchars($url) ?>">
                                <?php foreach ($visibleColumns as $key => $col):
                                    $val  = orderValue($o, $col['getter']);
                                    $text = displayCell($col['type'], $val);
                                ?>
                                    <td>
                                        <?php if ($key === 'id'): ?>
                                            <a href="<?= htmlspecialchars($url) ?>">#<?= htmlspecialchars((string)$text) ?></a>
                                        <?php elseif ($col['type'] === 'status'): ?>
                                            <span class="status-badge <?= htmlspecialchars($status) ?>"><?= htmlspecialchars((string)$text) ?></span>
                                        <?php else: ?>
                                            <?= htmlspecialchars((string)$text) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noOrdersRow" style="display:none;">
                                <td colspan="<?= max(1, count($visibleColumns)) ?>" class="empty-state">No orders match this filter</td>
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
            const body = document.getElementById('orderBody');
            if (!body) return;                              // No orders 
            const noRow = document.getElementById('noOrdersRow');
            const statusSelect = document.getElementById('statusSelect');
            const headers = document.querySelectorAll('th.sortable');
            const statusRank = { pending: 1, approved: 2, rejected: 3, fulfilled: 4 };

            let sortKey = document.querySelector('th[data-sort="submission_date"]') ? 'submission_date' : 'id';
            let sortDir = 'desc';                           // Newest first

            const dataRows = () => Array.from(body.querySelectorAll('tr.order-row'));

            // Click anywhere on a row to open that order
            dataRows().forEach(r => r.addEventListener('click', e => {
                if (e.target.closest('a')) return;         
                window.location.href = r.dataset.href;
            }));
 
            function applyFilter() {
                const sel = statusSelect.value;
                let visible = 0;
                dataRows().forEach(r => {
                    const show = sel === 'all' || r.dataset.status === sel;
                    r.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                noRow.style.display = visible === 0 ? '' : 'none';
            }

            function applySort() {
                const type = document.querySelector('th[data-sort="' + sortKey + '"]').dataset.type;
                const rows = dataRows();
                rows.sort((a, b) => {
                    const av = a.dataset[sortKey] ?? '', bv = b.dataset[sortKey] ?? '';
                    if (av === '' && bv === '') return 0;
                    if (av === '') return 1;                // Empty values always go last
                    if (bv === '') return -1;
                    let cmp;
                    if (type === 'num' || type === 'date') cmp = Number(av) - Number(bv);
                    else if (type === 'status')            cmp = (statusRank[av] ?? 99) - (statusRank[bv] ?? 99);
                    else                                   cmp = av.localeCompare(bv);
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
                    sortDir = (h.dataset.type === 'date' || key === 'id') ? 'desc' : 'asc';
                }
                applySort();
            }));

            statusSelect.addEventListener('change', applyFilter);
            applySort();
            applyFilter();
        })();
    </script>
</body>
</html>