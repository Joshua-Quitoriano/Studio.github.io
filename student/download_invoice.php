<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

// ✅ Load Dompdf
require_once '../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

checkStudentAccess();

$appointmentId = $_GET['appointment_id'] ?? null;
if (!$appointmentId) {
    die("Missing appointment ID.");
}

// Fetch appointment details
$stmt = $conn->prepare("SELECT a.*, s.first_name, s.last_name, s.student_type, s.college_course_id, s.shs_strand_id 
                        FROM appointments a 
                        JOIN students s ON a.student_id = s.id 
                        WHERE a.id = ?");
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    die("Appointment not found.");
}

// Prepare details
$studentId = $appointment['student_id'];
$fullName = $appointment['first_name'] . ' ' . $appointment['last_name'];
$studentType = $appointment['student_type'];

if ($studentType === 'regular_college') {
    $courseStrand = 'Course ID: ' . $appointment['college_course_id'];
} elseif ($studentType === 'senior_high') {
    $courseStrand = 'Strand ID: ' . $appointment['shs_strand_id'];
} else {
    $courseStrand = 'N/A';
}

$preparedDate = date('F j, Y');
$preparedTime = date('g:i A');

// HTML content
$html = "
    <h2 style='text-align: center;'>Student Appointment Invoice</h2>
    <p><strong>Student ID:</strong> {$studentId}</p>
    <p><strong>Full Name:</strong> {$fullName}</p>
    <p><strong>Student Type:</strong> {$studentType}</p>
    <p><strong>Course/Strand:</strong> {$courseStrand}</p>
    <p><strong>Prepared Date:</strong> {$preparedDate}</p>
    <p><strong>Prepared Time:</strong> {$preparedTime}</p>
";

// ✅ Initialize and use Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options); // ✅ Proper instantiation here
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("invoice_{$studentId}.pdf", ["Attachment" => true]);
exit;
