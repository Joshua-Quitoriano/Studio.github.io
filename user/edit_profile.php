<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkUserAccess();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$response = ['success' => false, 'message' => ''];

// Get user data
$user_id = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');

// Validate required fields
if (empty($username) || empty($email) || empty($full_name)) {
    $response['message'] = 'All fields are required';
    echo json_encode($response);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

// Handle profile picture upload
$new_filename = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['profile_picture']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $response['message'] = 'Invalid file type. Only JPG, PNG and GIF are allowed.';
        echo json_encode($response);
        exit;
    }
    
    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = '../uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }
    
    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path . $new_filename)) {
        $response['message'] = 'Failed to upload profile picture';
        echo json_encode($response);
        exit;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if username is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username is already taken');
    }

    // Check if email is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email is already taken');
    }

    // Prepare base query
    $sql = "UPDATE users SET username = ?, email = ?, full_name = ?";
    $params = [$username, $email, $full_name];
    $types = "sss";

    // Add profile picture if uploaded
    if ($new_filename !== null) {
        $sql .= ", profile_picture = ?";
        $params[] = $new_filename;
        $types .= "s";
    }

    // Add WHERE clause
    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    // Update session variables
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    if ($new_filename !== null) {
        $_SESSION['profile_picture'] = $new_filename;
    }
    
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Profile updated successfully';
    $response['data'] = [
        'username' => $username,
        'full_name' => $full_name,
        'email' => $email,
        'profile_picture' => $new_filename
    ];
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
