<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkAdminAccess();
header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            a.id,
            a.preferred_date as appointment_date,
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.course,
            a.verification_status,
            a.receipt_path
        FROM appointments a
        JOIN students s ON a.student_id = s.user_id
        ORDER BY a.preferred_date DESC
    ";
    
    $result = $conn->query($query);
    $appointments = [];
    
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    echo json_encode([
        'data' => $appointments
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
