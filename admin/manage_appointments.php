<?php
session_start();
$pageTitle = "Appointment Management"; 
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = filter_var($_POST['appointment_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = sanitize_input($_POST['status']);
    $notes = sanitize_input($_POST['notes'] ?? '');

    $stmt = $conn->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $notes, $appointment_id);
    $stmt->execute();
}

// Get appointment statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'rejected' => 0
];

$result = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get filters
$student_type = $_GET['student_type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT a.*, 
           s.first_name,
           s.last_name,
           s.student_id,
           s.student_type,
           COALESCE(cc.name, ss.name, 'N/A') AS program_name
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN student_academic_info sai ON s.id = sai.student_id
    LEFT JOIN college_courses cc ON sai.college_course_id = cc.id
    LEFT JOIN shs_strands ss ON sai.shs_strand_id = ss.id
    WHERE a.status != 'cancelled'
";

$params = [];
$types = "";

// Add filters dynamically
if ($student_type !== 'all') {
    $query .= " AND s.student_type = ?";
    $params[] = $student_type;
    $types .= "s";
}

if ($status !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

$query .= " ORDER BY a.preferred_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
    .program-badge {
        background-color: #e3f2fd;
        color: #0d6efd;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }
    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .status-badge.approved {
        background-color: #d1e7dd;
        color: #146c43;
    }
    .status-badge.rejected {
        background-color: #f8d7da;
        color: #b02a37;
    }
    .status-badge.cancelled {
        background-color:rgb(102, 102, 102);
        color: #fff;
    }
    .status-badge.completed {
        background-color: #d1e7dd;
        color: #146c43;
    }
    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .stats-card {
        transition: transform 0.2s;
        border-radius: 15px !important;
        min-height: 140px;
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
    }
    .stats-card::before {
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
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-card .card-body {
        position: relative;
        z-index: 2;
        padding: 1.5rem;
        height: 100%;
    }
    .stats-card h3 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    .stats-card p {
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }
    .stats-card i {
        font-size: 2rem;
        opacity: 0.9;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    .stats-card.total { background-image: url('../includes/card-primary.png'); }
    .stats-card.pending { background-image: url('../includes/card-warning.png'); }
    .stats-card.approved { background-image: url('../includes/card-success.png'); }
    .stats-card.completed { background-image: url('../includes/card-success.png'); }
    .stats-card.cancelled { background-image: url('../includes/card-dark.png'); }
    .stats-card.rejected { background-image: url('../includes/card-danger.png'); }
</style>

<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<div class="container py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i> Appointment Management</h2>
            <p class="text-muted">Manage and track all student appointments</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <?php 
        $icons = [
            'total' => 'fa-calendar',
            'pending' => 'fa-clock',
            'approved' => 'fa-check-circle',
            'completed' => 'fa-check-double',
            'cancelled' => 'fa-ban',
            'rejected' => 'fa-times-circle'
        ];
        foreach ($stats as $stat_status => $count): ?>
            <div class="col-md-4 col-lg-2">
                <div class="card stats-card shadow-sm text-white h-100 <?php echo $stat_status; ?>">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <i class="fas <?php echo $icons[$stat_status]; ?> mb-2"></i>
                        <h3 class="mb-2"><?php echo $count; ?></h3>
                        <p class="mb-0"><?php echo ucfirst($stat_status); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchAppointment" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or student number">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="student_type" id="studentType" class="form-select">
                        <option value="all" <?php echo $student_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="regular_college" <?php echo $student_type === 'regular_college' ? 'selected' : ''; ?>>Regular College</option>
                        <option value="octoberian" <?php echo $student_type === 'octoberian' ? 'selected' : ''; ?>>Octoberian</option>
                        <option value="senior_high" <?php echo $student_type === 'senior_high' ? 'selected' : ''; ?>>Senior High School</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>


                <!-- Appointments Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Student No.</th>
                                <th>Program/Course</th>
                                <th>Preferred Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr data-id="<?php echo $appointment['id']; ?>"
                                    data-search="<?php echo strtolower($appointment['first_name'] . ' ' . $appointment['last_name'] . ' ' . $appointment['student_id']); ?>" 
                                    data-student-type="<?php echo $appointment['student_type']; ?>" 
                                    data-status="<?php echo $appointment['status']; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['student_id']); ?></td>
                                    <td>
                                        <span class="program-badge">
                                            <?php echo htmlspecialchars($appointment['program_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($appointment['preferred_date'])); ?></td>
                                    <td class="status-cell">
                                        <span class="status-badge <?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-info btn-sm view-student me-1"
                                                    data-id="<?php echo $appointment['id']; ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#appointmentModal">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <div class="btn-group">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="updateAppointmentBtn">
                                                        <i class="fas fa-edit"></i> Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="directUpdateAppointment(event, <?php echo $appointment['id']; ?>, 'pending')">Pending</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="directUpdateAppointment(event, <?php echo $appointment['id']; ?>, 'approved')">Approve</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="directUpdateAppointment(event, <?php echo $appointment['id']; ?>, 'rejected')">Reject</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- View Student's Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="appointmentDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Student Details
    document.querySelectorAll('.view-student').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.id;
            fetch(`get_appointment_details.php?id=${studentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('appointmentDetails').innerHTML = html;
                });
        });
    });
});
</script>

<!-- Receipt View Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0" style="overflow: auto; max-height: 75vh;">
                <img id="receiptImage" src="" alt="Receipt" class="img-fluid" style="transition: transform 0.3s ease;" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" onclick="zoomReceipt()">Zoom In</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="resetZoom()">Reset</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>

    let currentZoom = 1;

    function viewReceipt(imagePath) {
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        const img = document.getElementById('receiptImage');
        img.src = '../uploads/receipts/' + imagePath;
        currentZoom = 1;
        img.style.transform = 'scale(1)';
        img.style.cursor = 'default';
        modal.show();
    }

    function zoomReceipt() {
        currentZoom *= 1.5;
        const img = document.getElementById('receiptImage');
        img.style.transform = 'scale(' + currentZoom + ')';
        img.style.cursor = 'move';
    }

    function resetZoom() {
        currentZoom = 1;
        const img = document.getElementById('receiptImage');
        img.style.transform = 'scale(1)';
        img.style.cursor = 'default';
    }

</script>

<script>
function applyFilters() {
    const search = document.getElementById('searchAppointment').value.toLowerCase();
    const studentType = document.getElementById('studentType').value.toLowerCase();
    const status = document.getElementById('status').value.toLowerCase();
    
    document.querySelectorAll('.table tbody tr').forEach(row => {
        const searchData = (row.dataset.search || '').toLowerCase();
        const rowStudentType = (row.dataset.studentType || '').toLowerCase();
        const rowStatus = (row.dataset.status || '').toLowerCase();
        
        const searchMatch = !search || searchData.includes(search);
        const typeMatch = studentType === 'all' || rowStudentType === studentType;
        const statusMatch = status === 'all' || rowStatus === status;
        
        row.style.display = searchMatch && typeMatch && statusMatch ? '' : 'none';
    });
}

document.getElementById('searchAppointment').addEventListener('input', applyFilters);
document.getElementById('studentType').addEventListener('change', applyFilters);
document.getElementById('status').addEventListener('change', applyFilters);
</script>


<script>
// Initialize DataTables
const appointmentsTable = $('#appointmentsTable').DataTable({
    pageLength: 10,
    order: [[3, 'desc']],
    searching: false, // disables the search bar
    dom: 'frtip'  // Changed from 'Bfrtip' to 'frtip' since we don't have buttons
});

function directUpdateAppointment(event, appointmentId, status) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: `Change status to "${status}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('status', status);

            fetch('api/update_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    location.reload(); // 🔄 Force page refresh to reflect status change
                }
                else {
                    toastr.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('An error occurred while updating the appointment');
            });
        }
    });
}

</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>