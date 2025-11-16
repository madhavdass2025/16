<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin', 'Manager']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_supplier':
        $supplier_id = (int)$_GET['supplier_id'];
        $stmt = $mysqli->prepare("SELECT supplier_id, name, phone, gst_number FROM suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_supplier':
        $supplier_id = (int)$_POST['supplier_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $gst_number = $_POST['gst_number'];

        if ($supplier_id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE suppliers SET name = ?, phone = ?, gst_number = ? WHERE supplier_id = ?");
            $stmt->bind_param("sssi", $name, $phone, $gst_number, $supplier_id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO suppliers (name, phone, gst_number) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $gst_number);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_supplier':
        $supplier_id = (int)$_POST['supplier_id'];
        $stmt = $mysqli->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
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