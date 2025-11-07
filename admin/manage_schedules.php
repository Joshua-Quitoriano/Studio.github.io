<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

// Add required libraries
?>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css">
<!-- Add Toastr for notifications -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };
</script>
<?php

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><i class="fas fa-calendar me-2"></i> Schedule Management</h2>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-info" onclick="window.location.href='oversee_schedule.php'">
                <i class="fas fa-eye me-2"></i>Oversee Schedule
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                <i class="fas fa-plus me-2"></i>Create New Schedule
            </button>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="formTitle">Create New Schedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="scheduleForm" novalidate>
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <!-- Calendar Column -->
                            <div class="col-md-7">
                                <div class="calendar-wrapper" style="background: #fff; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                                    <div id="scheduleCalendar"></div>
                                </div>
                            </div>

                            <!-- Form Column -->
                            <div class="col-md-5">
                                <input type="hidden" name="schedule_id" id="schedule_id">
                                <input type="hidden" name="start_date" id="start_date">
                                <input type="hidden" name="end_date" id="end_date">
                                <input type="hidden" name="selectedDates" id="formSelectedDates">
                                
                                <div class="mb-3">
                                    <label class="form-label">Student Type</label>
                                    <select class="form-select" name="student_type" id="studentType" required>
                                        <option value="">Select Student Type</option>
                                        <option value="regular_college">Regular College</option>
                                        <option value="octoberian">Octoberian</option>
                                        <option value="senior_high">Senior High School</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Course/Program</label>
                                    <select class="form-select" name="course_id" id="courseSelect" required disabled>
                                        <option value="">Select Course/Program</option>
                                    </select>
                                </div>
                                
                                <input type="hidden" name="selected_dates" id="selectedDates">
                                <div class="mb-3">
                                    <label class="form-label">Selected Dates</label>
                                    <div id="selectedDatesDisplay" class="border rounded p-2 bg-light" style="min-height: 38px">
                                        <small class="text-muted">Select dates from the calendar</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Maximum Appointments per Time Slot</label>
                                    <input type="number" class="form-control" name="max_appointments_per_slot" value="10" min="1" max="50" required>
                                    <div class="form-text">Maximum number of students that can book each time slot (1-50)</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Available Time Slots</label>
                                    <div class="time-slots-wrapper" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Morning Slots -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Morning</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <?php
                                                    $morning_slots = [
                                                        ['7:00 AM', '8:00 AM'],
                                                        ['8:00 AM', '9:00 AM'],
                                                        ['9:00 AM', '10:00 AM'],
                                                        ['10:00 AM', '11:00 AM'],
                                                        ['11:00 AM', '12:00 PM']
                                                    ];
                                                    
                                                    foreach ($morning_slots as $index => $slot) {
                                                        $start = date('H:i', strtotime($slot[0]));
                                                        $end = date('H:i', strtotime($slot[1]));
                                                        $value = $start . '-' . $end;
                                                        $id = 'slot' . ($index + 1);
                                                        echo "<div class='col-12 mb-2'>
                                                            <div class='time-slot-card'>
                                                                <input type='checkbox' class='btn-check' name='time_slots[]' value='{$value}' id='{$id}'>
                                                                <label class='btn btn-outline-primary w-100' for='{$id}'>{$slot[0]} - {$slot[1]}</label>
                                                            </div>
                                                        </div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                
                                        <!-- Afternoon Slots -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Afternoon</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <?php
                                                    $afternoon_slots = [
                                                        ['1:00 PM', '2:00 PM'],
                                                        ['2:00 PM', '3:00 PM'],
                                                        ['3:00 PM', '4:00 PM'],
                                                        ['4:00 PM', '5:00 PM']
                                                    ];
                                                    
                                                    foreach ($afternoon_slots as $index => $slot) {
                                                        $start = date('H:i', strtotime($slot[0]));
                                                        $end = date('H:i', strtotime($slot[1]));
                                                        $value = $start . '-' . $end;
                                                        $id = 'slot' . ($index + 6);
                                                        echo "<div class='col-12 mb-2'>
                                                            <div class='time-slot-card'>
                                                                <input type='checkbox' class='btn-check' name='time_slots[]' value='{$value}' id='{$id}'>
                                                                <label class='btn btn-outline-primary w-100' for='{$id}'>{$slot[0]} - {$slot[1]}</label>
                                                            </div>
                                                        </div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>

                <script>
                function initModalCalendar() {
                    var calendarEl = document.getElementById('scheduleCalendar');
                    if (!calendarEl._calendar) {
                        window.selectedDates = new Set(); // Make it globally accessible
                        var calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            selectable: false, // Disable drag selection
                            selectMirror: true,
                            initialDate: new Date(),
                            unselectAuto: false,
                            multiMonthMaxColumns: 1,
                            height: 'auto', // Auto height to prevent scrolling
                            events: function(fetchInfo, successCallback, failureCallback) {
                                fetch('api/get_schedules.php')
                                    .then(response => response.json())
                                    .then(data => {
                                        const events = data.map(schedule => ({
                                            title: schedule.student_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                                            start: schedule.start_date,
                                            end: moment(schedule.end_date).add(1, 'days').format('YYYY-MM-DD'),  // End at midnight of next day
                                            backgroundColor: getScheduleColor(schedule.student_type),
                                            extendedProps: {
                                                time_slots: JSON.parse(schedule.time_slots),
                                                max_appointments: schedule.max_appointments_per_slot
                                            }
                                        }));
                                        successCallback(events);
                                    })
                                    .catch(error => {
                                        console.error('Error fetching schedules:', error);
                                        failureCallback(error);
                                    });
                            },
                            eventDidMount: function(info) {
                                const timeSlots = info.event.extendedProps.time_slots;
                                const maxAppointments = info.event.extendedProps.max_appointments;
                                
                                // Create tooltip content
                                const formattedSlots = timeSlots.map(slot => {
                                    const [start, end] = slot.split('-');
                                    return formatTime(start) + ' - ' + formatTime(end);
                                }).join('<br>');
                                
                                // Initialize tooltip
                                tippy(info.el, {
                                    content: `
                                        <div class='p-2'>
                                            <strong>Time Slots:</strong><br>
                                            ${formattedSlots}<br>
                                            <strong>Max per slot:</strong> ${maxAppointments}
                                        </div>
                                    `,
                                    allowHTML: true,
                                    theme: 'light',
                                    placement: 'top',
                                    interactive: true
                                });
                            },
                            dayCellDidMount: function(arg) {
                                // Add visual styling for past dates
                                const today = new Date();
                                today.setHours(0, 0, 0, 0);
                                if (arg.date < today) {
                                    arg.el.style.backgroundColor = '#f8f9fa';
                                    arg.el.style.color = '#adb5bd';
                                    arg.el.classList.add('past-date');
                                }
                            },
                            dateClick: function(info) {
                                const clickedDate = new Date(info.dateStr);
                                const today = new Date();
                                today.setHours(0, 0, 0, 0);

                                // Prevent selecting past dates
                                if (clickedDate < today) {
                                    return;
                                }

                                if (window.selectedDates.has(info.dateStr)) {
                                    window.selectedDates.delete(info.dateStr);
                                } else {
                                    window.selectedDates.add(info.dateStr);
                                }
                                renderSelectedDates();
                                updateSelectedDatesDisplay();
                            },
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: ''
                            },
                            titleFormat: { 
                                month: 'short',
                                year: 'numeric'
                            },
                            dayCellDidMount: function(info) {
                                if (selectedDates.has(info.dateStr)) {
                                    info.el.style.backgroundColor = 'rgba(13, 110, 253, 0.2)';
                                }
                            }
                        });
                        calendarEl._calendar = calendar;
                        calendar.render();
                    }
                }

                function formatTime(time) {
                    return new Date('2000-01-01T' + time).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                }

                function renderSelectedDates() {
                    // Clear all previously highlighted dates
                    document.querySelectorAll('.fc-day').forEach(el => {
                        el.style.backgroundColor = '';
                    });
                    
                    // Highlight selected dates
                    window.selectedDates.forEach(dateStr => {
                        const el = document.querySelector(`.fc-day[data-date="${dateStr}"]`);
                        if (el) {
                            el.style.backgroundColor = 'rgba(13, 110, 253, 0.2)';
                        }
                    });
                }

                function updateSelectedDatesDisplay() {
                    const display = document.getElementById('selectedDatesDisplay');
                    const datesInput = document.getElementById('formSelectedDates');
                    const startDateInput = document.getElementById('start_date');
                    const endDateInput = document.getElementById('end_date');
                    const dates = Array.from(window.selectedDates).sort();
                    
                    if (dates.length > 0) {
                        display.innerHTML = dates.map(date => {
                            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                                weekday: 'short',
                                month: 'short',
                                day: 'numeric'
                            });
                            return `<span class="badge bg-primary me-1 mb-1">${formattedDate} <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeDate('${date}')"></button></span>`;
                        }).join('');
                        datesInput.value = JSON.stringify(dates);
                        
                        // Update start and end date inputs
                        startDateInput.value = dates[0];
                        endDateInput.value = dates[dates.length - 1];
                    } else {
                        display.innerHTML = '<small class="text-muted">Select dates from the calendar</small>';
                        datesInput.value = '';
                        startDateInput.value = '';
                        endDateInput.value = '';
                    }
                }

                // Course selection handling
                document.getElementById('studentType').addEventListener('change', function() {
                    const courseSelect = document.getElementById('courseSelect');
                    const selectedType = this.value;
                    let endpoint = '';

                    if (selectedType === 'senior_high') {
                        endpoint = '../api/get_shs_strands.php';
                    } else if (selectedType === 'regular_college' || selectedType === 'octoberian') {
                        endpoint = '../api/get_college_courses.php';
                    }

                    if (endpoint) {
                        courseSelect.disabled = false;
                        courseSelect.innerHTML = '<option value="">Loading courses...</option>';
                        
                        fetch(endpoint)
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    courseSelect.innerHTML = '<option value="">Select Course/Program</option>';
                                    result.data.forEach(item => {
                                        courseSelect.innerHTML += `<option value="${item.id}">${item.code} - ${item.name}</option>`;
                                    });
                                    courseSelect.disabled = false;
                                } else {
                                    throw new Error(result.message || 'Failed to load options');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                                courseSelect.disabled = true;
                            });
                    } else {
                        courseSelect.disabled = true;
                        courseSelect.innerHTML = '<option value="">Select Course/Program</option>';
                    }
                });

                function removeDate(date) {
                    window.selectedDates.delete(date);
                    renderSelectedDates();
                    updateSelectedDatesDisplay();
                }

                // Form validation and submission
                document.getElementById('scheduleForm').addEventListener('submit', function (e) {
                    e.preventDefault(); // prevent actual form submit

                    const form = e.target;
                    const formData = new FormData(form);

                    // You can validate here if needed...

                    fetch('api/save_schedule.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message || 'Schedule saved successfully');

                            // Clear query params from URL bar
                            history.replaceState(null, '', window.location.pathname);

                            // Close modal if needed
                            const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
                            if (modal) modal.hide();

                            // Reset form
                            form.reset();

                            // Clear and update custom date picker if used
                            if (window.selectedDates) {
                                window.selectedDates.clear();
                                renderSelectedDates?.();
                                updateSelectedDatesDisplay?.();
                            }

                            // Refresh calendar if applicable
                            document.getElementById('mainCalendar')?._calendar?.refetchEvents();
                            refreshScheduleTable();
                        } else {
                            toastr.error(data.message || 'Failed to save schedule');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('An error occurred while saving the schedule');
                    });
                });


                document.getElementById('scheduleModal').addEventListener('show.bs.modal', function () {
                    const courseSelect = document.getElementById('courseSelect');
                    courseSelect.disabled = true;
                    courseSelect.innerHTML = '<option value="">Select Course/Program</option>';
                    document.getElementById('studentType').value = '';
                    window.selectedDates.clear();
                    updateSelectedDatesDisplay();
                    
                    // Reset time slots
                    document.querySelectorAll('input[name="time_slots[]"]').forEach(input => {
                        input.checked = false;
                    });
                    
                    // Reset max appointments
                    document.querySelector('input[name="max_appointments_per_slot"]').value = '10';
                });

                // Initialize modal calendar when modal is shown
                document.getElementById('scheduleModal').addEventListener('shown.bs.modal', function () {
                    initModalCalendar();
                });

                // Add custom styles for calendar
                document.head.insertAdjacentHTML('beforeend', `
                    <style>
                        .fc .fc-daygrid-day-number { text-decoration: none; }
                        .fc .fc-highlight { background-color: rgba(13, 110, 253, 0.2); }
                        .time-slot-card { margin-bottom: 0.5rem; }
                        .fc .past-date { cursor: not-allowed; }
                        .fc .past-date:hover { background-color: #f8f9fa !important; }
                        .time-slot-card .btn { text-align: left; padding: 0.75rem; }
                        .btn-check:checked + .btn-outline-primary { background-color: var(--bs-primary) !important; }
                    </style>
                `);
                </script>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <!-- Calendar View -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Schedule Calendar</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="mainCalendar"></div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('mainCalendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },
                    events: function(info, successCallback, failureCallback) {
                        // Fetch events via AJAX
                        fetch('api/get_schedules.php')
                            .then(response => response.json())
                            .then(data => {
                                const events = data.map(schedule => {
                                    const slots = JSON.parse(schedule.time_slots);
                                    const type = schedule.student_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    const formattedTitle = type + (schedule.program_name ? ' [' + schedule.program_name + ']' : '');
                                    return {
                                        id: schedule.id,
                                        title: formattedTitle,
                                        start: schedule.start_date,
                                        end: moment(schedule.end_date).add(1, 'days').format('YYYY-MM-DD'),  // End at midnight of next day
                                        backgroundColor: getScheduleColor(schedule.student_type),
                                        borderColor: getScheduleColor(schedule.student_type),
                                        allDay: true,
                                        extendedProps: {
                                            slots: slots,
                                            maxPerSlot: schedule.max_appointments_per_slot,
                                            type: schedule.student_type,
                                            program: schedule.program_name
                                        }
                                    };
                                });
                                successCallback(events);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                failureCallback(error);
                            });
                    },
                    eventDidMount: function(info) {
                        const slots = info.event.extendedProps.slots;
                        const maxPerSlot = info.event.extendedProps.maxPerSlot;
                        const type = info.event.extendedProps.type;
                        
                        tippy(info.el, {
                            content: `
                                <div class="p-2">
                                    <h6 class="mb-2">${type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}${info.event.extendedProps.program ? ' [' + info.event.extendedProps.program + ']' : ''}</h6>
                                    <div class="small">
                                        <div class="mb-1">Time Slots:</div>
                                        ${slots.map(slot => `<div>• ${slot}</div>`).join('')}
                                        <div class="mt-1">Max ${maxPerSlot} appointments per slot</div>
                                    </div>
                                </div>
                            `,
                            allowHTML: true,
                            theme: 'light',
                            placement: 'top',
                            interactive: true
                        });
                    },
                    height: 650,
                    dayMaxEvents: true
                });
                calendar.render();
                calendarEl._calendar = calendar;
            });
            </script>

            <!-- Schedule List -->
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <h5 class="card-title mb-0">Upcoming Schedules</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover w-100">
                            <colgroup>
                                <col style="width: 12%">
                                <col style="width: 25%">
                                <col style="width: 20%">
                                <col style="width: 23%">
                                <col style="width: 8%">
                                <col style="width: 12%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Student Type</th>
                                    <th>Program/Course</th>
                                    <th>Date Range</th>
                                    <th>Time Slots</th>
                                    <th class="text-center">Max per Slot</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody">
                                <!-- JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-wrapper {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 15px;
    height: 100%;
}

