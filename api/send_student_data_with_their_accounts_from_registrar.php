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

// Validate required fields for both user and student
$required_fields = [
    'student_id',
    'first_name',
    'last_name',
    'middle_name',
    'email',
    'contact_number',
    'birthday',
    'civil_status',
    'password',
    'student_type',
    'course_strand_id',
    'section',
    'semester',
];

foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Check if student ID or email already exists
$stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
$stmt->bind_param("ss", $data['student_id'], $data['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Student ID or email already exists']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert student record
    $stmt = $conn->prepare("
        INSERT INTO students (
            student_id,
            first_name,
            last_name,
            middle_name,
            email,
            contact_number,
            birthday,
            civil_status,
            password,
            student_type,
            college_course_id,
            shs_strand_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Check if course/strand exists
    if ($data['student_type'] === 'senior_high') {
        $stmt_check = $conn->prepare("SELECT id FROM shs_strands WHERE id = ?");
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM college_courses WHERE id = ?");
    }
    $stmt_check->bind_param("i", $data['course_strand_id']);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows === 0) {
        throw new Exception('Invalid course/strand ID');
    }

    // Set course/strand ID based on student type
    $college_course_id = in_array($data['student_type'], ['regular_college', 'octoberian']) ? $data['course_strand_id'] : null;
    $shs_strand_id = $data['student_type'] === 'senior_high' ? $data['course_strand_id'] : null;

    $stmt->bind_param(
        "ssssssssssii",
        $data['student_id'],
        $data['first_name'],
        $data['last_name'],
        $data['middle_name'],
        $data['email'],
        $data['contact_number'],
        $data['birthday'],
        $data['civil_status'],
        $hashed_password,
        $data['student_type'],
        $college_course_id,
        $shs_strand_id
    );
    
    $stmt->execute();
    $student_id = $conn->insert_id;

    // Insert academic info
    $stmt = $conn->prepare("
        INSERT INTO student_academic_info (
            student_id,
            section,
            semester,
            school_year,
            college_course_id,
            shs_strand_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $school_year = date('Y') . '-' . (date('Y') + 1);

    $stmt->bind_param(
        "issssi",
        $student_id,
        $data['section'],
        $data['semester'],
        $school_year,
        $college_course_id,
        $shs_strand_id
    );
    
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Student account created successfully',
        'student_id' => $student_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

