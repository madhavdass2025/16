<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'search_products':
        $term = $_GET['term'] ?? '';
        $like_term = "%{$term}%";
        $stmt = $mysqli->prepare("SELECT product_id, product_name FROM products WHERE product_name LIKE ? LIMIT 10");
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'get_batches':
        $product_id = (int)$_GET['product_id'];
        $stmt = $mysqli->prepare("SELECT batch_id, batch_number, current_qty FROM stock_batches WHERE product_id = ? ORDER BY expiry_date ASC");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'adjust_stock':
        $batch_id = (int)$_POST['batch_id'];
        $new_qty = (int)$_POST['new_qty'];
        $reason = $_POST['reason'];
        $user_id = $_SESSION['user_id'];

        $mysqli->begin_transaction();
        try {
            // Get old quantity for logging
            $stmt_old_qty = $mysqli->prepare("SELECT current_qty FROM stock_batches WHERE batch_id = ?");
            $stmt_old_qty->bind_param("i", $batch_id);
            $stmt_old_qty->execute();
            $old_qty = $stmt_old_qty->get_result()->fetch_assoc()['current_qty'];

            // Update stock
            $stmt_update = $mysqli->prepare("UPDATE stock_batches SET current_qty = ? WHERE batch_id = ?");
            $stmt_update->bind_param("ii", $new_qty, $batch_id);
            $stmt_update->execute();

            // Log the adjustment
            $stmt_log = $mysqli->prepare("INSERT INTO stock_adjustment_log (batch_id, user_id, old_qty, new_qty, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt_log->bind_param("iiiis", $batch_id, $user_id, $old_qty, $new_qty, $reason);
            $stmt_log->execute();

            $mysqli->commit();
            echo json_encode(['status' => 'success', 'message' => 'Stock adjusted successfully.']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>