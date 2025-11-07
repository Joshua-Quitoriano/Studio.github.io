<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST Data: " . print_r($_POST, true));

    // Personal Information
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $middle_name = isset($_POST['middle_name']) ? htmlspecialchars(trim($_POST['middle_name'])) : null;
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact_number = htmlspecialchars(trim($_POST['contact_number']));
    $birthday = $_POST['birthday'];
    $civil_status = htmlspecialchars(trim($_POST['civil_status']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Academic Information
    $student_type = trim($_POST['student_type'] ?? '');
    $student_id = htmlspecialchars(trim($_POST['student_id']));
    $course_strand = $_POST['course_strand'] ?? null;
    $section = htmlspecialchars(trim($_POST['section']));
    $semester = $_POST['semester'] ?? null;

    error_log("Student Type Value: '" . $student_type . "'");
    error_log("Student Type Type: " . gettype($student_type));

    // Validate student_type
    if (empty($student_type) || !in_array($student_type, ['regular_college', 'octoberian', 'senior_high'])) {
        header("Location: ../register.php?error=Invalid student type selected");
        exit();
    }

    // Validate semester for college students
    if (($student_type === 'regular_college' || $student_type === 'octoberian') && empty($semester)) {
        header("Location: ../register.php?error=Semester is required for college students");
        exit();
    }

    // Validate passwords
    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Passwords do not match");
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=Invalid email format");
        exit();
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            header("Location: ../register.php?error=Email already exists");
            exit();
        }

        // Check if student ID exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            header("Location: ../register.php?error=Student ID already exists");
            exit();
        }

        // Ensure course_strand is selected
        if (empty($course_strand)) {
            header("Location: ../register.php?error=Please select a course/strand");
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Set course/strand ID based on student type
        $college_course_id = null;
        $shs_strand_id = null;
        
        if ($student_type === 'senior_high') {
            $shs_strand_id = $course_strand;
        } else {
            $college_course_id = $course_strand;
        }

        // Insert student data
        $stmt = $conn->prepare("
            INSERT INTO students (
                student_id, first_name, middle_name, last_name, email, 
                contact_number, birthday, civil_status, 
                password, student_type, college_course_id, shs_strand_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssssssii",
            $student_id, $first_name, $middle_name, $last_name, $email,
            $contact_number, $birthday, $civil_status,
            $hashed_password, $student_type, $college_course_id, $shs_strand_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting student: " . $stmt->error);
        }

        $student_id_inserted = $conn->insert_id;

        // Insert academic info

        $current_year = date('Y');
        $next_year = $current_year + 1;
        $school_year = $current_year . '-' . $next_year;

        $academic_stmt = $conn->prepare("
            INSERT INTO student_academic_info (
                student_id, section,
                school_year, semester, college_course_id, shs_strand_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $academic_stmt->bind_param(
            "issssis",
            $student_id_inserted, $section,
            $school_year, $semester, $college_course_id, $shs_strand_id
        );

        if (!$academic_stmt->execute()) {
            throw new Exception("Error inserting academic info: " . $academic_stmt->error);
        }

        // Commit transaction
        $conn->commit();

        // Redirect to login page with success message
        header("Location: ../index.php?success=Registration successful! Please login with your credentials.");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        header("Location: ../register.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}
?>
