<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkStudentAccess();

include '../includes/header.php';

$appointmentId = $_GET['appointment_id']; // Get the appointment ID from the query parameter

// Fetch appointment details
$stmt = $conn->prepare("SELECT 
                            a.*, 
                            s.student_id AS student_number,
                            s.first_name, 
                            s.middle_name, 
                            s.last_name, 
                            s.student_type,
                            cc.name AS course_name,
                            ss.name AS strand_name
                        FROM appointments a 
                        JOIN students s ON a.student_id = s.id 
                        LEFT JOIN college_courses cc ON s.college_course_id = cc.id
                        LEFT JOIN shs_strands ss ON s.shs_strand_id = ss.id
                        WHERE a.id = ?");
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

$courseOrStrand = 'N/A';
if ($appointment['student_type'] === 'regular_college' || $appointment['student_type'] === 'octoberian') {
    $courseOrStrand = $appointment['course_name'];
} elseif ($appointment['student_type'] === 'senior_high') {
    $courseOrStrand = $appointment['strand_name'];
}

$programLabel = ($appointment['student_type'] === 'senior_high') ? 'Strand' : 'Course';

if (!$appointment) {
    die("Appointment not found.");
}

// Prepare data for invoice
$studentId = $appointment['student_number'];
$studentFullName = $appointment['first_name'] . ' ' . $appointment['middle_name'] . ' ' . $appointment['last_name'];

switch ($appointment['student_type']) {
    case 'regular_college':
        $studentType = 'Regular College';
        break;
    case 'senior_high':
        $studentType = 'Senior High';
        break;
    case 'octoberian':
        $studentType = 'Octoberian';
        break;
    default:
        $studentType = ucfirst($appointment['student_type']);
}

$appointmentDate = date('F j, Y', strtotime($appointment['preferred_date']));
$appointmentTime = date('g:i A', strtotime($appointment['preferred_time']));


// Add functionality to download the invoice as PDF (using a library like TCPDF or MPDF)
?>

<div class="container mt-5 w-75">
    <div class="card shadow rounded-4 border-0">
        <div class="card-header bg-dark text-white rounded-top-4 d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Graduation Picture Appointment</h3>
            <span class="badge bg-light text-primary fw-semibold">#<?php echo str_pad($appointmentId, 5, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="card-body p-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1 text-muted"><i class="fas fa-id-card me-2"></i><strong>Student Number:</strong></p>
                    <p class="fs-5"><?php echo $studentId; ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted"><i class="fas fa-user me-2"></i><strong>Full Name:</strong></p>
                    <p class="fs-5"><?php echo htmlspecialchars($studentFullName); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted"><i class="fas fa-user-tag me-2"></i><strong>Student Type:</strong></p>
                    <p class="fs-5 text-capitalize"><?php echo $studentType; ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted"><i class="fas fa-book me-2"></i><strong><?php echo $programLabel; ?>:</strong></p>
                    <p class="fs-5"><?php echo htmlspecialchars($courseOrStrand); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted"><i class="fas fa-calendar-alt me-2"></i><strong>Appointment Date:</strong></p>
                    <p class="fs-5" ><?php echo $appointmentDate . ' at ' . $appointmentTime; ?></p>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                This invoice confirms your appointment for graduation pictorial. Please bring a copy on your scheduled session.
            </div>
        </div>

        <div class="card-footer bg-light d-flex justify-content-end gap-2 rounded-bottom-4">
            <a href="#=<?php echo $appointmentId; ?>" class="btn btn-primary">
                <i class="fas fa-download me-1"></i> Download PDF
            </a>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .card, .card * {
            visibility: visible;
        }
        .card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
        }
        .card-footer {
            display: none;
        }
    }
</style>
