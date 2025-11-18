<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action == 'get_product_details') {
        $product_id = (int)$_GET['product_id'];
        $stmt = $mysqli->prepare("SELECT UnitPrice, taxAmount FROM products WHERE Mid = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (This will need to be rewritten to use the new product structure)
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
?>