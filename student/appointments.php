<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkStudentAccess();

// Add required libraries
?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<?php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["success" => false, "message" => "Session user_id is not set"]));
}

// Prepare query to find the student
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']); // Using 'i' since id is integer
$stmt->execute();
$result = $stmt->get_result();

// If student is not found, return error
if ($result->num_rows === 0) {
    die(json_encode(["success" => false, "message" => "Student information not found"]));
}
 
$student = $result->fetch_assoc();

// Get student's appointments that are not cancelled
$stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE student_id = ? 
    AND status != 'cancelled'
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if student has any appointments
$hasAppointment = !empty($appointments);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Appointments</h2>
        <?php if (!$hasAppointment): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                <i class="fas fa-plus"></i> Schedule New Appointment
            </button>
        <?php endif; ?>
    </div>

    <!-- Appointments List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($appointments)): ?>
                <p class="text-center text-muted">No appointments found. Schedule your first appointment!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Requested Date</th>
                                <th>Requested Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($apt['preferred_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($apt['preferred_time'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] === 'approved' ? 'success' : 
                                                ($apt['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($apt['status'] === 'pending'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="cancelAppointment(<?php echo $apt['id']; ?>)">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($apt['status'] === 'approved'): ?>
                                            <a href="view_appointment.php?appointment_id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-primary">
                                                View Appointment
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="formTitle">Schedule New Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="appointmentForm" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <input type="hidden" id="scheduleId" name="schedule_id">
                        <!-- Calendar Column -->
                        <div class="col-md-7">
                            <div class="calendar-wrapper" style="background: #fff; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                                <div id="appointmentCalendar"></div>
                            </div>
                        </div>
                        <!-- Time Slots Column -->
                        <div class="col-md-5">
                            <div class="mb-3" id="timeSlotSection" style="display: none;">
                                <label class="form-label">Available Time Slots</label>
                                <div class="time-slots-wrapper" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Morning Slots -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Morning</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="morningSlots" class="d-flex flex-wrap gap-2"></div>
                                        </div>
                                    </div>
                                    <!-- Afternoon Slots -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Afternoon</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="afternoonSlots" class="d-flex flex-wrap gap-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="noDateMessage" class="text-center py-4">
                                <p class="text-muted">Please select a date from the calendar</p>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes?"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="receipt" class="form-label">Upload Receipt</label>
                                <input type="file" id="receipt" name="receipt" class="form-control" accept="image/*,.pdf" required>
                                <small class="text-muted">Accepted formats: Images, PDF</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="scheduleBtn" disabled>
                        <i class="fas fa-calendar-check me-2"></i>Schedule Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FullCalendar Dependencies -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
/* Calendar Styles */
.calendar-wrapper {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    padding: 1rem;
    margin-bottom: 1rem;
}

#appointmentCalendar {
    min-height: 525px;
    background: white;
}

/* Style for highlighted dates with appointments */
.has-appointments {
    cursor: pointer !important;
    background-color: rgba(40, 167, 69, 0.15) !important;
    border: 1px solid rgba(40, 167, 69, 0.25) !important;
}

/* Style for the currently selected date */
.selected-date {
    background-color: rgba(13, 110, 253, 0.2) !important;
    border: 2px solid #0d6efd !important;
    box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
}

/* Style time slot buttons */
.time-slot-btn.selected {
    font-weight: 600;
}

/* Improve the time slots section */
.time-slots-wrapper {
    border-radius: 10px;
    overflow: hidden;
}

.fc {
    background: white !important;
}

.fc .fc-toolbar {
    padding: 1rem;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.fc .fc-button-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.fc .fc-button-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.fc .fc-button-primary:disabled {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.fc-day-past {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.fc-day-today {
    background-color: #e9ecef !important;
}

.fc-day-selected {
    background-color: #cfe2ff !important;
}

.fc-day-disabled {
    background-color: #f8f9fa !important;
    cursor: not-allowed;
}

/* Time Slots Styles */
.time-slots-wrapper {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    padding: 1rem;
}

.time-slots-list {
    max-height: 250px;
    overflow-y: auto;
}

.time-slot {
    display: block;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    color: #495057;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.time-slot:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
    color: #0d6efd;
}

.time-slot.selected {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.time-slot.disabled {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #adb5bd;
    cursor: not-allowed;
}

/* Form Controls */
.form-control {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedDate = null;
    let selectedTime = null;
    let scheduleId = null;

    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    // Handle form submission
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();

        if (!selectedDate || !selectedTime || !scheduleId) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Information',
                text: 'Please select a date and time slot.'
            });
            return;
        }

        const formData = new FormData(this);
        formData.append('selected_date', selectedDate);
        formData.append('selected_time', selectedTime);
        formData.append('schedule_id', scheduleId);

        // Disable submit button and show loading state
        const submitBtn = document.getElementById('scheduleBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Scheduling...';

        fetch('api/save_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Scheduled!',
                    text: data.message
                }).then(() => {
                    $('#scheduleModal').modal('hide');
                    location.reload(); // Reload after success
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An error occurred while scheduling the appointment.'
            });
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    
    // Initialize calendar
    var calendarEl = document.getElementById('appointmentCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: false, // Don't allow date selection directly
        unselectAuto: false,
        selectMirror: false,
        events: 'api/get_student_schedules.php',
        eventDisplay: 'block', // Show events as blocks
        eventColor: '#28a745', // Default color for available slots
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        // Handle multi-day events by treating each day as selectable
        eventDidMount: function(info) {
            // Add a custom class to highlight dates with appointments
            if (info.event.extendedProps.remaining_slots > 0) {
                info.el.classList.add('has-appointments');
                
                // Handle multi-day events
                if (info.event.end) {
                    const startDate = new Date(info.event.start);
                    const endDate = new Date(info.event.end);

                    startDate.setHours(0, 0, 0, 0);
                    endDate.setHours(0, 0, 0, 0);
                    
                    // If this is a multi-day event, mark all days in the range
                    if (startDate.toDateString() !== endDate.toDateString()) {
                        console.log('Multi-day event detected:', startDate, 'to', endDate);
                        
                        const currentDate = new Date(startDate);

                        // Loop through all dates in range and mark them
                        while (currentDate < endDate) {
                            // Format as YYYY-MM-DD
                            const year = currentDate.getFullYear();
                            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                            const day = String(currentDate.getDate()).padStart(2, '0');
                            const dateStr = `${year}-${month}-${day}`;

                            const dayCell = document.querySelector(`.fc-day[data-date="${dateStr}"]`);
                            
                            if (dayCell) {
                                dayCell.classList.add('has-appointments');
                                dayCell.setAttribute('data-schedule-id', info.event.id);
                                console.log('Marked date:', dateStr, 'with schedule ID:', info.event.id);
                            }
                            
                            // Move to next day
                            currentDate.setDate(currentDate.getDate() + 1);
                        }
                    }
                }
            }
        },
        
        // When rendering is complete, we'll do additional processing
        datesSet: function(info) {
            console.log('Calendar dates set, processing multi-day events...');
            // Process all days that were marked with data-schedule-id
            document.querySelectorAll('.fc-day[data-schedule-id]').forEach(dayCell => {
                dayCell.classList.add('has-appointments');
                dayCell.style.cursor = 'pointer';
            });
        },
        eventClick: function(info) {
            const event = info.event;
            console.log('Event clicked:', event);
            
            if (event.extendedProps.remaining_slots > 0) {
                // Get the schedule data from the event
                scheduleId = event.id;
                selectedDate = event.startStr.split('T')[0]; // Get just the date part
                
                // Extract time slots from the event
                const timeSlots = event.extendedProps.time_slots || [];
                
                // Show time slots directly from the event data
                displayTimeSlots({
                    schedule_id: scheduleId,
                    selected_date: selectedDate,
                    time_slots: timeSlots,
                    max_slots: event.extendedProps.max_slots,
                    remaining_slots: event.extendedProps.remaining_slots
                });
                
                // Highlight the selected date in the calendar
                document.querySelectorAll('.fc-day').forEach(day => {
                    day.classList.remove('selected-date');
                });
                
                // Find the day cell for this date and highlight it
                const dateStr = event.startStr.split('T')[0];
                const dayCell = document.querySelector(`.fc-day[data-date="${dateStr}"]`);
                if (dayCell) {
                    dayCell.classList.add('selected-date');
                }
            } else {
                toastr.warning('No available slots for this date');
            }
        },
        dateClick: function(info) {
            // When a date is clicked, look for events on that date
            const clickedDate = info.dateStr;
            console.log('Date clicked:', clickedDate);
            
            // First check if this date has been marked as part of a multi-day event
            const scheduleIdFromCell = info.dayEl.getAttribute('data-schedule-id');
            
            if (scheduleIdFromCell) {
                console.log('Found schedule ID from cell attributes:', scheduleIdFromCell);
                // We have a direct schedule ID from the date cell
                scheduleId = scheduleIdFromCell;
                selectedDate = clickedDate;
                
                // Find the original event to get its data
                const events = calendar.getEvents();
                const matchingEvent = events.find(event => event.id == scheduleId);
                
                if (matchingEvent) {
                    // Show time slots for this event
                    displayTimeSlots({
                        schedule_id: scheduleId,
                        selected_date: selectedDate,
                        time_slots: matchingEvent.extendedProps.time_slots || [],
                        max_slots: matchingEvent.extendedProps.max_slots,
                        remaining_slots: matchingEvent.extendedProps.remaining_slots
                    });
                    
                    // Highlight the selected date
                    document.querySelectorAll('.fc-day').forEach(day => {
                        day.classList.remove('selected-date');
                    });
                    info.dayEl.classList.add('selected-date');
                    
                    return; // We're done, no need to check further
                }
            }
            
            // If we get here, try the original method - check events that start on this date
            const events = calendar.getEvents();
            let foundEvent = false;
            
            events.forEach(event => {
                // Check start date
                const eventStartDate = event.startStr.split('T')[0];
                
                // Check if this is an event that starts on the clicked date or includes it in its range
                if (eventStartDate === clickedDate && event.extendedProps.remaining_slots > 0) {
                    // Trigger a click on the event
                    scheduleId = event.id;
                    selectedDate = clickedDate;
                    
                    // Show time slots for this event
                    displayTimeSlots({
                        schedule_id: event.id,
                        selected_date: selectedDate,
                        time_slots: event.extendedProps.time_slots || [],
                        max_slots: event.extendedProps.max_slots,
                        remaining_slots: event.extendedProps.remaining_slots
                    });
                    
                    // Highlight the selected date
                    document.querySelectorAll('.fc-day').forEach(day => {
                        day.classList.remove('selected-date');
                    });
                    info.dayEl.classList.add('selected-date');
                    
                    foundEvent = true;
                }
                // Also check if this date falls between the event's start and end
                else if (event.end) {
                    const clickedDateObj = new Date(clickedDate + 'T00:00:00');
                    const eventStart = new Date(event.start);
                    const eventEnd = new Date(event.end);
                    
                    if (clickedDateObj >= eventStart && clickedDateObj < eventEnd && event.extendedProps.remaining_slots > 0) {
                        // This date is within a multi-day event range
                        scheduleId = event.id;
                        selectedDate = clickedDate;
                        
                        // Show time slots for this event
                        displayTimeSlots({
                            schedule_id: event.id,
                            selected_date: selectedDate,
                            time_slots: event.extendedProps.time_slots || [],
                            max_slots: event.extendedProps.max_slots,
                            remaining_slots: event.extendedProps.remaining_slots
                        });
                        
                        // Highlight the selected date
                        document.querySelectorAll('.fc-day').forEach(day => {
                            day.classList.remove('selected-date');
                        });
                        info.dayEl.classList.add('selected-date');
                        
                        foundEvent = true;
                    }
                }
            });
            
            if (!foundEvent) {
                // Clear any existing time slots
                // Remove any existing date information
                const existingDateInfo = document.querySelector('#timeSlotSection .alert');
                if (existingDateInfo) {
                    existingDateInfo.remove();
                }
                
                document.getElementById('timeSlotSection').style.display = 'block';
                document.getElementById('noDateMessage').style.display = 'none';
                document.getElementById('morningSlots').innerHTML = '<div class="text-muted text-center py-4">No available schedule for this date</div>';
                document.getElementById('afternoonSlots').innerHTML = '<div class="text-muted text-center py-4">Please select a highlighted date</div>';
                
                // Add an info message about selecting dates
                const infoMsg = document.createElement('div');
                infoMsg.className = 'alert alert-warning mt-3';
                infoMsg.innerHTML = 'Please select a date that is highlighted in green to view available time slots.';
                document.getElementById('timeSlotSection').prepend(infoMsg);
            }
        },
        unselect: function() {
            selectedDate = null;
            selectedTime = null;
            document.getElementById('scheduleBtn').disabled = true;
        }
    });
    calendar.render();

    // Handle URL parameters for direct booking
    const urlParams = new URLSearchParams(window.location.search);
    const dateParam = urlParams.get('date');
    if (dateParam) {
        handleDateSelection(dateParam);
    }

    function initializeCalendar() {
        const calendarEl = document.getElementById('appointmentCalendar');
        if (!calendarEl) return;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: 'today'
            },
            height: 'auto',
            aspectRatio: 1.35,
            firstDay: 1,
            selectable: true,
            unselectAuto: false,
            fixedWeekCount: false,
            showNonCurrentDates: true,
            dayCellClassNames: function(arg) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (arg.date < today) {
                    return ['fc-day-disabled'];
                }
                
                const day = arg.date.getDay();
                if (day === 0 || day === 6) {
                    return ['fc-day-disabled'];
                }
                
                return [];
            },
            dateClick: function(info) {
                const clickedDate = new Date(info.dateStr);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (clickedDate < today) {
                    alert('Cannot select past dates');
                    return;
                }

                if (clickedDate.getDay() === 0 || clickedDate.getDay() === 6) {
                    alert('Please select a weekday (Monday-Friday)');
                    return;
                }

                // Remove previous selection
                document.querySelectorAll('.fc-day-selected').forEach(el => {
                    el.classList.remove('fc-day-selected');
                });
                
                // Add selection to clicked date
                info.dayEl.classList.add('fc-day-selected');
                
                selectedDate = info.dateStr;
                
                // Find a schedule for this date from the calendar events
                const events = calendar.getEvents();
                let foundSchedule = false;
                
                events.forEach(event => {
                    const eventDate = new Date(event.start);
                    const selectedDay = new Date(selectedDate);
                    
                    // Compare year, month, and day to match dates
                    if (eventDate.getFullYear() === selectedDay.getFullYear() && 
                        eventDate.getMonth() === selectedDay.getMonth() && 
                        eventDate.getDate() === selectedDay.getDate()) {
                        
                        // Use the schedule_id from the event
                        scheduleId = event.id;
                        foundSchedule = true;
                        console.log('Found schedule ID:', scheduleId, 'for date:', selectedDate);
                    }
                });
                
                if (foundSchedule) {
                    loadTimeSlots(selectedDate);
                } else {
                    console.log('No schedule found for date:', selectedDate);
                    morningSlots.innerHTML = '<div class="text-muted text-center py-4">No available schedule for this date</div>';
                    afternoonSlots.innerHTML = '<div class="text-muted text-center py-4">No available schedule for this date</div>';
                    document.getElementById('timeSlotSection').style.display = 'block';
                    document.getElementById('noDateMessage').style.display = 'none';
                }
            }
        });
        
        calendar.render();
    }

    // Initialize calendar when modal opens
    const scheduleModal = document.getElementById('scheduleModal');
    scheduleModal.addEventListener('shown.bs.modal', function () {
        if (!calendar) {
            initializeCalendar();
        } else {
            calendar.render();
        }
    });

    // New function to display time slots directly from the event data
    function displayTimeSlots(eventData) {
        console.log('Displaying time slots:', eventData);
        
        const morningSlots = document.getElementById('morningSlots');
        const afternoonSlots = document.getElementById('afternoonSlots');
        
        morningSlots.innerHTML = '';
        afternoonSlots.innerHTML = '';
        
        document.getElementById('timeSlotSection').style.display = 'block';
        document.getElementById('noDateMessage').style.display = 'none';
        
        // Remove any existing date information
        const existingDateInfo = document.querySelector('#timeSlotSection .alert');
        if (existingDateInfo) {
            existingDateInfo.remove();
        }
        
        // Show selected date information
        const selectedDateInfo = document.createElement('div');
        selectedDateInfo.className = 'alert alert-info mb-3';
        selectedDateInfo.innerHTML = `Selected Date: <strong>${formatDate(eventData.selected_date)}</strong> - Available Slots: <strong>${eventData.remaining_slots}</strong>`;
        document.getElementById('timeSlotSection').prepend(selectedDateInfo);
        
        if (!eventData.time_slots || eventData.time_slots.length === 0) {
            morningSlots.innerHTML = '<div class="text-muted text-center py-4">No slots available</div>';
            afternoonSlots.innerHTML = '<div class="text-muted text-center py-4">No slots available</div>';
            return;
        }
        
        // Process time slots from the event data
        eventData.time_slots.forEach(timeSlot => {
            // Create a slot object
            const slot = {
                time: timeSlot.split('-')[0], // Use start time from range
                end_time: timeSlot.split('-')[1], // Store end time too
                available: true,
                remaining: eventData.remaining_slots,
                max: eventData.max_slots
            };
            
            const btn = createTimeSlotButton(slot);
            
            // Determine if it's morning or afternoon
            const hour = parseInt(slot.time.split(':')[0]);
            if (hour < 12) {
                morningSlots.appendChild(btn);
            } else {
                afternoonSlots.appendChild(btn);
            }
        });
        
        // Helper function to format date nicely
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }
    
    // Keep the loadTimeSlots function for backward compatibility
    function loadTimeSlots(date) {
        console.log('Legacy loadTimeSlots called for date:', date);
        
        if (!scheduleId) {
            console.error('No schedule ID available');
            document.getElementById('timeSlotSection').style.display = 'block';
            document.getElementById('noDateMessage').style.display = 'none';
            document.getElementById('morningSlots').innerHTML = '<div class="text-muted text-center py-4">No schedule available for this date</div>';
            document.getElementById('afternoonSlots').innerHTML = '<div class="text-muted text-center py-4">Please select a highlighted date</div>';
            return;
        }
        
        // Instead of loading from API, find the event in calendar and use its data
        const events = calendar.getEvents();
        const matchingEvent = events.find(event => event.id == scheduleId);
        
        if (matchingEvent) {
            displayTimeSlots({
                schedule_id: matchingEvent.id,
                time_slots: matchingEvent.extendedProps.time_slots || [],
                max_slots: matchingEvent.extendedProps.max_slots,
                remaining_slots: matchingEvent.extendedProps.remaining_slots
            });
        } else {
            // Fallback to API if event not found in calendar
            const morningSlots = document.getElementById('morningSlots');
            const afternoonSlots = document.getElementById('afternoonSlots');
            
            morningSlots.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            afternoonSlots.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            
            fetch(`api/get_student_schedules.php`)
                .then(response => response.json())
                .then(events => {
                    const matchingEvent = events.find(evt => evt.id == scheduleId);
                    if (matchingEvent) {
                        displayTimeSlots({
                            schedule_id: matchingEvent.id,
                            time_slots: matchingEvent.time_slots || [],
                            max_slots: matchingEvent.max_slots,
                            remaining_slots: matchingEvent.remaining_slots
                        });
                    } else {
                        morningSlots.innerHTML = '<div class="text-muted text-center py-4">No schedule found</div>';
                        afternoonSlots.innerHTML = '<div class="text-muted text-center py-4">Please try another date</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading schedules:', error);
                    morningSlots.innerHTML = '<div class="text-muted text-center py-4">Error loading schedule</div>';
                    afternoonSlots.innerHTML = '<div class="text-muted text-center py-4">Please try again</div>';
                });
        }
    }

    function selectTimeSlot(btn, time) {
        // Reset all buttons
        document.querySelectorAll('.time-slot-btn.selected').forEach(el => {
            el.classList.remove('selected');
            el.classList.remove('btn-primary');
            el.classList.add('btn-outline-primary');
        });
        
        // Highlight selected button
        btn.classList.add('selected');
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
        
        selectedTime = time;
        validateForm();
    }

    // Form validation and submission
    const appointmentForm = document.getElementById('appointmentForm');
    const scheduleBtn = document.getElementById('scheduleBtn');
    const receiptInput = document.getElementById('receipt');

    function validateForm() {
        const isValid = selectedDate && selectedTime && receiptInput.files.length > 0;
        scheduleBtn.disabled = !isValid;
        if (isValid) {
            scheduleBtn.classList.remove('btn-secondary');
            scheduleBtn.classList.add('btn-primary');
        } else {
            scheduleBtn.classList.remove('btn-primary');
            scheduleBtn.classList.add('btn-secondary');
        }
    }

    receiptInput.addEventListener('change', validateForm);

    function handleDateSelection(date) {
        selectedDate = date;
        document.getElementById('noDateMessage').style.display = 'none';
        document.getElementById('timeSlotSection').style.display = 'block';
        document.getElementById('morningSlots').innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';
        document.getElementById('afternoonSlots').innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';

        // Fetch available time slots
        fetch(`api/get_available_slots.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }

                scheduleId = data.data.schedule_id;
                document.getElementById('scheduleId').value = scheduleId;

                // Group time slots by period (morning/afternoon)
                const slots = data.data.slots;
                const morning = slots.filter(slot => {
                    const hour = parseInt(slot.time.split(':')[0]);
                    return hour < 12;
                });
                const afternoon = slots.filter(slot => {
                    const hour = parseInt(slot.time.split(':')[0]);
                    return hour >= 12;
                });

                let html = '';
                
                // Morning slots
                if (morning.length > 0) {
                    html += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Morning</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    ${morning.map(slot => createTimeSlotButton(slot)).join('')}
                                </div>
                            </div>
                        </div>`;
                }

                // Afternoon slots
                if (afternoon.length > 0) {
                    html += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Afternoon</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    ${afternoon.map(slot => createTimeSlotButton(slot)).join('')}
                                </div>
                            </div>
                        </div>`;
                }

                // Split slots into morning and afternoon
                const morningSlots = slots.filter(slot => {
                    const hour = parseInt(slot.time.split(':')[0]);
                    return hour < 12;
                });

                const afternoonSlots = slots.filter(slot => {
                    const hour = parseInt(slot.time.split(':')[0]);
                    return hour >= 12;
                });

                // Update morning slots
                document.getElementById('morningSlots').innerHTML = morningSlots.length > 0 ?
                    morningSlots.map(slot => createTimeSlotButton(slot)).join('') :
                    '<p class="text-muted mb-0">No morning slots available</p>';

                // Update afternoon slots
                document.getElementById('afternoonSlots').innerHTML = afternoonSlots.length > 0 ?
                    afternoonSlots.map(slot => createTimeSlotButton(slot)).join('') :
                    '<p class="text-muted mb-0">No afternoon slots available</p>';

                // Add click handlers to time slot buttons
                document.querySelectorAll('.time-slot-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        selectedTime = this.dataset.time;
                        document.getElementById('submitAppointment').disabled = false;
                    });
                });
            })
            .catch(error => {
                document.getElementById('timeSlots').innerHTML = `
                    <div class="alert alert-danger">
                        ${error.message || 'Failed to load time slots'}
                    </div>`;
            });
    }

    function createTimeSlotButton(slot) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn m-1 time-slot-btn ' + (slot.available ? 'btn-outline-primary' : 'btn-secondary');
        btn.disabled = !slot.available;
        btn.textContent = slot.time;
        
        if (slot.available) {
            btn.addEventListener('click', () => selectTimeSlot(btn, slot.time));
        }
        
        // Update the button text to show time in a nice format
        const startTime = new Date(`2000-01-01T${slot.time}`);
        let timeText = startTime.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        if (slot.end_time) {
            const endTime = new Date(`2000-01-01T${slot.end_time}`);
            timeText += ' - ' + endTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
        
        btn.textContent = timeText;
        
        // Add a small badge showing available slots
        if (slot.remaining) {
            const badge = document.createElement('span');
            badge.className = 'badge bg-success ms-2';
            badge.textContent = `${slot.remaining} slots`;
            btn.appendChild(badge);
        }
        
        return btn;
    }

    // Handle form submission
    document.getElementById('scheduleBtn').addEventListener('click', function() {
        if (!selectedDate || !selectedTime || !scheduleId) {
            toastr.error('Please select both date and time');
            return;
        }

        const formData = new FormData();
        formData.append('schedule_id', scheduleId);
        formData.append('preferred_date', selectedDate);
        formData.append('preferred_time', selectedTime);
        formData.append('preferred_time', selectedTime);
        formData.append('notes', document.getElementById('notes').value.trim() || "No additional notes");
        formData.append('receipt', receiptInput.files[0]);

        scheduleBtn.disabled = true;
        scheduleBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Scheduling...';

        fetch('process_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Appointment scheduled successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                scheduleBtn.disabled = false;
                scheduleBtn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Schedule Appointment';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while scheduling the appointment');
            scheduleBtn.disabled = false;
            scheduleBtn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Schedule Appointment';
        });
    });
});
</script>

<script>
function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }

    fetch('cancel_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            appointment_id: appointmentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error cancelling appointment: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error cancelling appointment');
    });
}
</script>