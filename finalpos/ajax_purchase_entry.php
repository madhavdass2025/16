<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
$invoice_number = $_POST['invoice_number'];
$invoice_date = $_POST['invoice_date'];
$items = $_POST['items'] ?? [];

if (!$supplier_id || !$invoice_number || !$invoice_date || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$mysqli->begin_transaction();

try {
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += ($item['quantity'] * $item['purchase_price']);
    }

    $stmt_invoice = $mysqli->prepare("INSERT INTO purchase_invoices (supplier_id, invoice_number, invoice_date, total_amount, payment_status) VALUES (?, ?, ?, ?, 'Credit')");
    $stmt_invoice->bind_param("isds", $supplier_id, $invoice_number, $invoice_date, $total_amount);
    $stmt_invoice->execute();
    $purchase_id = $mysqli->insert_id;

    $stmt_stock_upsert_select = $mysqli->prepare("SELECT batch_id, current_qty FROM stock_batches WHERE product_id = ? AND batch_number = ?");
    $stmt_stock_update = $mysqli->prepare("UPDATE stock_batches SET current_qty = ?, purchase_price = ?, selling_price = ? WHERE batch_id = ?");
    $stmt_stock_insert = $mysqli->prepare("INSERT INTO stock_batches (product_id, batch_number, purchase_price, selling_price, current_qty, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_purchase_item = $mysqli->prepare("INSERT INTO purchase_items (purchase_id, batch_id, quantity_purchased, unit_cost) VALUES (?, ?, ?, ?)");

    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $batch_number = $item['batch_number'];
        $purchase_price = $item['purchase_price'];
        $selling_price = $item['selling_price'];
        $quantity = $item['quantity'];
        $expiry_date = $item['expiry_date'];
        $batch_id = null;

        $stmt_stock_upsert_select->bind_param("is", $product_id, $batch_number);
        $stmt_stock_upsert_select->execute();
        $result = $stmt_stock_upsert_select->get_result();
        $existing_batch = $result->fetch_assoc();

        if ($existing_batch) {
            $batch_id = $existing_batch['batch_id'];
            $new_qty = $existing_batch['current_qty'] + $quantity;
            $stmt_stock_update->bind_param("iddi", $new_qty, $purchase_price, $selling_price, $batch_id);
            $stmt_stock_update->execute();
        } else {
            $stmt_stock_insert->bind_param("isddis", $product_id, $batch_number, $purchase_price, $selling_price, $quantity, $expiry_date);
            $stmt_stock_insert->execute();
            $batch_id = $mysqli->insert_id;
        }

        $stmt_purchase_item->bind_param("iiid", $purchase_id, $batch_id, $quantity, $purchase_price);
        $stmt_purchase_item->execute();
    }

    // Ledger Entry: Debit Inventory, Credit Accounts Payable
    record_ledger_entry($mysqli, INVENTORY_ACCOUNT_ID, ACCOUNTS_PAYABLE_ID, $total_amount, "Purchase Invoice #{$invoice_number}");

    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Purchase recorded successfully!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>