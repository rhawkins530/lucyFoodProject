<?php
session_cache_expire(30);
session_start();

$loggedIn = isset($_SESSION['_id']);
$accessLevel = $_SESSION['access_level'] ?? 0;
$userID = $_SESSION['_id'] ?? null;

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/database/dbReports.php');

$reportID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$report = ($reportID && $reportID > 0) ? getReportById($reportID) : null;

$reportDate = '';
if ($report && !empty($report['date_created'])) {
    $timestamp = strtotime($report['date_created']);
    $reportDate = $timestamp ? date('M j, Y', $timestamp) : $report['date_created'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Report Details | CCDA</title>
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
            min-height: 3.5rem;
            width: auto;
            font-size: 2rem;
            font-weight: 600;
            color: var(--secondary-accent-color);
            padding: .4rem 1rem;
            border-radius: 10px;
            background-color: #ffffffee;
        }
        .report-container {
            max-width: 1100px;
            margin: 0 auto 4rem;
            padding: 1rem;
        }
        .report-section {
            background-color: #D4E7F6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .report-section h2 {
            color: var(--secondary-accent-color);
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        .detail-row {
            margin: .8rem 0;
            overflow-wrap: anywhere;
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
        }
    </style>
</head>
<body>
    <?php require_once('header.php'); ?>

    <pageheader>
        <h1 class="title">Report Details</h1>
    </pageheader>

    <main>
        <div class="report-container">
            <section class="report-section">
                <?php if (!$report): ?>
                    <h2>Report Not Found</h2>
                    <p class="empty-state">
                        This report could not be found. It may have been removed or the link may be invalid.
                    </p>
                <?php else: ?>
                    <h2><?= htmlspecialchars($report['name'] ?? 'Untitled Report', ENT_QUOTES, 'UTF-8') ?></h2>

                    <p class="detail-row">
                        <strong>Report Type:</strong>
                        <?= htmlspecialchars($report['report_type'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <p class="detail-row">
                        <strong>Date Created:</strong>
                        <?= htmlspecialchars($reportDate ?: '—', ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <p class="detail-row">
                        <strong>Report ID:</strong>
                        <?= (int)$report['id'] ?>
                    </p>

                    <!-- PDF link/report contents can be added here later. -->
                <?php endif; ?>

                <a class="back-link" href="viewReports.php">← Back to Reports</a>
            </section>
        </div>
    </main>
</body>
</html>
