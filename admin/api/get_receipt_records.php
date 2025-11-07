<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkAdminAccess();
header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.student_type,
            s.course,
            a.receipt_path,
            a.created_at as upload_date
        FROM appointments a
        JOIN students s ON a.student_id = s.user_id
        WHERE a.receipt_path IS NOT NULL
        ORDER BY a.created_at DESC
    ";
    
    $result = $conn->query($query);
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['upload_date'] = date('Y-m-d H:i:s', strtotime($row['upload_date']));
        $records[] = $row;
    }
    
    echo json_encode([
        'data' => $records
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
