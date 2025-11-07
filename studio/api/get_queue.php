<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkStudioAccess();

try {
    $query = "
        SELECT 
            a.id, 
            CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) AS student_name,
            CONCAT(DATE_FORMAT(a.preferred_date, '%Y-%m-%d'), ' ', TIME_FORMAT(a.preferred_time, '%H:%i')) AS appointment_time 
        FROM appointments a
        JOIN students s ON a.student_id = s.id
        WHERE a.status = 'approved'
          AND a.actual_date IS NULL
          AND a.actual_time IS NULL
          AND a.processed_by IS NULL
        ORDER BY a.preferred_date ASC, a.preferred_time ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode($appointments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
