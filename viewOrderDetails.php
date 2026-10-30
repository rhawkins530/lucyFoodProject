<?php
session_cache_expire(30);
session_start();
ini_set("display_errors",1);
error_reporting(E_ALL);

if(!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/database/dbFoodOrder.php');
require_once(__DIR__ . '/database/dbFoodOrderItem.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = $id > 0 ? getFoodOrderById($id) : null;

if (!$order) {
    header('Location: viewOrders.php');
    exit;
}

$items = getFoodOrderItemsByOrderId($id);

// Categories with IDs
$CATEGORIES = [
    1 => 'Cereal/Oatmeal', 2 => 'Cooking Oil', 3 => 'Peanut Butter',
    4 => 'Jelly', 5 => 'Dried Pasta', 6 => 'Pasta Sauce',
    7 => 'Canned Chicken', 8 => 'Canned Tuna', 9 => 'Condiments',
    10 => 'Green Beans', 11 => 'Corn', 12 => 'Mixed Vegetables',
    13 => 'Baked Beans', 14 => 'Canned/Dried Beans', 15 => 'Canned Fruit (Mixed)',
    16 => 'Canned Peaches', 17 => 'Canned Pasta', 18 => 'Carrots',
    19 => 'Peas', 20 => 'Ramen', 21 => 'Milk',
    22 => 'Diced Tomatoes', 23 => 'Rice', 24 => 'Flour (Masa)',
    25 => 'Flour (White)', 26 => 'Soup', 27 => 'Juice',
    28 => 'Baking Goods', 29 => 'Macaroni & Cheese', 30 => 'Meal Helpers',
    31 => 'Dark Beans', 32 => 'Snacks/Cookies', 33 => 'Canned Meat',
    34 => 'Vegetable', 35 => 'Tomato Product', 36 => 'Tortilla',
    37 => 'Bagged Dry Bean',
];

// Display conversion for dates/times
function fmtDate($v, $withTime = true) {
    if ($v === null || $v === '') return null;
    $t = strtotime($v);
    if (!$t) return $v;
    return date($withTime ? 'M j, Y g:i A' : 'M j, Y', $t);
}
function fmtTime($v) {
    if ($v === null || $v === '') return null;
    $t = strtotime($v);
    return $t ? date('g:i A', $t) : $v;
}

$status = strtolower((string)$order->getStatus());

// Sections of fields: [label, getter names, formatter]
$sections = [
    'Order Info' => [
        ['Requestor ID',     'getRequestorPersonId', null],
        ['Approver ID',      'getApproverPersonId', null],
        ['Date Submitted',   'getSubmissionDate', 'fmtDate'],
        ['Approval Date',    'getApprovalDate', 'fmtDate'],
        ['Fulfillment Date', 'getFulfillmentDate', 'fmtDate'],
    ],
];

// Resolve values and drop empty fields/sections (display conversion)
$resolved = [];
foreach ($sections as $title => $fields) {
    foreach ($fields as [$label, $getter, $fmt]) {
        $v = $order->$getter();
        if ($v !== null && $v !== '') {
            if ($fmt) $v = $fmt($v);
            if ($v !== null && $v !== '') $resolved[$title][] = [$label, (string)$v];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Order #<?= $id ?> | CCDA</title>
    <link rel="stylesheet" href="css/base.css">
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
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--secondary-accent-color);
            font-weight: 600;
            text-decoration: none;
        }
        .back-link:hover { 
            text-decoration: underline;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem 2rem;
        }
        .detail-item .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #658A9C;
        }
        .detail-item .value {
            color: var(--page-font-color);
            font-weight: 500;
            word-break: break-word;
        }
        .report-table { 
            width: 100%;
            border-collapse: collapse;
            background: #D4E7F6; 
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
        }
        .report-table td.num, .report-table th.num {
            text-align: right; 
        }
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #658A9C; 
        }

        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.7rem;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
            vertical-align: middle;
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
            .report-table th, .report-table td {
                padding: 6px;
                font-size: 0.85rem; 
            }
        }
    </style>
</head>
<pageheader>
    <h1 class="title">Order #<?= $id ?></h1> <!-- Header for selected order -->
</pageheader>

<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <a class="back-link" href="viewOrders.php">&larr; Back to all orders</a> <!-- Back to orders list -->

            <div class="report-section">
                <h2>
                    Order Details
                    <span class="status-badge <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                </h2>
                <?php foreach ($resolved as $title => $rows): ?>
                    <h3 style="margin:1.25rem 0 .5rem; color: var(--secondary-accent-color);"><?= htmlspecialchars($title) ?></h3>
                    <div class="detail-grid">
                        <?php foreach ($rows as [$label, $v]): ?>
                            <div class="detail-item">
                                <div class="label"><?= htmlspecialchars($label) ?></div>
                                <div class="value"><?= htmlspecialchars($v) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="report-section">
                <h2>Items</h2>
                <?php if (empty($items)): ?>
                    <div class="empty-state">This order has no items.</div>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="num">Requested</th>
                                <th class="num">Delivered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $catId     = (int)$item->getItem_category_id();
                                $requested = (int)$item->getQuantity_requested();
                                $delivered = (int)$item->getQuantity_delivered();
                                $name      = $CATEGORIES[$catId] ?? ('Category #' . $catId);
                                $deliveredText = ($status === 'fulfilled' || $delivered > 0) ? (string)$delivered : '—';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($name) ?></td>
                                <td class="num"><?= $requested ?></td>
                                <td class="num"><?= htmlspecialchars($deliveredText) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>