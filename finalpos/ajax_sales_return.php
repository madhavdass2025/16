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
        SELECT ii.item_id, p.product_name, sb.batch_number, ii.quantity_sold, si.invoice_id, sb.batch_id, ii.selling_price_at_sale
        FROM invoice_items ii
        JOIN sales_invoices si ON ii.invoice_id = si.invoice_id
        JOIN stock_batches sb ON ii.batch_id = sb.batch_id
        JOIN products p ON sb.product_id = p.product_id
        WHERE si.invoice_number = ?
    ");
    $stmt->bind_param("s", $invoice_number);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
    $return_date = $_POST['return_date'];
    $items = $_POST['items'] ?? [];

    if (!$invoice_id || !$return_date || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $mysqli->begin_transaction();
    try {
        $total_refund_amount = 0;

        $stmt_item_price = $mysqli->prepare("SELECT selling_price_at_sale FROM invoice_items WHERE item_id = ?");

        foreach ($items as $item_id => $item) {
            if ($item['quantity_returned'] > 0) {
                $stmt_item_price->bind_param("i", $item_id);
                $stmt_item_price->execute();
                $price_result = $stmt_item_price->get_result()->fetch_assoc();
                $total_refund_amount += $item['quantity_returned'] * $price_result['selling_price_at_sale'];
            }
        }

        $stmt_return = $mysqli->prepare("INSERT INTO sales_returns (invoice_id, return_date, refund_amount) VALUES (?, ?, ?)");
        $stmt_return->bind_param("isd", $invoice_id, $return_date, $total_refund_amount);
        $stmt_return->execute();
        $s_return_id = $mysqli->insert_id;

        $stmt_return_item = $mysqli->prepare("INSERT INTO sales_return_items (s_return_id, batch_id, quantity_returned) VALUES (?, ?, ?)");
        $stmt_stock_update = $mysqli->prepare("UPDATE stock_batches SET current_qty = current_qty + ? WHERE batch_id = ?");

        foreach ($items as $item_id => $item) {
            if ($item['quantity_returned'] > 0) {
                $stmt_return_item->bind_param("iii", $s_return_id, $item['batch_id'], $item['quantity_returned']);
                $stmt_return_item->execute();

                $stmt_stock_update->bind_param("ii", $item['quantity_returned'], $item['batch_id']);
                $stmt_stock_update->execute();
            }
        }

        // Ledger Entry: Debit Sales Return, Credit Cash
        $sales_return_acc_id = 6;
        $cash_acc_id = 1;
        record_ledger_entry($mysqli, $sales_return_acc_id, $cash_acc_id, $total_refund_amount, "Sales Return for Invoice #{$invoice_id}");

        $mysqli->commit();
        echo json_encode(['status' => 'success', 'message' => 'Sales return processed.']);

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>