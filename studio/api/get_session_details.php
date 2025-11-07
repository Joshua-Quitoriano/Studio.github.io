<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');

checkStudioAccess();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing appointment ID']);
    exit();
}

try {
    // Updated query to use the correct table structure
    $query = "SELECT a.*, 
              CONCAT(s.first_name, ' ', s.last_name) as student_name,
              CONCAT(DATE_FORMAT(a.preferred_date, '%Y-%m-%d'), ' ', 
                    TIME_FORMAT(a.preferred_time, '%H:%i')) as appointment_time 
              FROM appointments a 
              JOIN students s ON a.student_id = s.id
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_GET['id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    if (!$appointment) {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
        exit();
    }
    
    echo json_encode(['success' => true, 'data' => $appointment]);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching the appointment details']);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
