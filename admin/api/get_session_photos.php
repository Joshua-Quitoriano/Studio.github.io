<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');

// Only allow admins (or super admins)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check for a valid appointment ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid appointment ID']);
    exit();
}

$appointmentId = intval($_GET['id']);

try {
    // Query photos related to this appointment (if it is completed)
    $query = "
        SELECT sp.file_name 
        FROM studio_photos sp
        JOIN appointments a ON sp.appointment_id = a.id
        WHERE a.id = ? AND a.status = 'completed'
        ORDER BY sp.uploaded_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $photos = [];
    while ($row = $result->fetch_assoc()) {
        $photos[] = [
            'url' => "./uploads/studio_photos/" . $row['file_name']
        ];
    }

    echo json_encode(['photos' => $photos]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
