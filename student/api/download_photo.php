<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkStudentAccess();

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Photo ID is required');
}

try {
    // Get photo details and verify ownership
    $query = "SELECT sp.*, a.student_id 
              FROM studio_photos sp
              JOIN appointments a ON sp.appointment_id = a.id
              WHERE sp.id = ? AND a.student_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $_GET['id'], $_SESSION['user_id']);
    $stmt->execute();
    $photo = $stmt->get_result()->fetch_assoc();
    
    if (!$photo) {
        http_response_code(404);
        die('Photo not found or access denied');
    }
    
    $file_path = '../../' . $photo['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('Photo file not found');
    }
    
    // Get file information
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    // Set headers for download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . basename($photo['file_name']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($file_path);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    die('Error downloading photo: ' . $e->getMessage());
}
