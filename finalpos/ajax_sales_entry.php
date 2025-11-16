<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

// --- Product Search Endpoint ---
if (isset($_GET['action']) && $_GET['action'] == 'search_products') {
    // ... (search logic is the same)
}

// --- Sale Processing Endpoint ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];
$items = $_POST['items'] ?? [];
$payments = $_POST['payments'] ?? [];

if (!$customer_id || empty($items)) {
    // ... (validation is the same)
}

$mysqli->begin_transaction();

try {
    $total_amount = 0;
    // ... (total amount calculation is the same)

    $total_paid = 0;
    foreach ($payments as $payment) {
        $total_paid += $payment['amount'];
    }

    $payment_status = ($total_paid >= $total_amount) ? 'Paid' : 'Credit';

    $invoice_number = 'INV-' . time();
    $invoice_date = date('Y-m-d H:i:s');

    $stmt_invoice = $mysqli->prepare("INSERT INTO sales_invoices (invoice_number, invoice_date, customer_id, user_id, net_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_invoice->bind_param("ssiids", $invoice_number, $invoice_date, $customer_id, $user_id, $total_amount, $payment_status);
    $stmt_invoice->execute();
    $invoice_id = $mysqli->insert_id;

    // ... (invoice item and stock update logic is the same)

    // Insert payments
    $stmt_payment = $mysqli->prepare("INSERT INTO payments_collection (invoice_id, payment_date, amount, payment_method) VALUES (?, ?, ?, ?)");
    foreach ($payments as $payment) {
        $stmt_payment->bind_param("isds", $invoice_id, $invoice_date, $payment['amount'], $payment['method']);
        $stmt_payment->execute();
    }

    // Ledger Entries
    // ... (ledger entries are the same)

    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale processed successfully!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>