#scheduleCalendar {
    height: 100%;
    min-height: 400px;
}

.time-slots-wrapper {
    max-height: 400px;
    overflow-y: auto;
}

.schedule-item {
    cursor: pointer;
    font-size: 0.8rem;
    opacity: 0.8;
}
.schedule-item:hover {
    opacity: 1;
}
.morning-slots, .afternoon-slots {
    max-height: 200px;
    overflow-y: auto;
}
</style>

<script>
function getScheduleColor(type) {
    const colors = {
        'regular_college': '#8dbd8b',
        'octoberian': '#f4c17e',
        'senior_high': '#a7c7e7'
    };
    return colors[type] || '#gray';
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle "Select All" checkboxes
    document.querySelectorAll('.select-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const period = this.dataset.period;
            const slots = document.querySelectorAll(`.${period}-slot`);
            slots.forEach(slot => slot.checked = this.checked);
        });
    });
});


function resetForm() {
    const form = document.getElementById('scheduleForm');
    document.getElementById('formTitle').textContent = 'Create New Schedule';
    form.reset();
    document.getElementById('schedule_id').value = '';
}

function deleteSchedule(id) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        fetch('api/delete_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete schedule: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the schedule');
        });
    }
}

function refreshScheduleTable() {
    fetch('api/get_schedule_table_rows.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('scheduleTableBody').innerHTML = html;
        })
        .catch(error => {
            console.error('Failed to refresh table:', error);
        });
}
refreshScheduleTable();

setInterval(refreshScheduleTable, 10000);

</script>

<?php
function getScheduleColor($type) {
    $colors = [
        'regular_college' => '#8dbd8b',
        'octoberian' => '#f4c17e',
        'senior_high' => '#a7c7e7'
    ];
    return $colors[$type] ?? '#gray';
}
?>