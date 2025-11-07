<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkAdminAccess();

try {
    $query = "
SELECT 
    a.id AS id,
    CONCAT(st.first_name, ' ', st.last_name) AS student_name,
    CONCAT(a.preferred_date, ' ', a.preferred_time) AS appointment_date,
    CONCAT(a.actual_date, ' ', a.actual_time) AS completed_date,
    IFNULL(u.full_name, '-') AS studio_staff,
    (SELECT COUNT(*) FROM studio_photos WHERE appointment_id = a.id) AS photo_count
FROM appointments a
JOIN studio_sessions s ON a.id = s.appointment_id
JOIN students st ON a.student_id = st.id
LEFT JOIN users u ON a.processed_by = u.id
WHERE a.status = 'completed'
";





    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("Query: " . $query);

    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $sessions = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($sessions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
