<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['appointment_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$appointment_id = intval($data['appointment_id']);
$status = $data['status'];
$notes = $data['notes'] ?? '';

// Validate status
$valid_statuses = ['pending', 'approved', 'completed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn->begin_transaction();

    // Check if appointment is cancelled
    $stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        throw new Exception('Appointment not found');
    }

    if ($result['status'] === 'cancelled') {
        throw new Exception('Cannot update cancelled appointments');
    }

    // Update appointment status
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = ?, 
            notes = ?,
            processed_by = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $status, $notes, $_SESSION['user_id'], $appointment_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update appointment');
    }

    // Get student ID for notification
    $stmt = $conn->prepare("
        SELECT a.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name 
        FROM appointments a
        JOIN students s ON a.student_id = s.students_id
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    // Create notification for student
    $message = "Your appointment has been " . strtolower($status);
    if (!empty($notes)) {
        $message .= ". Note: " . $notes;
    }

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, message) 
        VALUES (?, 'appointment', ?)
    ");
    $stmt->bind_param("is", $student['student_id'], $message);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
