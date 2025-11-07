<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'adminjoshdev123');
define('DB_NAME', 'socmed');

// Initialize connection variable
$conn = null;

try {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Select the database
    $conn->select_db(DB_NAME);

} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die("A database error occurred. Please contact the administrator.");
}
?>
