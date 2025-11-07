<?php
require_once '../../config/database.php';

$sql = "ALTER TABLE appointments 
        ADD COLUMN receipt_path VARCHAR(255) DEFAULT NULL,
        ADD COLUMN receipt_name VARCHAR(255) DEFAULT NULL,
        ADD COLUMN verification_status ENUM('pending', 'verified', 'non-verified') DEFAULT 'pending'";

if ($conn->query($sql) === TRUE) {
    echo "Table modified successfully";
} else {
    echo "Error modifying table: " . $conn->error;
}

$conn->close();
?>
