<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ... (GET logic remains the same)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    $invoice_date = $_POST['invoice_date'];
    $items = $_POST['items'] ?? [];

    if (!$customer_id || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer and items are required.']);
        exit;
    }

    $mysqli->begin_transaction();

    try {
        // ... (sale processing logic remains the same)

        $mysqli->commit();
        echo json_encode(['status' => 'success', 'message' => 'Sale processed successfully!', 'invoice_id' => $invoice_id]);

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
?>