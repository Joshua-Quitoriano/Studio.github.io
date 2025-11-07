<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkStudioAccess();

try {
    // Get active sessions (processing status)
    $active_query = "SELECT COUNT(*) as active_count FROM appointments WHERE status = 'processing'";
    $active_result = $conn->query($active_query);
    $active_count = $active_result->fetch_assoc()['active_count'];

    // Get pending sessions (approved but not yet processing)
    $pending_query = "SELECT COUNT(*) as pending_count FROM appointments WHERE status = 'approved'";
    $pending_result = $conn->query($pending_query);
    $pending_count = $pending_result->fetch_assoc()['pending_count'];

    // Get completed sessions
    $completed_query = "SELECT COUNT(*) as completed_count FROM appointments WHERE status = 'completed'";
    $completed_result = $conn->query($completed_query);
    $completed_count = $completed_result->fetch_assoc()['completed_count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'active_sessions' => $active_count,
            'pending_sessions' => $pending_count,
            'completed_sessions' => $completed_count
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
