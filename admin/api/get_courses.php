<?php
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');

// Ensure user is authenticated
session_start();
if (!isAuthenticated() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $student_type = $_GET['type'] ?? '';
    $courses = [];

    switch ($student_type) {
        case 'regular_college':
            $sql = "SELECT id, code, name FROM college_courses WHERE type = 'regular' ORDER BY name";
            break;
        case 'octoberian':
            $sql = "SELECT id, code, name FROM octoberian_courses ORDER BY name";
            break;
        case 'senior_high':
            $sql = "SELECT id, code, name FROM shs_strands ORDER BY name";
            break;
        default:
            echo json_encode([]);
            exit();
    }

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    echo json_encode($courses);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
