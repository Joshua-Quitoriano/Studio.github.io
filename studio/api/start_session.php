<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkStudioAccess();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['appointment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if appointment exists and is in approved status
    $check_query = "SELECT status FROM appointments WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $data['appointment_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Appointment not found');
    }

    $appointment = $result->fetch_assoc();
    if ($appointment['status'] !== 'approved') {
        throw new Exception('Appointment is not in approved status');
    }

    // Update appointment status to processing
    $update_query = "UPDATE appointments 
                    SET status = 'processing',
                        actual_date = CURRENT_DATE(),
                        actual_time = CURRENT_TIME()
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $data['appointment_id']);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update appointment status');
    }

    // Create session entry in studio_sessions table
    $session_query = "INSERT INTO studio_sessions (appointment_id, started_by, start_time)
                     VALUES (?, ?, NOW())";
    
    $session_stmt = $conn->prepare($session_query);
    $session_stmt->bind_param("ii", $data['appointment_id'], $_SESSION['user_id']);
    
    if (!$session_stmt->execute()) {
        throw new Exception('Failed to create studio session');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Session started successfully',
        'appointment_id' => $data['appointment_id']
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error starting session: ' . $e->getMessage()
    ]);
}
