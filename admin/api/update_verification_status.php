<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkAdminAccess();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['verification_status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $id = $data['id'];
    $verification_status = $data['verification_status'];
    
    if (!in_array($verification_status, ['verified', 'non-verified'])) {
        throw new Exception('Invalid status value');
    }
    
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET verification_status = ?
        WHERE id = ?
    ");
    $stmt->bind_param('si', $verification_status, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update status');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
