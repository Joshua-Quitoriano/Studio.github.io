<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

$query = "
    SELECT g.*, u.full_name 
    FROM gallery g
    JOIN users u ON g.student_id = u.id
";

if ($student_id) {
    $query .= " WHERE g.student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'photos' => $photos
]);
?>
