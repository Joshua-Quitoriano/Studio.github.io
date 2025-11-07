<?php
// Handle preflight request before any output
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
    header("Access-Control-Max-Age: 86400");
    http_response_code(204);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Regular CORS headers for actual request
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header('Content-Type: application/json');


// Verify API key
if (!isset($_SERVER['HTTP_X_API_KEY']) || $_SERVER['HTTP_X_API_KEY'] !== 'A1B2C3DAKO4E5A1B2C3DSI4E5A1B2C3DJOSHUA4E5F6GPOGI7H8I9J0') {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['student_id']) || !isset($data['receipts'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (student_id, receipts)']);
    exit;
}

try {
    // Check if student exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $data['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
        exit;
    }

    // Process base64 image
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['receipts']));
    if ($image_data === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image data']);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/receipts/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $filename = 'receipt_' . $data['student_id'] . '_' . time() . '.jpg';
    $filepath = $upload_dir . $filename;

    // Save the image
    if (!file_put_contents($filepath, $image_data)) {
        throw new Exception('Failed to save image');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Update student record
    $stmt = $conn->prepare("UPDATE students SET receipts = ?, verification_receipt = 'pending' WHERE student_id = ?");
    $stmt->bind_param("ss", $filename, $data['student_id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Receipt uploaded successfully',
        'filename' => $filename
    ]);

} catch (Exception $e) {
    $conn->rollback();  // Safe to call even if no transaction
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process receipt: ' . $e->getMessage()]);
}