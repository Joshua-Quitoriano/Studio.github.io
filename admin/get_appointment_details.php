<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

if (!isset($_GET['id'])) {
    exit('Appointment ID not provided');
}

$appointment_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get appointment and student data (with academic info)
$query = "
    SELECT a.*, 
           s.first_name, s.last_name, s.email, s.contact_number, s.birthday, s.civil_status,
           s.student_id as stud_no, s.student_type,
           sai.section,
           COALESCE(cc.name, ss.name) as program_name
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN student_academic_info sai ON s.id = sai.student_id
    LEFT JOIN college_courses cc ON sai.college_course_id = cc.id
    LEFT JOIN shs_strands ss ON sai.shs_strand_id = ss.id
    WHERE a.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Appointment Details</h1>
    <p class="breadcrumb-item active">View Appointment</p>

    <?php if ($appointment): ?>
        <div class="row">
            <!-- Student Information Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white fw-bold">
                        Student Information
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></p>
                        <p><strong>Student ID:</strong> <?= htmlspecialchars($appointment['stud_no']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($appointment['email']); ?></p>
                        <p><strong>Contact Number:</strong> <?= htmlspecialchars($appointment['contact_number']); ?></p>
                        <p><strong>Birthday:</strong> <?= htmlspecialchars($appointment['birthday']); ?></p>
                        <p><strong>Civil Status:</strong> <?= htmlspecialchars($appointment['civil_status']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Academic Information Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white fw-bold">
                        Academic Information
                    </div>
                    <div class="card-body">
                        <p><strong>Student Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $appointment['student_type']))); ?></p>
                        <p><strong>Program:</strong> <?= htmlspecialchars($appointment['program_name'] ?? 'N/A'); ?></p>
                        <p><strong>Section:</strong> <?= htmlspecialchars($appointment['section'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Appointment Information Card -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-info text-white fw-bold">
                        Appointment Information
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <?= ucfirst($appointment['status']); ?></p>
                                <p><strong>Verification:</strong> <?= ucfirst($appointment['verification_status']); ?></p>
                                <p><strong>Preferred Date:</strong> <?= htmlspecialchars($appointment['preferred_date']); ?></p>
                                <p><strong>Preferred Time:</strong> <?= htmlspecialchars(date('h:i A', strtotime($appointment['preferred_time']))); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Processed By:</strong> <?= htmlspecialchars($appointment['processed_by'] ?? 'Not yet processed'); ?></p>
                                <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($appointment['notes'])); ?></p>

                                <?php if (!empty($appointment['receipt'])): ?>
                                    <p><strong>Receipt:</strong><br>
                                        <a href="#" class="text-decoration-underline text-primary" onclick="viewReceipt('<?= $appointment['receipt']; ?>')">
                                            View Uploaded Receipt
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger">Appointment not found.</div>
    <?php endif; ?>
</div>
