<?php
session_start();
$pageTitle = "Student Management";
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

checkAdminAccess();

// Handle student status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['action'])) {
    $student_id = $_POST['student_id'];

    if ($_POST['action'] === 'activate') {
        $stmt = $conn->prepare("UPDATE students SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'deactivate') {
        $stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
    }
}

// Initialize filter variables
$student_type = 'all';
$program = 'all';
$search = '';

// Get all students
$query = "SELECT s.*, COALESCE(cc.name, ss.name, 'N/A') AS program_name
         FROM students s
         LEFT JOIN student_academic_info sai ON s.id = sai.student_id
         LEFT JOIN college_courses cc ON sai.college_course_id = cc.id
         LEFT JOIN shs_strands ss ON sai.shs_strand_id = ss.id";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all available programs for filter
$programs_query = "
    SELECT name FROM college_courses 
    UNION 
    SELECT name FROM shs_strands 
    ORDER BY name
";
$programs = $conn->query($programs_query)->fetch_all(MYSQLI_ASSOC);
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
    .status-badge.active {
        background-color: #d1e7dd;
        color: #146c43;
    }
    .status-badge.inactive {
        background-color: #f8d7da;
        color: #b02a37;
    }
    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .appointment-stats {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .appointment-stats .card {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .appointment-stats .card:hover {
        transform: translateY(-5px);
    }
    .appointment-stats .card.bg-primary {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
    }
    .appointment-stats .card.bg-info {
        background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%) !important;
    }
    .appointment-stats .card.bg-success {
        background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
    }
    .appointment-stats .card h6 {
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0;
    }
    .appointment-stats .card h3 {
        font-size: 1.75rem;
        font-weight: 600;
    }
    .modal-body {
        max-height: 75vh;
        overflow-y: auto;
    }
    #viewStudentModal .modal-content {
        height: 85vh;
    }
    #viewStudentModal .modal-body {
        height: calc(100% - 56px); /* Subtract modal header height */
    }
</style>

<div class="container py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-graduate me-2"></i> Student Management</h2>
            <p class="text-muted">Manage registered students</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchStudent" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or student number">
                        <button class="btn btn-outline-primary" type="button" onclick="applyFilters()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-3">
                    <select class="form-select" id="studentType" onchange="applyFilters()">
                        <option value="all" <?php echo $student_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="regular_college" <?php echo $student_type === 'regular_college' ? 'selected' : ''; ?>>Regular College</option>
                        <option value="octoberian" <?php echo $student_type === 'octoberian' ? 'selected' : ''; ?>>Octoberian</option>
                        <option value="senior_high" <?php echo $student_type === 'senior_high' ? 'selected' : ''; ?>>Senior High School</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="program" onchange="applyFilters()">
                        <option value="all" <?php echo $program === 'all' ? 'selected' : ''; ?>>All Programs</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['name']); ?>" <?php echo $program === $p['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>  


                <!-- Students Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Student No.</th>
                                <th>Program/Course</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr data-search="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['student_id']); ?>" 
                                    data-student-type="<?php echo $student['student_type']; ?>" 
                                    data-program="<?php echo $student['program_name']; ?>">
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td>
                                        <span class="program-badge">
                                            <?php echo htmlspecialchars($student['program_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $student['status']; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-info btn-sm view-student me-1"
                                                    data-id="<?php echo $student['id']; ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewStudentModal">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <form method="post" class="d-inline status-form" data-student-id="<?php echo $student['id']; ?>" data-action="<?php echo $student['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo $student['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                                <button type="submit" class="btn btn-<?php echo $student['status'] === 'active' ? 'danger' : 'success'; ?> btn-sm">
                                                    <i class="fas <?php echo $student['status'] === 'active' ? 'fa-user-times' : 'fa-user-check'; ?>"></i>
                                                </button>
                                            </form>
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

<script>
$(document).ready(function() {
    // Initialize DataTable with custom filtering
    const studentsTable = $('#studentsTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        dom: 't<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ students',
            infoEmpty: 'No students found',
            infoFiltered: '(filtered from _MAX_ total students)'
        }
    });

    // Custom search function
    function applyFilters() {
        const searchVal = $('#searchStudent').val().toLowerCase();
        const studentType = $('#studentType').val().toLowerCase();
        const program = $('#program').val().toLowerCase();

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const row = $(studentsTable.row(dataIndex).node());
            const rowStudentType = row.data('student-type').toLowerCase();
            const rowProgram = row.data('program').toLowerCase();

            const typeMatch = studentType === 'all' || rowStudentType === studentType;
            const programMatch = program === 'all' || rowProgram === program;

            return typeMatch && programMatch;
        });

        studentsTable.search(searchVal).draw();
        $.fn.dataTable.ext.search.pop(); // Remove the custom filter after drawing
    }

    // Handle all filter changes
    $('#searchStudent').on('keyup', applyFilters);
    $('#studentType').on('change', applyFilters);
    $('#program').on('change', applyFilters);
});
</script>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetails">
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
            fetch(`get_student_details.php?id=${studentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('studentDetails').innerHTML = html;
                });
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.querySelectorAll('.status-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default submit

        const action = form.dataset.action;
        const message = `Are you sure you want to ${action} this student?`;

        Swal.fire({
            title: 'Confirm Action',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Submit after confirmation
            }
        });
    });
});
</script>
