<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

checkStudentAccess();

header('Content-Type: application/json');

try {
    // Get student's course from their profile
    $stmt = $conn->prepare("
    SELECT 
    COALESCE(cc.name, ss.name, 'N/A') AS program_name,
    s.college_course_id,
    s.shs_strand_id,
    s.student_type
    FROM students s
    LEFT JOIN college_courses cc ON s.college_course_id = cc.id
    LEFT JOIN shs_strands ss ON s.shs_strand_id = ss.id
    WHERE s.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $student_type = $student['student_type'];

    if (!$student) {
        throw new Exception("Student profile not found");
    }

    // Determine the course or strand to filter by
    $filter_column = $student['college_course_id'] ? 'course_id' : 'strand_id';
    $filter_value = $student['college_course_id'] ? $student['college_course_id'] : $student['shs_strand_id'];

    // Construct the query with dynamic column filter
    $query = "
    SELECT 
        s.*, 
        COALESCE(COUNT(a.id), 0) as booked_slots,
        s.max_appointments_per_slot as max_slots
    FROM appointment_schedules s
    LEFT JOIN appointments a 
        ON s.id = a.schedule_id
        AND a.status IN ('approved', 'pending')
    WHERE s.$filter_column = ?   -- Use the dynamic filter column
    AND s.student_type = ? 
    AND s.start_date >= CURDATE()
    GROUP BY s.id
    ORDER BY s.start_date ASC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Bind the parameters
    $stmt->bind_param("is", $filter_value, $student_type);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();
    $schedules = [];

    while ($row = $result->fetch_assoc()) {
        // Decode time_slots
        $time_slots = isset($row['time_slots']) ? json_decode($row['time_slots'], true) : [];

        // Initialize time range
        $time_range = ''; // Default empty string

        // If there are time slots, process them
        if (is_array($time_slots) && !empty($time_slots)) {
            $first_slot = reset($time_slots);
            $last_slot = end($time_slots);
            $time_range = date('g:i A', strtotime(explode('-', $first_slot)[0])) . ' -  ' . 
                          date('g:i A', strtotime(explode('-', $last_slot)[1]));
        }

        // Default time for schedule if not provided
        $start_time = '00:00:00';
        $end_time = '00:30:00'; // Fallback if slot is missing

        if (is_array($time_slots) && !empty($time_slots)) {
            [$start_time_raw, $end_time_raw] = explode('-', $time_slots[0]);
            $start_time = date('H:i:s', strtotime($start_time_raw));
            $end_time = date('H:i:s', strtotime($end_time_raw));
        }

        // Combine date and time for accurate event times
        $start_date = $row['start_date'];
        $end_date_exclusive = date('Y-m-d', strtotime($row['end_date'] . ' +1 day'));

        // Multi-day event check
        if ($row['start_date'] != $row['end_date']) {
            $end_datetime = date('Y-m-d\TH:i:s', strtotime($row['end_date'] . ' ' . $end_time));
        }

        $max_slots = isset($row['max_slots']) ? $row['max_slots'] : 0;
        $remaining_slots = $max_slots - $row['booked_slots'];

        // Push the event data into the schedules array
        $schedules[] = [
            'id' => $row['id'],
            'title' => $student['program_name'] . "\n" . 
                       $time_range . "\n" . 'Available: ' . 
                       $remaining_slots . ' of ' . 
                       $max_slots . ' slots',
            'start' => $start_date,
            'end' => $end_date_exclusive,
            'allDay' => true,
            'time_slots' => $time_slots,
            'max_slots' => $max_slots,
            'booked_slots' => $row['booked_slots'],
            'remaining_slots' => $remaining_slots,
            'backgroundColor' => $remaining_slots <= 0 ? '#dc3545' : '#28a745',
            'borderColor' => $remaining_slots <= 0 ? '#dc3545' : '#28a745',
            'textColor' => '#ffffff'
        ];
    }

    // Return the schedules as JSON
    echo json_encode($schedules);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
