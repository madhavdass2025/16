<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'get_invoice_items') {
    $invoice_number = $_GET['invoice_number'];
    $stmt = $mysqli->prepare("
        SELECT pi.p_item_id, p.product_name, sb.batch_number, pi.quantity_purchased, pur.purchase_id, sb.batch_id
        FROM purchase_items pi
        JOIN purchase_invoices pur ON pi.purchase_id = pur.purchase_id
        JOIN stock_batches sb ON pi.batch_id = sb.batch_id
        JOIN products p ON sb.product_id = p.product_id
        WHERE pur.invoice_number = ?
    ");
    $stmt->bind_param("s", $invoice_number);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_id = filter_input(INPUT_POST, 'purchase_id', FILTER_VALIDATE_INT);
    $return_date = $_POST['return_date'];
    $items = $_POST['items'] ?? [];

    if (!$purchase_id || !$return_date || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $mysqli->begin_transaction();
    try {
        $total_return_amount = 0;

        $stmt_batch_price = $mysqli->prepare("SELECT purchase_price FROM stock_batches WHERE batch_id = ?");

        foreach ($items as $item) {
            if ($item['quantity_returned'] > 0) {
                $stmt_batch_price->bind_param("i", $item['batch_id']);
                $stmt_batch_price->execute();
                $price_result = $stmt_batch_price->get_result()->fetch_assoc();
                $total_return_amount += $item['quantity_returned'] * $price_result['purchase_price'];
            }
        }

        $stmt_return = $mysqli->prepare("INSERT INTO purchase_returns (purchase_id, return_date, amount_credited) VALUES (?, ?, ?)");
        $stmt_return->bind_param("isd", $purchase_id, $return_date, $total_return_amount);
        $stmt_return->execute();
        $return_id = $mysqli->insert_id;

        $stmt_return_item = $mysqli->prepare("INSERT INTO purchase_return_items (return_id, batch_id, quantity_returned) VALUES (?, ?, ?)");
        $stmt_stock_update = $mysqli->prepare("UPDATE stock_batches SET current_qty = current_qty - ? WHERE batch_id = ?");

        foreach ($items as $item) {
            if ($item['quantity_returned'] > 0) {
                $stmt_return_item->bind_param("iii", $return_id, $item['batch_id'], $item['quantity_returned']);
                $stmt_return_item->execute();

                $stmt_stock_update->bind_param("ii", $item['quantity_returned'], $item['batch_id']);
                $stmt_stock_update->execute();
            }
        }

        // Ledger Entry: Debit Accounts Payable, Credit Inventory
        $ap_acc_id = 3;
        $inventory_acc_id = 2;
        record_ledger_entry($mysqli, $ap_acc_id, $inventory_acc_id, $total_return_amount, "Purchase Return for Invoice #{$purchase_id}");

        $mysqli->commit();
        echo json_encode(['status' => 'success', 'message' => 'Purchase return processed.']);

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>