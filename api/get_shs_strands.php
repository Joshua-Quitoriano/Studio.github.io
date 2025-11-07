<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
header('Content-Type: application/json');

try {
    // First check if table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'shs_strands'");
    if ($check_table->num_rows === 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS shs_strands (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(10) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create shs_strands table: " . $conn->error);
        }
    }

    // Check if there are any strands
    $count = $conn->query("SELECT COUNT(*) as total FROM shs_strands");
    $count_result = $count->fetch_assoc();
    if ($count_result['total'] === 0) {
        // Insert default strands if none exist
        $default_strands = [
            ['GAS', 'General Academic Strand'],
            ['ABM', 'Accountancy Business and Management'],
            ['HUMMS', 'Humanities and Social Sciences'],
            ['STEM', 'Science Technology Engineering and Mathematics'],
            ['ICT', 'Information and Computer Technology'],
            ['HE', 'Home Economics'],
            ['PA', 'Performing Arts'],
            ['IA', 'Industrial Arts']
        ];

        $insert_stmt = $conn->prepare("INSERT INTO shs_strands (code, name) VALUES (?, ?)");
        foreach ($default_strands as $strand) {
            $insert_stmt->bind_param("ss", $strand[0], $strand[1]);
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to insert strand {$strand[0]}: " . $insert_stmt->error);
            }
        }
        $insert_stmt->close();
    }

    // Now fetch the strands
    $stmt = $conn->prepare("
        SELECT id, code, name 
        FROM shs_strands 
        ORDER BY name
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $strands = [];
    while ($row = $result->fetch_assoc()) {
        $strands[] = $row;
    }
    
    if (empty($strands)) {
        throw new Exception("No strands found in database");
    }
    
    echo json_encode(['success' => true, 'data' => $strands], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Strand fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch strands: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Don't close the connection here, it will be handled by database.php
if (isset($stmt)) {
    $stmt->close();
}
