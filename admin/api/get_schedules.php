<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');

// Ensure user is authenticated
checkAdminAccess();

try {
    // Get all schedules that haven't ended yet
    $stmt = $conn->prepare("
        SELECT a.*, 
        CASE 
            WHEN a.student_type IN ('regular_college', 'octoberian') THEN 
                (SELECT name FROM college_courses WHERE id = a.course_id)
            WHEN a.student_type = 'senior_high' THEN 
                (SELECT name FROM shs_strands WHERE id = a.strand_id)
            ELSE NULL
        END as program_name
        FROM appointment_schedules a 
        WHERE end_date >= CURRENT_DATE 
        ORDER BY start_date ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $schedules = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($schedules);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
