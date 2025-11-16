<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_product':
        $product_id = (int)$_GET['product_id'];
        $stmt = $mysqli->prepare("SELECT p.product_id, p.product_name, p.mrp, p.tax_rate, p.category_id, rl.reorder_level FROM products p LEFT JOIN reorder_levels rl ON p.product_id = rl.product_id WHERE p.product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_product':
        $product_id = (int)$_POST['product_id'];
        $product_name = $_POST['product_name'];
        $mrp = (float)$_POST['mrp'];
        $tax_rate = (float)$_POST['tax_rate'];
        $category_id = empty($_POST['category_id']) ? null : (int)$_POST['category_id'];
        $reorder_level = (int)$_POST['reorder_level'];

        $mysqli->begin_transaction();
        try {
            if ($product_id > 0) { // Update
                $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, mrp = ?, tax_rate = ?, category_id = ? WHERE product_id = ?");
                $stmt->bind_param("sddii", $product_name, $mrp, $tax_rate, $category_id, $product_id);
                $stmt->execute();
            } else { // Insert
                $stmt = $mysqli->prepare("INSERT INTO products (product_name, mrp, tax_rate, category_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sddi", $product_name, $mrp, $tax_rate, $category_id);
                $stmt->execute();
                $product_id = $mysqli->insert_id;
            }

            // Upsert reorder level
            $stmt_reorder = $mysqli->prepare("INSERT INTO reorder_levels (product_id, reorder_level) VALUES (?, ?) ON DUPLICATE KEY UPDATE reorder_level = ?");
            $stmt_reorder->bind_param("iii", $product_id, $reorder_level, $reorder_level);
            $stmt_reorder->execute();

            $mysqli->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_product':
        $product_id = (int)$_POST['product_id'];
        // Note: You might want to handle foreign key constraints more gracefully
        $mysqli->begin_transaction();
        try {
            $stmt1 = $mysqli->prepare("DELETE FROM reorder_levels WHERE product_id = ?");
            $stmt1->bind_param("i", $product_id);
            $stmt1->execute();

            $stmt2 = $mysqli->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt2->bind_param("i", $product_id);
            $stmt2->execute();

            $mysqli->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>