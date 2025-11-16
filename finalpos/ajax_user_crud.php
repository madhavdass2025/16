<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

check_access(['Admin']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_user':
        $user_id = (int)$_GET['user_id'];
        $stmt = $mysqli->prepare("SELECT user_id, name, email, role_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
        break;

    case 'save_user':
        $user_id = (int)$_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role_id = (int)$_POST['role_id'];
        $password = $_POST['password'];

        if ($user_id > 0) { // Update
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role_id = ?, password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("ssisi", $name, $email, $role_id, $password_hash, $user_id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role_id = ? WHERE user_id = ?");
                $stmt->bind_param("ssii", $name, $email, $role_id, $user_id);
            }
        } else { // Insert
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (name, email, role_id, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $name, $email, $role_id, $password_hash);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
        }
        break;

    case 'delete_user':
        $user_id = (int)$_POST['user_id'];
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
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