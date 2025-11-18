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
        $stmt = $mysqli->prepare("SELECT Mid, name, UnitPrice, hsn, Itax, cess FROM products WHERE Mid = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_product':
        $mid = (int)($_POST['Mid'] ?? 0);
        $name = $_POST['name'];
        $unitPrice = (float)$_POST['UnitPrice'];
        $hsn = $_POST['hsn'];
        $itax = (float)$_POST['Itax'];
        $cess = (float)$_POST['cess'];
        $submittedby = $_SESSION['user_id']; // Assuming you store user name/id in session

        // Server-side calculation for tax breakdown
        $tax_rate_decimal = $itax / 100;
        $taxAmount = $unitPrice - ($unitPrice / (1 + $tax_rate_decimal));
        $taxExcluded_price = $unitPrice - $taxAmount;

        if ($mid > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE products SET name=?, UnitPrice=?, taxExcluded_price=?, taxAmount=?, hsn=?, Itax=?, cess=? WHERE Mid=?");
            $stmt->bind_param("sddsssdi", $name, $unitPrice, $taxExcluded_price, $taxAmount, $hsn, $itax, $cess, $mid);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO products (name, UnitPrice, taxExcluded_price, taxAmount, hsn, Itax, cess, submittedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sddsssss", $name, $unitPrice, $taxExcluded_price, $taxAmount, $hsn, $itax, $cess, $submittedby);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_product':
        $product_id = (int)$_POST['Mid'];
        $stmt = $mysqli->prepare("DELETE FROM products WHERE Mid = ?");
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