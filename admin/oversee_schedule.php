<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

?>
<div class="container-fluid">
    <!-- Modern Schedule Dashboard -->
    <div class="card border-0 shadow-lg" style="background-color: #00565d">
        <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #142828">
            <div>
                <i class="fas fa-calendar-alt text-white me-2"></i>
                <h5 class="card-title mb-0 d-inline text-white">Schedule Analytics Dashboard</h5>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown">
                    <i class="far fa-calendar-alt me-1"></i> Last 30 Days
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Last 7 Days</a></li>
                    <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
                    <li><a class="dropdown-item" href="#">Last 90 Days</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Key Metrics Row -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card metric-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-2">Total Slots</h6>
                                    <h3 class="mb-0" id="total-slots">0</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark w-100 text-start">
                                    <i class="fas fa-circle text-primary me-1 fs-10"></i> 
                                    <span id="active-schedules">0</span> Active Schedules
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card metric-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-2">Booked Slots</h6>
                                    <h3 class="mb-0" id="booked-slots">0</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-2 rounded">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                            <div class="progress mt-3" style="height: 6px;">
                                <div id="booking-progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card metric-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-2">Utilization Rate</h6>
                                    <h3 class="mb-0" id="utilization-rate">0%</h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-2 rounded">
                                    <i class="fas fa-chart-pie text-warning"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark w-100 text-start">
                                    <i class="fas fa-circle text-warning me-1 fs-10"></i> 
                                    <span id="peak-day">N/A</span> Peak Day
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card metric-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-2">Avg. Slots/Day</h6>
                                    <h3 class="mb-0" id="avg-slots">0</h3>
                                </div>
                                <div class="bg-info bg-opacity-10 p-2 rounded">
                                    <i class="fas fa-clock text-info"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark w-100 text-start">
                                    <i class="fas fa-circle text-info me-1 fs-10"></i> 
                                    <span id="popular-time">N/A</span> Popular Time
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visualization Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-white border-0">
                            <h6>Booking Trend Over Time</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div id="bookingTrendChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white border-0">
                            <h6>Student Type Distribution</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div id="studentTypeChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Schedule Details</h6>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search schedules..." id="scheduleSearch">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Date Range</th>
                                    <th>Student Type</th>
                                    <th>Program/Strand</th>
                                    <th class="text-center">Time Slots</th>
                                    <th class="text-center">Capacity</th>
                                    <th class="text-center">Booked</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("
                                    SELECT 
                                        v.*, 
                                        COALESCE(cc.name, ss.name, 'General') AS program_name,
                                        CASE 
                                            WHEN v.booked_slots = 0 THEN 'Empty'
                                            WHEN v.booked_slots = v.max_appointments_per_slot THEN 'Full'
                                            WHEN v.booked_slots >= v.max_appointments_per_slot * 0.8 THEN 'Almost Full'
                                            ELSE 'Available'
                                        END AS status
                                    FROM 
                                        v_schedule_summary v
                                    LEFT JOIN 
                                        college_courses cc ON v.course_id = cc.id
                                    LEFT JOIN 
                                        shs_strands ss ON v.strand_id = ss.id
                                    ORDER BY 
                                        v.start_date DESC
                                    LIMIT 50
                                ");
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $trendData = [];
                                $studentTypes = [];
                                $timeSlots = [];

                                while ($row = $result->fetch_assoc()) {
                                    // Process data for visualizations
                                    $date = date('M j', strtotime($row['start_date']));
                                    $trendData[$date] = $trendData[$date] ?? 0;
                                    $trendData[$date] += $row['booked_slots'];
                                    
                                    $studentTypes[$row['student_type']] = ($studentTypes[$row['student_type']] ?? 0) + $row['booked_slots'];
                                    
                                    // Process time slots if needed
                                    
                                    // Output table row
                                    echo '<tr>
                                        <td>'.$row['schedule_id'].'</td>
                                        <td>
                                            <div>'.date('M j', strtotime($row['start_date'])).' - '.date('M j', strtotime($row['end_date'])).'</div>
                                            <small class="text-muted">'.date('D', strtotime($row['start_date'])).'</small>
                                        </td>
                                        <td>'.$row['student_type'].'</td>
                                        <td>'.$row['program_name'].'</td>
                                        <td class="text-center">'.$row['time_slots'].'</td>
                                        <td class="text-center">'.$row['max_appointments_per_slot'].'</td>
                                        <td class="text-center">'.$row['booked_slots'].'</td>
                                        <td class="text-center">
                                            <span class="badge '.getStatusBadgeClass($row['status']).'">'.$row['status'].'</span>
                                        </td>
                                    </tr>';
                                }

                                function getStatusBadgeClass($status) {
                                    switch ($status) {
                                        case 'Full': return 'bg-danger';
                                        case 'Almost Full': return 'bg-warning text-dark';
                                        case 'Available': return 'bg-success';
                                        default: return 'bg-secondary';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.0.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.2.0"></script>

<script>
// Process PHP data
const trendData = <?php echo json_encode($trendData); ?>;
const studentTypeData = <?php echo json_encode($studentTypes); ?>;

// Calculate metrics
const totalSlots = Object.values(trendData).reduce((a, b) => a + b, 0);
const totalDays = Object.keys(trendData).length;
const avgSlotsPerDay = totalDays > 0 ? Math.round(totalSlots / totalDays) : 0;

// Update metric cards
document.getElementById('total-slots').textContent = totalSlots;
document.getElementById('booked-slots').textContent = totalSlots; // Same in this example
document.getElementById('utilization-rate').textContent = '100%'; // Simplified
document.getElementById('avg-slots').textContent = avgSlotsPerDay;
document.getElementById('booking-progress').style.width = '100%';

// Find peak day
let peakDay = '';
let maxSlots = 0;
for (const [day, slots] of Object.entries(trendData)) {
    if (slots > maxSlots) {
        maxSlots = slots;
        peakDay = day;
    }
}
document.getElementById('peak-day').textContent = peakDay;

// Booking Trend Chart (Time Series)
new ApexCharts(document.querySelector("#bookingTrendChart"), {
    series: [{
        name: "Booked Slots",
        data: Object.entries(trendData).map(([date, value]) => ({ x: date, y: value }))
    }],
    chart: {
        type: 'area',
        height: 300,
        toolbar: { show: true },
        zoom: { enabled: true }
    },
    colors: ['#3b82f6'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
        }
    },
    xaxis: { type: 'category' },
    tooltip: {
        x: { format: 'MMM dd' }
    }
}).render();

// Student Type Distribution Chart
new ApexCharts(document.querySelector("#studentTypeChart"), {
    series: Object.values(studentTypeData),
    labels: Object.keys(studentTypeData),
    chart: { type: 'donut', height: 300 },
    plotOptions: { pie: { donut: { size: '70%' } } },
    dataLabels: { enabled: true },
    legend: { position: 'bottom' },
    colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
}).render();

// Table Search Functionality
document.getElementById('scheduleSearch').addEventListener('input', function(e) {
    const value = e.target.value.toLowerCase();
    document.querySelectorAll('#scheduleTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>