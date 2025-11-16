<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'get_categories':
            $result = $mysqli->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            exit;
        case 'get_products':
            $category_id = (int)$_GET['category_id'];
            $stmt = $mysqli->prepare("SELECT product_id, product_name, mrp FROM products WHERE category_id = ? ORDER BY product_name ASC");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            exit;
    }
}

// --- Sale Processing Endpoint ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // This part should handle the GET requests for search, so we check for POST explicitly for sale processing.
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        exit;
    }
} else {
    $customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    $items = $_POST['items'] ?? [];
    $payments = $_POST['payments'] ?? [];

    if (!$customer_id || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer and items are required.']);
        exit;
    }

    $mysqli->begin_transaction();

    try {
        // ... (rest of the sale processing logic is the same)
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>