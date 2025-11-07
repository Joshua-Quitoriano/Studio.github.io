<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT 
            s.schedule_id,
            COUNT(a.appointment_id) AS booked_slots,
            s.max_appointments_per_slot,
            CASE 
                WHEN COUNT(a.appointment_id) = 0 THEN 'Empty'
                WHEN COUNT(a.appointment_id) >= s.max_appointments_per_slot THEN 'Full'
                WHEN COUNT(a.appointment_id) >= s.max_appointments_per_slot * 0.8 THEN 'Almost Full'
                ELSE 'Available'
            END AS status
        FROM 
            schedules s
        LEFT JOIN 
            appointments a ON s.schedule_id = a.schedule_id
        GROUP BY 
            s.schedule_id
    ");
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalBooked = array_sum(array_column($schedules, 'booked_slots'));
    $totalCapacity = array_sum(array_column($schedules, 'max_appointments_per_slot'));
    
    echo json_encode([
        'success' => true,
        'totalBooked' => $totalBooked,
        'totalCapacity' => $totalCapacity,
        'schedules' => $schedules
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>