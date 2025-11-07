<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

// Add required libraries for calendar
?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<?php

checkStudentAccess();

// Get student's upcoming appointment
$stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE student_id = ? 
    AND status IN ('pending', 'approved') 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

// Get student's gallery count from completed sessions
$stmt = $conn->prepare("
    SELECT COUNT(*) as photo_count 
    FROM studio_photos sp
    JOIN appointments a ON sp.appointment_id = a.id
    WHERE a.student_id = ? 
    AND a.status = 'completed'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$gallery = $stmt->get_result()->fetch_assoc();
?>

<div class="container py-4">
    <h2 class="mb-4">Student Dashboard</h2>

    <!-- Calendar Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="studentCalendar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Appointment Status Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Appointment Status</h5>
                    <?php if ($appointment): ?>
                        <div class="alert <?php echo $appointment['status'] === 'approved' ? 'alert-success' : 'alert-warning'; ?>">
                            <strong>Status:</strong> <?php echo ucfirst($appointment['status']); ?>
                            <?php if ($appointment['status'] === 'approved'): ?>
                                <p class="mb-0">
                                    <strong>Schedule:</strong> 
                                    <?php 
                                        $schedule = $appointment['preferred_date'] . ' ' . $appointment['preferred_time'];
                                        echo date('F j, Y g:i A', strtotime($schedule)); 
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No active appointments</p>
                        <a href="appointments.php" class="btn btn-primary">Schedule Appointment</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Gallery Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">My Gallery</h5>
                    <div class="text-center">
                        <h3 class="display-4"><?php echo $gallery['photo_count']; ?></h3>
                        <p class="text-muted">Photos Available</p>
                        <a href="gallery.php" class="btn btn-primary">View Gallery</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('studentCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: {
            url: 'api/get_student_schedules.php',
            failure: function() {
                toastr.error('Error loading schedule data');
            }
        },
        eventDisplay: 'block',
        displayEventTime: false,
        eventClick: function(info) {
            if (info.event.extendedProps.remaining_slots > 0) {
                window.location.href = 'appointments.php';
            } else {
                toastr.warning('No available slots for this schedule');
            }
        },
        eventDidMount: function(info) {
            info.el.style.whiteSpace = 'pre-wrap';
            if (info.event.extendedProps.remaining_slots <= 0) {
                info.el.style.cursor = 'not-allowed';
                info.el.style.opacity = '0.7';
            }
        },
        eventContent: function(arg) {
            return {
                html: '<div class="fc-event-title" style="white-space: pre-wrap; padding: 5px;">' + 
                      arg.event.title + '</div>'
            };
        }
    });
    calendar.render();

    // Check if there are any events
    setTimeout(function() {
        if (calendar.getEvents().length === 0) {
            document.getElementById('studentCalendar').innerHTML = 
                '<div class="text-center py-5">' +
                '<h4>Scheduled for Pictorial Coming Soon</h4>' +
                '</div>';
        }
    }, 1000);
});
</script>

<style>
.fc-event {
    cursor: pointer;
}
.fc-event-title {
    white-space: pre-wrap !important;
    overflow: visible;
    font-size: 0.9em;
    line-height: 1.3;
}
.fc-daygrid-event {
    white-space: normal !important;
    margin: 3px !important;
}
</style>
