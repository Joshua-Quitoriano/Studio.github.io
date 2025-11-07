<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
header('Content-Type: application/json');

try {
    // First check if table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'octoberian_courses'");
    if ($check_table === false) {
        throw new Exception("Failed to check table existence: " . $conn->error);
    }
    
    if ($check_table->num_rows === 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS octoberian_courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(10) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create octoberian_courses table: " . $conn->error);
        }
        error_log("Created octoberian_courses table");
    }

    // Check if there are any courses
    $count = $conn->query("SELECT COUNT(*) as total FROM octoberian_courses");
    if ($count === false) {
        throw new Exception("Failed to count courses: " . $conn->error);
    }
    
    $count_result = $count->fetch_assoc();
    error_log("Found " . $count_result['total'] . " courses in database");
    
    if ($count_result['total'] == 0) {
        error_log("No courses found, inserting default courses");
        // Insert default courses if none exist
        $default_courses = [
            ['BSIT', 'Bachelor of Science in Information Technology'],
            ['BSP', 'Bachelor of Science in Psychology'],
            ['BSTM', 'Bachelor of Science in Tourism Management'],
            ['BSHM', 'Bachelor of Science in Hospitality Management'],
            ['BSBA', 'Bachelor of Science in Business Administration'],
            ['BSOA', 'Bachelor of Science in Office Administration'],
            ['BSCrim', 'Bachelor of Science in Criminology'],
            ['BEEd', 'Bachelor of Elementary Education'],
            ['BSEd', 'Bachelor of Secondary Education'],
            ['BSCpE', 'Bachelor of Science in Computer Engineering'],
            ['BSEntrep', 'Bachelor of Science in Entrepreneurship'],
            ['BSAIS', 'Bachelor of Science in Accounting Information System'],
            ['BLIS', 'Bachelor of Library and Information Science']
        ];

        // First, truncate the table to ensure it's empty
        $conn->query("TRUNCATE TABLE octoberian_courses");
        error_log("Truncated octoberian_courses table");

        // Now insert the courses
        foreach ($default_courses as $course) {
            $sql = "INSERT INTO octoberian_courses (code, name) VALUES ('" . 
                   $conn->real_escape_string($course[0]) . "', '" . 
                   $conn->real_escape_string($course[1]) . "')";
            
            if (!$conn->query($sql)) {
                throw new Exception("Failed to insert course {$course[0]}: " . $conn->error);
            }
            error_log("Inserted course: {$course[0]}");
        }
        error_log("Finished inserting all courses");
    }

    // Now fetch the courses
    $result = $conn->query("SELECT id, code, name FROM octoberian_courses ORDER BY name");
    if ($result === false) {
        throw new Exception("Failed to fetch courses: " . $conn->error);
    }
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    error_log("Fetched " . count($courses) . " courses from database");
    
    if (empty($courses)) {
        throw new Exception("No courses found in database after insertion");
    }
    
    echo json_encode(['success' => true, 'data' => $courses], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Course fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch courses: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Don't close the connection here, it will be handled by database.php
