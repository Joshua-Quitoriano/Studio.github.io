<?php
session_start(); // Start the session at the very beginning

$pageTitle = "Admin Dashboard"; // Add the page title
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Ensure user is logged in and is an admin
checkAdminAccess();

// Check if full_name is set in session, otherwise set a default
$user_full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin';
$user_user_name = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';


// Database connection
$counts = [];

// Get total students count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM students");
$stmt->execute();
$result = $stmt->get_result();
$counts['students'] = $result->fetch_assoc()['count'];

// Get pending appointments count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$counts['pending_appointments'] = $result->fetch_assoc()['count'];

// Get today's appointments count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE DATE(preferred_date) = CURRENT_DATE
    AND status = 'approved'
");
$stmt->execute();
$result = $stmt->get_result();
$counts['today_appointments'] = $result->fetch_assoc()['count'];

// Get recent activities
$stmt = $conn->prepare("
    SELECT a.*, s.first_name, s.last_name, s.student_type
    FROM appointments a
    JOIN students s ON a.student_id = s.student_id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Fetch recent appointment activities
$sql = "
    SELECT a.status, a.created_at, s.first_name, s.last_name, s.student_type
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    ORDER BY a.created_at DESC
    LIMIT 5
";
$result = $conn->query($sql);
$recent_activities = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}
?>

<style>
    .card { 
        transition: transform 0.2s; 
    }
    .card:hover { 
        transform: translateY(-5px); 
    }
    .stat-card { 
        border-radius: 15px; 
        border: none;
        min-height: 140px;
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: inherit;
        filter: brightness(0.6);
        z-index: 1;
    }
    .stat-card .card-body {
        padding: 1.5rem;
        position: relative;
        z-index: 2;
        height: 100%;
    }
    .stat-card i { 
        font-size: 2.5rem; 
        opacity: 0.9;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    .stat-card h6 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }
    .stat-card h2 {
        font-size: 2.5rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    .activity-card {
        border-radius: 15px;
        border: none;
    }
    .activity-card .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        padding: 1.25rem 1.5rem;
    }
    .activity-card .card-header h5 {
        color: #2c3e50;
        font-weight: 600;
    }
    .activity-card .card-body {
        padding: 1.5rem;
    }
    .quick-action-btn {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    .quick-action-btn i {
        width: 2.5rem;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .quick-action-btn span {
        font-size: 1.1rem;
        font-weight: 500;
        position: relative;
        z-index: 2;
    }
    .quick-action-btn.appointments {
        background: linear-gradient(135deg, #233763 0%, #20c997 100%);
        color: white;
    }
    .quick-action-btn.appointments:hover {
        color: rgba(48, 47, 47);
    }
    .quick-action-btn.students {
        background: linear-gradient(135deg, #233763 0%, #20c997 100%);
        color: white;
    }
    .quick-action-btn.students:hover {
        color: rgba(0, 0, 0);
    }
    .quick-action-btn.reports {
        background: linear-gradient(135deg, #233763 0%, #20c997 100%);
        color: white;
    }
    .quick-action-btn.reports:hover {
        color: rgba(48, 47, 47);
    }
    .quick-action-btn.schedule {
        background: linear-gradient(135deg, #233763 0%, #20c997 100%);
        color: white;
    }
    .quick-action-btn.schedule:hover {
        color: rgba(48, 47, 47);
    }
    .activity-status {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .activity-status.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .activity-status.approved {
        background-color: #d4edda;
        color: #155724;
    }
    .activity-status.rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    .activity-status.cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    .activity-status.completed {
        background-color: #d4edda;
        color: #155724;
    }
</style>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo $user_user_name; ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm text-white h-100" style="background-image: url('../includes/card-primary.png')">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Students</h6>
                        <h2 class="mb-0"><?php echo $counts['students']; ?></h2>
                    </div>
                    <i class="fas fa-users text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm text-white h-100" style="background-image: url('../includes/card-warning.png')">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Pending Appointments</h6>
                        <h2 class="mb-0"><?php echo $counts['pending_appointments']; ?></h2>
                    </div>
                    <i class="fas fa-clock text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm text-white h-100" style="background-image: url('../includes/card-success.png')">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Today's Appointments</h6>
                        <h2 class="mb-0"><?php echo $counts['today_appointments']; ?></h2>
                    </div>
                    <i class="fas fa-calendar-check text-success"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-8 mb-4">
            <div class="card activity-card shadow-sm" style="background-color: #00565d">
                <div class="card-header" style="background-color: #142828">
                    <h5 class="mb-0 text-white">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <a href="manage_schedules.php" class="quick-action-btn schedule w-100 p-4 text-start">
                                <i class="fas fa-calendar-alt me-3"></i>
                                <span>Manage Schedules</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="manage_appointments.php" class="quick-action-btn appointments w-100 p-4 text-start">
                                <i class="fas fa-calendar-check me-3"></i>
                                <span>Manage Appointments</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="students.php" class="quick-action-btn students w-100 p-4 text-start">
                                <i class="fas fa-user-graduate me-3"></i>
                                <span>Manage Students</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="reports.php" class="quick-action-btn reports w-100 p-4 text-start">
                                <i class="fas fa-chart-bar me-3"></i>
                                <span>Manage Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-4 mb-4">
            <div class="card activity-card shadow-sm">
                <div class="card-header" style="background-color: #142828">
                    <h5 class="mb-0 text-white"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="list-group-item px-3 text-white"  style="background-color: #00565d">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0">
                                            <i class="fas fa-user-circle me-2 text-white"></i>
                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                        </h6>
                                        <small class="text-white">
                                            <i class="fas fa-clock me-1 text-white"></i>
                                            <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="mb-0">
                                            <span class="text-white">
                                                <?php echo ucwords(str_replace('_', ' ', $activity['student_type'])); ?>
                                            </span>
                                        </p>
                                        <span class="activity-status <?php echo strtolower($activity['status']); ?>     ">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item px-3 text-center text-muted">
                                No recent activities.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</div>