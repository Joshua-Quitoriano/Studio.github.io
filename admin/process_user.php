<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'];

switch ($action) {
    case 'add':
        // Validate required fields
        $required = ['full_name', 'email', 'username', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit();
            }
        }

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $data['username'], $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit();
        }

        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, username, password, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", 
            $data['full_name'], 
            $data['email'], 
            $data['username'], 
            $hashed_password, 
            $data['role']
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'edit':
        // Validate required fields
        if (empty($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }

        // Check if username or email already exists for other users
        $stmt = $conn->prepare("
            SELECT id FROM users 
            WHERE (username = ? OR email = ?) 
            AND id != ?
        ");
        $stmt->bind_param("ssi", $data['username'], $data['email'], $data['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit();
        }

        // Build update query
        $updates = [
            "full_name = ?",
            "email = ?",
            "username = ?",
            "role = ?"
        ];
        $params = [
            $data['full_name'],
            $data['email'],
            $data['username'],
            $data['role']
        ];
        $types = "ssss";

        // Add password update if provided
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }

        $params[] = $data['user_id'];
        $types .= "i";

        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'delete':
        if (empty($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $data['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }

        // Only allow admin deletion if current user is the original admin (ID 1)
        if ($result['role'] === 'admin' && $_SESSION['user_id'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Only the original admin can delete other admins']);
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Delete user's notifications if they exist
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $data['user_id']);
            $stmt->execute();

            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $data['user_id']);
            
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete user');
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_status':
        if (empty($data['user_id']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'User ID and status are required']);
            exit();
        }

        // Validate status
        if (!in_array($data['status'], ['active', 'inactive'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }

        // Check if target user exists
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $data['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }

        // Only allow admin status change if current user is the original admin
        if ($result['role'] === 'admin' && $_SESSION['user_id'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Only the original admin can change admin status']);
            exit();
        }

        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $data['status'], $data['user_id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
