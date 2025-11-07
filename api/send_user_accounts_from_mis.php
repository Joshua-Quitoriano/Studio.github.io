<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verify API key
if (!isset($_SERVER['HTTP_X_API_KEY']) || $_SERVER['HTTP_X_API_KEY'] !== 'A1B2C3DAKO4E5A1B2C3DSI4E5A1B2C3DJOSHUA4E5F6GPOGI7H8I9J0') {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['username']) || 
        !isset($data['password']) || 
        !isset($data['email']) || 
        !isset($data['full_name']) ||
        !isset($data['role']) || 
        !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields (username, password, email, full_name, role, status)']);
        exit;
    }

    // Validate role enum
    if (!in_array($data['role'], ['admin', 'studio'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Role must be either "admin" or "studio"']);
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user account
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", 
            $data['username'],
            $hashedPassword,
            $data['email'],
            $data['full_name'],
            $data['role'],
            $data['status']
        );
        $stmt->execute();
        $userId = $conn->insert_id;
        
        // Commit the transaction
        $conn->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User account created successfully',
            'user_id' => $userId
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while creating the user account']);
    }
    exit;
}

// Handle invalid request method
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;