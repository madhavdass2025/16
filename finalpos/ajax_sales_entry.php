<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'get_product_details':
            $product_id = (int)$_GET['product_id'];
            $stmt = $mysqli->prepare("SELECT mrp, tax_rate FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_assoc());
            exit;
    }
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
        $total_amount = 0;

        $stmt_product = $mysqli->prepare("SELECT mrp, tax_rate FROM products WHERE product_id = ?");

        foreach ($items as $item) {
            $stmt_product->bind_param("i", $item['product_id']);
            $stmt_product->execute();
            $product_details = $stmt_product->get_result()->fetch_assoc();
            $total_amount += $product_details['mrp'];
        }

        $invoice_number = 'INV-' . time();

        $stmt_invoice = $mysqli->prepare("INSERT INTO sales_invoices (invoice_number, invoice_date, customer_id, user_id, net_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'Paid')");
        $stmt_invoice->bind_param("ssiids", $invoice_number, $invoice_date, $customer_id, $user_id, $total_amount, 'Paid');
        $stmt_invoice->execute();
        $invoice_id = $mysqli->insert_id;

        // The rest of the sale processing logic (invoice_items, stock update, ledger) would go here...

        $mysqli->commit();
        echo json_encode(['status' => 'success', 'message' => 'Sale processed successfully!']);

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
?>