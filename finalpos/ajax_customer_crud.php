<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager', 'Cashier']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_customer':
        $customer_id = (int)$_GET['customer_id'];
        $stmt = $mysqli->prepare("SELECT customer_id, name, phone, address FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_customer':
        $customer_id = (int)$_POST['customer_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        if ($customer_id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE customers SET name = ?, phone = ?, address = ? WHERE customer_id = ?");
            $stmt->bind_param("sssi", $name, $phone, $address, $customer_id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $address);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_customer':
        $customer_id = (int)$_POST['customer_id'];
        $stmt = $mysqli->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
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