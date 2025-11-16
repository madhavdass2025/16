<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Filtering logic
$where_clauses = [];
if (!empty($_GET['start_date'])) {
    $where_clauses[] = "al.transaction_date >= '" . $mysqli->real_escape_string($_GET['start_date']) . "'";
}
if (!empty($_GET['end_date'])) {
    $where_clauses[] = "al.transaction_date <= '" . $mysqli->real_escape_string($_GET['end_date']) . "'";
}
if (!empty($_GET['account_id'])) {
    $account_id = (int)$_GET['account_id'];
    $where_clauses[] = "(al.debit_account_id = {$account_id} OR al.credit_account_id = {$account_id})";
}
$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$ledger_result = $mysqli->query("
    SELECT
        al.transaction_date,
        debit_ac.account_name as debit_account,
        credit_ac.account_name as credit_account,
        al.amount,
        al.narration
    FROM accounts_ledger al
    JOIN chart_of_accounts debit_ac ON al.debit_account_id = debit_ac.account_id
    JOIN chart_of_accounts credit_ac ON al.credit_account_id = credit_ac.account_id
    {$where_sql}
    ORDER BY al.transaction_date DESC
");
$ledger_entries = $ledger_result->fetch_all(MYSQLI_ASSOC);

// For the filter dropdown
$accounts_result = $mysqli->query("SELECT account_id, account_name FROM chart_of_accounts ORDER BY account_name ASC");
$accounts = $accounts_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Ledger Report</h1>
    </div>

    <form id="ledger-filter-form" class="mb-3" method="GET" action="ledger_report.php">
        <div class="row">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
            </div>
            <div class="col-md-4">
                <label for="account_id" class="form-label">Account</label>
                <select class="form-select" id="account_id" name="account_id">
                    <option value="">All Accounts</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['account_id']; ?>" <?php echo (isset($_GET['account_id']) && $_GET['account_id'] == $account['account_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['account_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Debit Account</th>
                    <th scope="col">Credit Account</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Narration</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ledger_entries)): ?>
                    <?php foreach ($ledger_entries as $entry): ?>
                        <tr>
                            <td><?php echo date("Y-m-d H:i:s", strtotime($entry['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['debit_account']); ?></td>
                            <td><?php echo htmlspecialchars($entry['credit_account']); ?></td>
                            <td><?php echo number_format($entry['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($entry['narration']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No ledger entries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php
include 'includes/footer.php';
?>