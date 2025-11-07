<?php
require_once '../../config/database.php'; // ← fixed path

try {
    $stmt = $conn->query("
        SELECT s.id, s.student_type, s.start_date, s.end_date, s.time_slots, s.max_appointments_per_slot,
               CASE 
                   WHEN s.student_type IN ('regular_college', 'octoberian') THEN 
                       (SELECT name FROM college_courses WHERE id = s.course_id)
                   WHEN s.student_type = 'senior_high' THEN 
                       (SELECT name FROM shs_strands WHERE id = s.strand_id)
                   ELSE NULL
               END as program_name
        FROM appointment_schedules s
        WHERE s.end_date >= CURRENT_DATE
        ORDER BY s.start_date ASC
    ");

    while ($row = $stmt->fetch_assoc()) {
        $studentType = ucwords(str_replace('_', ' ', $row['student_type']));
        $programName = htmlspecialchars($row['program_name'] ?? 'N/A');
        $dateRange = date('M d, Y', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date']));
        $timeSlots = json_decode($row['time_slots'], true);
        
        // Format time slots
        $formattedSlots = '';
        if (is_array($timeSlots)) {
            foreach ($timeSlots as $slot) {
                $formattedSlots .= "<span class='badge bg-info me-1'>{$slot}</span>";
            }
        } else {
            $formattedSlots = "<span class='text-muted'>No slots</span>";
        }

        $max = "<span class='badge bg-primary'>{$row['max_appointments_per_slot']}</span>";

        echo "
        <tr>
            <td>{$studentType}</td>
            <td>{$programName}</td>
            <td>{$dateRange}</td>
            <td>{$formattedSlots}</td>
            <td class='text-center'>{$max}</td>
            <td><button class='btn btn-sm btn-danger' onclick='deleteSchedule({$row['id']})'>Delete</button></td>
        </tr>";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "<tr><td colspan='6'>Error loading schedules.</td></tr>";
}
?>
