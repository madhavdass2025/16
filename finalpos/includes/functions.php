<?php
function check_access($required_roles) {
    if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], $required_roles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

function record_ledger_entry($mysqli, $debit_id, $credit_id, $amount, $narration) {
    $stmt = $mysqli->prepare("INSERT INTO accounts_ledger (transaction_date, debit_account_id, credit_account_id, amount, narration) VALUES (NOW(), ?, ?, ?, ?)");
    $stmt->bind_param("iids", $debit_id, $credit_id, $amount, $narration);
    $stmt->execute();
    $stmt->close();
}
?>