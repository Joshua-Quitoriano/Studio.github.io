<?php
header("Content-Type: application/json");
session_start();

// Connect to database
require_once '../config/database.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
    exit;
}

// Handle file upload
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Receipt upload is required"
    ]);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($_FILES['receipt']['type'], $allowedTypes)) {
    echo json_encode([
        "success" => false,
        "message" => "Only JPG and PNG files are allowed"
    ]);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = '../uploads/receipts/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
$filename = uniqid('receipt_') . '.' . $extension;
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $targetPath)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to upload receipt"
    ]);
    exit;
}

// Get form data
$input_data = [
    'preferred_date' => $_POST['preferred_date'],
    'preferred_time' => $_POST['preferred_time'],
    'notes' => $_POST['notes'] ?? "No additional notes"
];

// Validate input
if (empty($input_data['preferred_date']) || empty($input_data['preferred_time'])) {
    echo json_encode([
        "success" => false,
        "message" => "Date and time are required"
    ]);
    exit;
}

// Validate date format
$date = date_create($input_data['preferred_date']);
if (!$date) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid date format"
    ]);
    exit;
}

// Ensure date is not in the past
$today = new DateTime();
$today->setTime(0, 0, 0);
if ($date < $today) {
    echo json_encode([
        "success" => false,
        "message" => "Cannot schedule appointments in the past"
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First check if the user exists in the students table
    $stmt = $conn->prepare("
        SELECT id, student_type, college_course_id, shs_strand_id 
        FROM students 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        throw new Exception("Student record not found. Please complete your profile first.");
    }

    $student_data = $student_result->fetch_assoc();
    $student_id = $student_data['id'];

    // Get student's academic info
    $stmt = $conn->prepare("
        SELECT id 
        FROM student_academic_info 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create a basic academic info record if none exists
        $stmt = $conn->prepare("
            INSERT INTO student_academic_info 
            (student_id, school_year, college_course_id, shs_strand_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $current_year = date('Y');
        $school_year = $current_year . '-' . ($current_year + 1);

        
        $stmt->bind_param("issii", 
            $student_id,
            $school_year,

            $student_data['college_course_id'],
            $student_data['shs_strand_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Could not create academic information. Please update your profile first.");
        }
        
        $academic_info_id = $conn->insert_id;
    } else {
        $academic_info = $result->fetch_assoc();
        $academic_info_id = $academic_info['id'];
    }

    // Get schedule ID from form input
$schedule_id = $_POST['schedule_id'] ?? null;

if (!$schedule_id) {
    throw new Exception("Schedule ID is required.");
}

    // Optional: Check slot availability before inserting
    $stmt = $conn->prepare("
        SELECT max_appointments_per_slot,
            (SELECT COUNT(*) FROM appointments WHERE schedule_id = ? AND status IN ('pending', 'approved')) AS booked_count
        FROM appointment_schedules
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $schedule_id, $schedule_id);
    $stmt->execute();
    $schedule_result = $stmt->get_result();

    if ($schedule_result->num_rows === 0) {
        throw new Exception("Selected schedule not found.");
    }

    $schedule = $schedule_result->fetch_assoc();

    if ($schedule['booked_count'] >= $schedule['max_appointments_per_slot']) {
        throw new Exception("No slots available for the selected schedule.");
    }

    // Insert appointment with receipt path
    $stmt = $conn->prepare("
        INSERT INTO appointments (
            student_id, academic_info_id, preferred_date, 
            preferred_time, notes, status, receipt, schedule_id
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->bind_param(
        "iissssi",
        $student_id,
        $academic_info_id,
        $input_data['preferred_date'],
        $input_data['preferred_time'],
        $input_data['notes'],
        $filename,
        $schedule_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creating appointment: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Appointment booked successfully"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Delete uploaded file if it exists
    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>