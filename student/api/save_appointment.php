<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkStudentAccess();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get student information
    $stmt = $conn->prepare("SELECT id, student_id, first_name, last_name FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student) {
        throw new Exception('Student not found');
    }

    // Get and validate input
    $scheduleId = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $selectedDate = isset($_POST['selected_date']) ? $_POST['selected_date'] : '';
    $selectedTime = isset($_POST['selected_time']) ? $_POST['selected_time'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    if (!$scheduleId || !$selectedDate || !$selectedTime) {
        throw new Exception('Missing required fields');
    }

    // Validate if the time slot is available
    $stmt = $conn->prepare("
        SELECT * FROM appointment_schedules 
        WHERE id = ? AND DATE(?) BETWEEN start_date AND end_date
    ");
    $stmt->bind_param("is", $scheduleId, $selectedDate);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();

    if (!$schedule) {
        throw new Exception('Invalid schedule selected');
    }

    // Check if student already has a pending or approved appointment for this schedule
    $stmt = $conn->prepare("
        SELECT * FROM appointments 
        WHERE student_id = ? AND schedule_id = ? 
        AND status IN ('pending', 'approved')
    ");
    $stmt->bind_param("ii", $student['id'], $scheduleId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('You already have a pending or approved appointment for this schedule');
    }

    // Handle receipt upload
    $receiptPath = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
        }

        $receiptPath = 'uploads/receipts/' . uniqid('receipt_') . '.' . $fileExtension;
        $fullPath = '../../' . $receiptPath;
        
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $fullPath)) {
            throw new Exception('Failed to upload receipt');
        }
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments (
            student_id, schedule_id, preferred_date, preferred_time,
            notes, receipt_path, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->bind_param("iissss", 
        $student['id'], 
        $scheduleId, 
        $selectedDate, 
        $selectedTime,
        $notes,
        $receiptPath
    );
    $stmt->execute();
    $appointmentId = $conn->insert_id;

    // Create notification for admin
    $notificationMessage = "New appointment request from {$student['first_name']} {$student['last_name']} for {$selectedDate} at {$selectedTime}";
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, message, created_at)
        SELECT id, 'appointment_request', ?, NOW()
        FROM users WHERE role = 'admin'
    ");
    $stmt->bind_param("s", $notificationMessage);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment scheduled successfully',
        'appointment_id' => $appointmentId
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
