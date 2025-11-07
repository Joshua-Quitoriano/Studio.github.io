<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkAdminAccess();

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing appointment ID']);
    exit();
}

$id = intval($_GET['id']);

try {
    $query = "SELECT 
                a.id AS id,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                a.preferred_date,
                a.preferred_time,
                a.actual_date,
                a.actual_time,
                u.full_name AS staff_name,
                ss.notes AS session_notes,
                ss.start_time,
                ss.end_time
              FROM appointments a
              JOIN students s ON a.student_id = s.id
              LEFT JOIN users u ON a.processed_by = u.id
              LEFT JOIN studio_sessions ss ON a.id = ss.appointment_id
              WHERE a.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $session = $result->fetch_assoc();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
        exit();
    }

    echo json_encode($session);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}