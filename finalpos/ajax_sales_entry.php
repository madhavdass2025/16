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
        $total_amount = 0;
        $total_cogs = 0;

        // First, validate quantities and calculate totals
        foreach ($items as &$item) { // Pass by reference to add details
            $stmt_product = $mysqli->prepare("SELECT mrp, tax_rate FROM products WHERE product_id = ?");
            $stmt_product->bind_param("i", $item['product_id']);
            $stmt_product->execute();
            $product_details = $stmt_product->get_result()->fetch_assoc();
            $item['mrp'] = $product_details['mrp'];
            $total_amount += $item['quantity'] * $item['mrp'];
        }
        unset($item);

        $invoice_number = 'INV-' . time();
        $stmt_invoice = $mysqli->prepare("INSERT INTO sales_invoices (invoice_number, invoice_date, customer_id, user_id, net_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'Paid')");
        $stmt_invoice->bind_param("ssiids", $invoice_number, $invoice_date, $customer_id, $user_id, $total_amount, 'Paid');
        $stmt_invoice->execute();
        $invoice_id = $mysqli->insert_id;

        $stmt_invoice_item = $mysqli->prepare("INSERT INTO invoice_items (invoice_id, batch_id, quantity_sold, selling_price_at_sale) VALUES (?, ?, ?, ?)");
        $stmt_stock_select = $mysqli->prepare("SELECT batch_id, current_qty, purchase_price FROM stock_batches WHERE product_id = ? AND current_qty > 0 ORDER BY expiry_date ASC");
        $stmt_stock_update = $mysqli->prepare("UPDATE stock_batches SET current_qty = current_qty - ? WHERE batch_id = ?");

        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $quantity_to_sell = $item['quantity'];

            $stmt_stock_select->bind_param("i", $product_id);
            $stmt_stock_select->execute();
            $batches = $stmt_stock_select->get_result()->fetch_all(MYSQLI_ASSOC);

            $available_qty = array_sum(array_column($batches, 'current_qty'));
            if ($available_qty < $quantity_to_sell) {
                throw new Exception("Not enough stock for a product in your cart.");
            }

            foreach ($batches as $batch) {
                if ($quantity_to_sell <= 0) break;
                $qty_from_this_batch = min($quantity_to_sell, $batch['current_qty']);

                $stmt_invoice_item->bind_param("iiid", $invoice_id, $batch['batch_id'], $qty_from_this_batch, $item['mrp']);
                $stmt_invoice_item->execute();

                $stmt_stock_update->bind_param("ii", $qty_from_this_batch, $batch['batch_id']);
                $stmt_stock_update->execute();

                $total_cogs += $qty_from_this_batch * $batch['purchase_price'];
                $quantity_to_sell -= $qty_from_this_batch;
            }
        }

        record_ledger_entry($mysqli, CASH_ACCOUNT_ID, SALES_REVENUE_ID, $total_amount, "Sale Invoice #{$invoice_number}");
        record_ledger_entry($mysqli, COGS_ID, INVENTORY_ACCOUNT_ID, $total_cogs, "COGS for Sale #{$invoice_number}");

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