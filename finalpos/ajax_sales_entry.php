<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

// --- Product Search Endpoint ---
if (isset($_GET['action']) && $_GET['action'] == 'search_products') {
    $term = $_GET['term'] ?? '';
    $like_term = "%{$term}%";
    $stmt = $mysqli->prepare("SELECT product_id, product_name, mrp FROM products WHERE product_name LIKE ? LIMIT 10");
    $stmt->bind_param("s", $like_term);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

// --- Sale Processing Endpoint ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];
$items = $_POST['items'] ?? [];

if (!$customer_id || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Customer and items are required.']);
    exit;
}

$mysqli->begin_transaction();

try {
    $total_amount = 0;
    $total_cogs = 0;

    foreach ($items as $item) {
        $total_amount += ($item['quantity'] * $item['price']);
    }

    $invoice_number = 'INV-' . time(); // Simple unique invoice number
    $invoice_date = date('Y-m-d H:i:s');

    $stmt_invoice = $mysqli->prepare("INSERT INTO sales_invoices (invoice_number, invoice_date, customer_id, user_id, net_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'Paid')");
    $stmt_invoice->bind_param("ssiid", $invoice_number, $invoice_date, $customer_id, $user_id, $total_amount);
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
            throw new Exception("Not enough stock for product ID #{$product_id}.");
        }

        foreach ($batches as $batch) {
            if ($quantity_to_sell <= 0) break;

            $qty_from_this_batch = min($quantity_to_sell, $batch['current_qty']);

            $stmt_invoice_item->bind_param("iiid", $invoice_id, $batch['batch_id'], $qty_from_this_batch, $item['price']);
            $stmt_invoice_item->execute();

            $stmt_stock_update->bind_param("ii", $qty_from_this_batch, $batch['batch_id']);
            $stmt_stock_update->execute();

            $total_cogs += $qty_from_this_batch * $batch['purchase_price'];
            $quantity_to_sell -= $qty_from_this_batch;
        }
    }

    // Ledger Entry: Debit Cash, Credit Sales Revenue
    $cash_acc_id = 1; // From initial data
    $sales_rev_acc_id = 4; // From initial data
    record_ledger_entry($mysqli, $cash_acc_id, $sales_rev_acc_id, $total_amount, "Sale Invoice #{$invoice_number}");

    // Ledger Entry: Debit COGS, Credit Inventory
    $cogs_acc_id = 5; // From initial data
    $inventory_acc_id = 2; // From initial data
    record_ledger_entry($mysqli, $cogs_acc_id, $inventory_acc_id, $total_cogs, "COGS for Sale Invoice #{$invoice_number}");

    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale processed successfully!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>