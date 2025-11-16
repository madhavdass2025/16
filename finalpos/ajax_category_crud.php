<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_category':
        $category_id = (int)$_GET['category_id'];
        $stmt = $mysqli->prepare("SELECT category_id, category_name FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_category':
        $category_id = (int)$_POST['category_id'];
        $category_name = $_POST['category_name'];

        if ($category_id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
            $stmt->bind_param("si", $category_name, $category_id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $category_name);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_category':
        $category_id = (int)$_POST['category_id'];
        $stmt = $mysqli->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
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