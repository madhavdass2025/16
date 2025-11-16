<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reports</h1>
    </div>

    <div class="list-group">
        <a href="ledger_report.php" class="list-group-item list-group-item-action">Ledger Report</a>
        <!-- Add links to other reports here as they are created -->
    </div>
</main>

<?php
include 'includes/footer.php';
?>