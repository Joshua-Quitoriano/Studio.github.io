<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $photo_id = $_POST['photo_id'];
                
                $stmt = $conn->prepare("UPDATE gallery SET is_approved = 1 WHERE id = ?");
                $stmt->bind_param("i", $photo_id);
                
                if ($stmt->execute()) {
                    // Send notification to student
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, message)
                        SELECT student_id, 'New photo has been approved in your gallery'
                        FROM gallery
                        WHERE id = ?
                    ");
                    $stmt->bind_param("i", $photo_id);
                    $stmt->execute();
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
                break;
                
            case 'delete':
                $photo_id = $_POST['photo_id'];
                
                // Get image path before deleting
                $stmt = $conn->prepare("SELECT image_path FROM gallery WHERE id = ?");
                $stmt->bind_param("i", $photo_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result) {
                    // Delete from database
                    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
                    $stmt->bind_param("i", $photo_id);
                    
                    if ($stmt->execute()) {
                        // Delete file from server
                        if (file_exists('../' . $result['image_path'])) {
                            unlink('../' . $result['image_path']);
                        }
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Database error']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Photo not found']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        // Handle photo upload
        if (!isset($_FILES['photos']) || !isset($_POST['student_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        $student_id = $_POST['student_id'];
        $upload_dir = '../uploads/gallery/';
        $uploaded_files = [];
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['photos']['name'][$key];
            $file_size = $_FILES['photos']['size'][$key];
            $file_tmp = $_FILES['photos']['tmp_name'][$key];
            $file_type = $_FILES['photos']['type'][$key];
            
            // Generate unique filename
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_name = uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $unique_name;
            
            // Validate file type
            $allowed = array('jpg', 'jpeg', 'png');
            if (!in_array($file_ext, $allowed)) {
                continue;
            }
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                $relative_path = 'uploads/gallery/' . $unique_name;
                
                $stmt = $conn->prepare("
                    INSERT INTO gallery (student_id, image_path, is_approved) 
                    VALUES (?, ?, 0)
                ");
                $stmt->bind_param("is", $student_id, $relative_path);
                
                if ($stmt->execute()) {
                    $uploaded_files[] = $relative_path;
                }
            }
        }
        
        if (count($uploaded_files) > 0) {
            // Send notification to student
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, 'New photos have been uploaded to your gallery')
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'files' => $uploaded_files]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No files were uploaded']);
        }
    }
}
?>
