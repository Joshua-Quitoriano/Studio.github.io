<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

if (!isset($_GET['id'])) {
    exit('Student ID not provided');
}

$student_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get student details with academic info
$query = "
    SELECT s.*, 
           COALESCE(cc.name, ss.name) as program_name,
           sai.section,
           s.student_type
    FROM students s
    LEFT JOIN student_academic_info sai ON s.id = sai.student_id
    LEFT JOIN college_courses cc ON sai.college_course_id = cc.id
    LEFT JOIN shs_strands ss ON sai.shs_strand_id = ss.id
    WHERE s.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get appointment statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN a.status IN ('pending', 'approved') THEN 1 ELSE 0 END) as active_appointments,
        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
        SUM(CASE WHEN s.student_type = 'senior_high' THEN 1 ELSE 0 END) as has_shs_appointment,
        SUM(CASE WHEN s.student_type = 'regular_college' THEN 1 ELSE 0 END) as has_college_appointment,
        GROUP_CONCAT(DISTINCT a.status) as appointment_statuses
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    WHERE a.student_id = ?
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get current/latest appointment
$current_query = "
    SELECT status, preferred_date, notes
    FROM appointments 
    WHERE student_id = ? 
    AND status IN ('pending', 'approved')
    ORDER BY preferred_date DESC 
    LIMIT 1
";

$stmt = $conn->prepare($current_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$current_appointment = $stmt->get_result()->fetch_assoc();

if ($student): ?>
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .status-badge.inactive {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .info-item {
            margin-bottom: 0;
        }
        .info-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-weight: 500;
            color: #2c3e50;
        }
    </style>
    <div class="container-fluid p-0">
        <div class="student-info">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                        <small class="text-muted">#<?php echo htmlspecialchars($student['student_id']); ?></small>
                    </h4>
                    <p class="text-muted mb-0">Registered on <?php echo date('F j, Y h:i A', strtotime($student['created_at'])); ?></p>
                </div>
                <span class="status-badge <?php echo $student['status'] === 'active' ? '' : 'inactive'; ?>">
                    <?php echo ucfirst($student['status']); ?>
                </span>
            </div>

            <!-- Appointment Statistics -->
            <div class="appointment-stats bg-light p-4 rounded-3 mb-4">
                <h5 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Appointment Statistics</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0" style="background: url('../includes/card-primary.png') no-repeat center center; background-size: cover; min-height: 160px;">
                            <div class="card-body text-white d-flex flex-column">
                                <h6 class="text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;"><i class="fas fa-clock me-2"></i>Current Appointment</h6>
                                <?php if ($current_appointment): ?>
                                    <div class="mt-auto">
                                        <h3 class="mb-2" style="font-size: 1.5rem; font-weight: 600;"><?php echo ucfirst($current_appointment['status']); ?></h3>
                                        <small style="font-size: 0.75rem;">Date: <?php echo date('M j, Y', strtotime($current_appointment['preferred_date'])); ?></small>
                                    </div>
                                <?php else: ?>
                                    <h3 class="mt-auto mb-0" style="font-size: 1.5rem; font-weight: 600;">None</h3>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0" style="background: url('../includes/card-dark.png') no-repeat center center; background-size: cover; min-height: 160px;">
                            <div class="card-body text-white d-flex flex-column">
                                <h6 class="text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;"><i class="fas fa-history me-2"></i>Previous Appointments</h6>
                                <div class="mt-auto">
                                    <div style="font-size: 0.9rem;">Completed: <?php echo $stats['completed_appointments'] ?? 0; ?></div>
                                    <div style="font-size: 0.9rem;">Cancelled: <?php echo $stats['cancelled_appointments'] ?? 0; ?></div>
                                    <div class="mt-2">
                                        <small style="font-size: 0.75rem;">Total: <?php echo $stats['total_appointments'] ?? 0; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0" style="background: url('../includes/card-success.png') no-repeat center center; background-size: cover; min-height: 160px;">
                            <div class="card-body text-white d-flex flex-column">
                                <h6 class="text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;"><i class="fas fa-graduation-cap me-2"></i>Type</h6>
                                <div class="mt-auto">
                                    <h3 class="mb-0" style="font-size: 1.5rem; font-weight: 600;">
                                        <?php 
                                        echo htmlspecialchars(ucwords(str_replace('_', ' ', $student['student_type'] ?? 'Not Set')));
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Student ID</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['contact_number']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Birthday</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['birthday']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Civil Status</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['civil_status']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Student Type</div>
                                    <div class="info-value"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $student['student_type']))); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Program</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Section</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        Student not found.
    </div>
<?php endif; ?>