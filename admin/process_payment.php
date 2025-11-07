<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $status = $_POST['status'];
    
    if (!in_array($status, ['verified', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    // Update payment status
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $status, $payment_id);
    
    if ($stmt->execute()) {
        // Get student ID for notification
        $stmt = $conn->prepare("
            SELECT a.student_id 
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            // Send notification to student
            $message = $status === 'verified' ? 
                'Your payment has been verified' : 
                'Your payment has been rejected. Please upload a new receipt.';
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->bind_param("is", $result['student_id'], $message);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
