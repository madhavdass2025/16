<?php
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports</h1>
</div>

<div class="list-group">
    <a href="ledger_report.php" class="list-group-item list-group-item-action">Ledger Report</a>
    <!-- Add links to other reports here -->
</div>

<?php
include 'includes/footer.php';
?>