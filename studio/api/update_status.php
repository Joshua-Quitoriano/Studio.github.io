<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkStudioAccess();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['appointment_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    // Check if appointment exists
    $check_query = "SELECT id FROM appointments WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $data['appointment_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        throw new Exception('Appointment not found');
    }

    // Update appointment status
    $query = "UPDATE appointments 
              SET status = ?,
                  processed_by = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", 
        $data['status'],
        $_SESSION['user_id'],
        $data['appointment_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update appointment status');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating status: ' . $e->getMessage()
    ]);
}
