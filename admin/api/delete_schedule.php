<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_helper.php';

header('Content-Type: application/json');
checkAdminAccess();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $schedule_id = $data['id'] ?? null;

        if (!$schedule_id) {
            throw new Exception("Schedule ID is required");
        }

        // Check if there are any existing appointments
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appointments a
            JOIN appointment_schedules s ON 
                a.preferred_date BETWEEN s.start_date AND s.end_date
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            throw new Exception("Cannot delete schedule with existing appointments");
        }

        // Delete the schedule
        $stmt = $conn->prepare("DELETE FROM appointment_schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ]);
        } else {
            throw new Exception("Failed to delete schedule");
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
