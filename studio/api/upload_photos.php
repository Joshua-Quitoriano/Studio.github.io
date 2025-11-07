<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkStudioAccess();

if (!isset($_POST['appointment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Appointment ID is required']);
    exit();
}

$appointment_id = $_POST['appointment_id'];
$upload_dir = '../../uploads/studio_photos/' . $appointment_id;

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
$errors = [];

if (isset($_FILES['photos'])) {
    $files = $_FILES['photos'];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_error = $files['error'][$i];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file: " . $file_name;
            continue;
        }
        
        // Generate unique filename
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_file_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . '/' . $unique_file_name;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Save file info to database
            $query = "INSERT INTO studio_photos (appointment_id, file_name, file_path, uploaded_by, uploaded_at) 
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $current_time = date('Y-m-d H:i:s');
            $relative_path = 'uploads/studio_photos/' . $appointment_id . '/' . $unique_file_name;
            
            $stmt->bind_param("issis", 
                $appointment_id,
                $unique_file_name,
                $relative_path,
                $_SESSION['user_id'],
                $current_time
            );
            
            if ($stmt->execute()) {
                $uploaded_files[] = $unique_file_name;
            } else {
                $errors[] = "Error saving file info to database: " . $file_name;
            }
        } else {
            $errors[] = "Error moving uploaded file: " . $file_name;
        }
    }
}

echo json_encode([
    'success' => count($errors) === 0,
    'uploaded_files' => $uploaded_files,
    'errors' => $errors
]);
