<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkAdminAccess();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate inputs
        $schedule_id = $_POST['schedule_id'] ?? null;
        $student_type = $_POST['student_type'] ?? null;
        $selected_dates = isset($_POST['selectedDates']) ? json_decode($_POST['selectedDates'], true) : [];
        $time_slots = isset($_POST['time_slots']) ? $_POST['time_slots'] : [];

        if (!is_array($time_slots)) {
            $time_slots = [$time_slots]; // Normalize to array
        }
        if (!is_array($selected_dates)) {
            throw new Exception("Invalid format for selected dates");
        }
        if (count($selected_dates) === 0) {
            throw new Exception("Please select at least one date");
        }
        if (empty($student_type)) {
            throw new Exception("Student type is required");
        }

        // Validate and format time slots
        $formatted_slots = [];
        foreach ($time_slots as $slot) {
            if (is_string($slot) && preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $slot)) {
                list($start, $end) = explode('-', $slot);
                if (strtotime($start) !== false && strtotime($end) !== false && strtotime($start) < strtotime($end)) {
                    $formatted_slots[] = $slot;
                }
            }
        }

        if (empty($formatted_slots)) {
            throw new Exception("At least one valid time slot is required (format: HH:MM-HH:MM)");
        }

        $max_appointments = intval($_POST['max_appointments_per_slot'] ?? 0);
        if ($max_appointments < 1) {
            throw new Exception("Maximum appointments per slot must be at least 1");
        }

        // Get course/strand ID
        $course_id = null;
        $strand_id = null;

        $selected_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

        if ($student_type === 'regular_college' || $student_type === 'octoberian') {
            if ($selected_id <= 0) {
                throw new Exception("A valid college course must be selected.");
            }

            $course_check = $conn->prepare("SELECT id FROM college_courses WHERE id = ?");
            $course_check->bind_param("i", $selected_id);
            $course_check->execute();
            $result = $course_check->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Selected course does not exist.");
            }

            $course_id = $selected_id;
            $strand_id = null;

        } elseif ($student_type === 'senior_high') {
            if ($selected_id <= 0) {
                throw new Exception("A valid SHS strand must be selected.");
            }

            $strand_check = $conn->prepare("SELECT id FROM shs_strands WHERE id = ?");
            $strand_check->bind_param("i", $selected_id);
            $strand_check->execute();
            $result = $strand_check->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Selected strand does not exist.");
            }

            $strand_id = $selected_id;
            $course_id = null;

        } else {
            throw new Exception("Unknown student type selected.");
        }

        // Ensure all dates are valid
        $valid_dates = [];
        foreach ($selected_dates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if ($d && $d->format('Y-m-d') === $date) {
                $valid_dates[] = $date;
            }
        }
        if (empty($valid_dates)) {
            throw new Exception("Invalid dates provided.");
        }

        // Check for overlapping schedules for each selected date
        foreach ($valid_dates as $date) {
            $query = "
                SELECT id FROM appointment_schedules
                WHERE student_type = ?
                AND start_date = ?
                AND (course_id <=> ?)
                AND (strand_id <=> ?)
            ";

            if ($schedule_id) {
                $query .= " AND id != ?";
                $check_stmt = $conn->prepare($query);
                $check_stmt->bind_param("ssiii", $student_type, $date, $course_id, $strand_id, $schedule_id);
            } else {
                $check_stmt = $conn->prepare($query);
                $check_stmt->bind_param("ssii", $student_type, $date, $course_id, $strand_id);
            }

            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows > 0) {
                throw new Exception("Conflict: A schedule already exists on $date for this group.");
            }
        }

        $time_slots_json = json_encode($formatted_slots);
        $created_by = $_SESSION['user_id'];
        $final_course_id = $course_id ?: null;
        $final_strand_id = $strand_id ?: null;

        if ($schedule_id) {
            // Updating one existing schedule — only one date should be given
            if (count($valid_dates) !== 1) {
                throw new Exception("Only one date is allowed when editing a schedule.");
            }
            $single_date = $valid_dates[0];

            $stmt = $conn->prepare("
                UPDATE appointment_schedules 
                SET student_type = ?,
                    start_date = ?,
                    end_date = ?,
                    time_slots = ?,
                    max_appointments_per_slot = ?,
                    course_id = ?,
                    strand_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("ssssiiii", 
                $student_type, $single_date, $single_date,
                $time_slots_json, $max_appointments, 
                $final_course_id, $final_strand_id, $schedule_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update schedule.");
            }

        } else {
            // Inserting multiple new schedules — one per selected date
            $stmt = $conn->prepare("
                INSERT INTO appointment_schedules (
                    student_type, start_date, end_date,
                    time_slots, max_appointments_per_slot, 
                    course_id, strand_id, created_by,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");

            foreach ($valid_dates as $date) {
                $stmt->bind_param("ssssiiis", 
                    $student_type, $date, $date,
                    $time_slots_json, $max_appointments,
                    $final_course_id, $final_strand_id, $created_by
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to save schedule for $date.");
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => $schedule_id ? 'Schedule updated successfully' : 'Schedule(s) created successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>