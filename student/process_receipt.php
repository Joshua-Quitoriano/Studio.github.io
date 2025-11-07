<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $amount = $_POST['amount'];
    
    // Verify appointment belongs to student
    $stmt = $conn->prepare("
        SELECT id FROM appointments 
        WHERE id = ? AND student_id = ? AND status = 'approved'
    ");
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        header("Location: appointments.php?error=Invalid appointment");
        exit();
    }
    
    // Check if payment already exists
    $stmt = $conn->prepare("
        SELECT id FROM payments 
        WHERE appointment_id = ? AND status != 'rejected'
    ");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: upload_receipt.php?error=Payment already exists");
        exit();
    }
    
    // Handle file upload
    $file = $_FILES['receipt'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed = array('jpg', 'jpeg', 'png', 'pdf');
    if (!in_array($file_ext, $allowed)) {
        header("Location: upload_receipt.php?error=Invalid file type");
        exit();
    }
    
    // Generate unique filename
    $unique_name = uniqid() . '.' . $file_ext;
    $upload_dir = '../uploads/receipts/';
    $file_path = $upload_dir . $unique_name;
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $relative_path = 'uploads/receipts/' . $unique_name;
        
        // Insert payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (appointment_id, amount, receipt_path, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->bind_param("ids", $appointment_id, $amount, $relative_path);
        
        if ($stmt->execute()) {
            // Notify admin
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                SELECT id, 'New payment receipt uploaded for verification'
                FROM users WHERE role = 'admin'
            ");
            $stmt->execute();
            
            header("Location: upload_receipt.php?success=Receipt uploaded successfully");
        } else {
            unlink($file_path);
            header("Location: upload_receipt.php?error=Database error");
        }
    } else {
        header("Location: upload_receipt.php?error=Failed to upload file");
    }
    exit();
}
?>
