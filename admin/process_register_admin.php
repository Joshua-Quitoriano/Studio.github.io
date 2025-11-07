<?php
require_once '../config/database.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'admin'; // Default role for admin registration
    $status = 'active'; // Set status to active by default

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        die("All fields are required.");
    }
    
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Username or Email already exists.");
    }

    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $sql = "INSERT INTO users (username, password, email, full_name, role, status, profile_picture, created_at) 
            VALUES (?, ?, ?, '', ?, ?, 'default', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $hashed_password, $email, $role, $status);
    
    if ($stmt->execute()) {
        echo "Admin registered successfully. <a href='login.php'>Login</a>";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
