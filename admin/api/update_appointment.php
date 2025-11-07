<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkAdminAccess();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if (!$appointmentId || !$status) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get appointment details
    $stmt = $conn->prepare("
        SELECT a.*, s.first_name, s.last_name 
        FROM appointments a 
        JOIN students s ON a.student_id = s.id 
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();

    if (!$appointment) {
        throw new Exception('Appointment not found');
    }

    // Update appointment
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $status, $appointmentId);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>