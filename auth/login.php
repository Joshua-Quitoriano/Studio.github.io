<?php
session_start();
require_once '../config/database.php';
require_once '../includes/tracking.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = trim($_POST['email'] ?? $_POST['username']); // Accept email or username
    $password = $_POST['password'];
    $user = null;

    // Check first in `students` table (only email, no username column)
    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS full_name, email, password, 'student' AS role 
            FROM students WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        // If not found, check `users` table (supports both username and email)
        $sql = "SELECT id, username, full_name, email, password, role, profile_picture FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
        }
    }

    // Verify credentials
    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_picture'] = $user['profile_picture'];

        // Track successful login for admins and studio staff
        if ($user['role'] === 'admin' || $user['role'] === 'studio') {
            trackUserLogin($conn, $user['id']);
        }
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header('Location: ../admin/dashboard.php');
                break;
            case 'studio':
                header('Location: ../studio/dashboard.php');
                break;
            case 'student':
                header('Location: ../student/dashboard.php');
                break;
            default:
                header('Location: ../dashboard.php');
        }
        exit;
    } else {
        // Login failed - redirect back with error
        // Track failed login attempt
        trackFailedLogin($conn, $input);
        
        header('Location: ../index.php?error=1');
        exit();
    }
} else {
    // Not a POST request - redirect to login page
    header('Location: ../index.php');
    exit;
}
?>
