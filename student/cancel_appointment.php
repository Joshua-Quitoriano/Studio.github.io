<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['appointment_id']) || !is_numeric($data['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

$appointment_id = intval($data['appointment_id']);
$student_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify the appointment belongs to this student and is pending
    $stmt = $conn->prepare("
        SELECT status 
        FROM appointments 
        WHERE id = ? AND student_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $appointment_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Appointment not found or cannot be cancelled');
    }

    // Update the appointment status to cancelled
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = 'cancelled' 
        WHERE id = ? AND student_id = ?
    ");
    $stmt->bind_param("ii", $appointment_id, $student_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to cancel appointment');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
