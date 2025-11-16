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
        $stmt = $mysqli->prepare("SELECT product_id, product_name, mrp, tax_rate, category_id FROM products WHERE product_id = ?");
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

        if ($product_id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, mrp = ?, tax_rate = ?, category_id = ? WHERE product_id = ?");
            $stmt->bind_param("sddii", $product_name, $mrp, $tax_rate, $category_id, $product_id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, mrp, tax_rate, category_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sddi", $product_name, $mrp, $tax_rate, $category_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_product':
        $product_id = (int)$_POST['product_id'];
        $stmt = $mysqli->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>