<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$pageTitle = "Reports";
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/auth_helper.php';


checkAdminAccess();

//Appointment's Tab
// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['verification_status'])) {
    $appointment_id = filter_var($_POST['appointment_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = sanitize_input($_POST['verification_status']);


    $stmt = $conn->prepare("UPDATE appointments SET verification_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    $stmt->execute();
}

// Get appointment statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'verified' => 0,
    'non-verified' => 0,
];

$result = $conn->query("SELECT verification_status, COUNT(*) as count FROM appointments GROUP BY verification_status");
while ($row = $result->fetch_assoc()) {
    $stats[$row['verification_status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get filters
$verification_status = $_GET['verification_status'] ?? 'all';
$searchStudent = $_GET['searchStudent'] ?? '';

// Build query
$query = "
    SELECT a.*, 
           s.first_name,
           s.middle_name,
           s.last_name,
           s.student_id,
           a.receipt,
           COALESCE(ss.name, 'N/A') AS program_name
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN student_academic_info sai ON s.id = sai.student_id
    LEFT JOIN shs_strands ss ON sai.shs_strand_id = ss.id
    WHERE a.status != 'cancelled'
";

// Prepare dynamic filters
$params = [];
$types = "";

// Add verification status filter
if ($verification_status !== 'all') {
    $query .= " AND a.verification_status = ?";
    $params[] = $verification_status;
    $types .= "s";
}

// Add student search filter
if (!empty($searchStudent)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $searchWildcard = "%$searchStudent%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "sss";
}

// Final ordering
$query .= " ORDER BY a.preferred_date DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

// Fetch result
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



//Receipt's Tab
// Build query
$stmt = $conn->prepare ("
    SELECT s.*, 
           s.student_id as student_no,
           CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(LEFT(s.middle_name, 1), '')) as full_name,
           s.contact_number,
           s.receipts
    FROM students s
    WHERE 1=1
");

$stmt->execute();
$receipts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get student receipt statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN receipts IS NOT NULL THEN 1 ELSE 0 END) as with_receipts,
        SUM(CASE WHEN receipts IS NULL THEN 1 ELSE 0 END) as without_receipts
    FROM students");
$stmt->execute();
$receipt_stats = $stmt->get_result()->fetch_assoc();
?>

<style>
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }
    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .status-badge.verified {
        background-color: #d1e7dd;
        color: #146c43;
    }
    .status-badge.non-verified {
        background-color: #f8d7da;
        color: #b02a37;
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
    .stats-card.verified { background-image: url('../includes/card-success.png'); }
    .stats-card.non-verified { background-image: url('../includes/card-danger.png'); }
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-chart-line me-2"></i> Reports and Analytics</h2>
            <p class="text-muted">View appointments and payment records</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <?php 
        $icons = [
            'total' => 'fa-calendar',
            'pending' => 'fa-clock',
            'verified' => 'fa-check-circle',
            'non-verified' => 'fa-times-circle'
        ];
        foreach ($stats as $stat_verification_status => $count): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card stats-card shadow-sm text-white h-100 <?php echo $stat_verification_status; ?>">
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <i class="fas <?php echo $icons[$stat_verification_status]; ?> mb-2"></i>
                        <h3 class="mb-2"><?php echo $count; ?></h3>
                        <p class="mb-0"><?php echo ucfirst($stat_verification_status); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">Appointments</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receipts" type="button" role="tab">Receipt Records</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- Appointments Tab -->
        <div class="tab-pane fade show active" id="appointments" role="tabpanel">
            <div class="card">

            <!-- Search -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchStudent" value="<?php echo htmlspecialchars($searchStudent); ?>" placeholder="Search by name or student number">
                                <button class="btn btn-outline-primary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?php echo $verification_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $verification_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="verified" <?php echo $verification_status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                <option value="non-verified" <?php echo $verification_status === 'non-verified' ? 'selected' : ''; ?>>Non-Verified</option>
                            </select>
                        </div>    
                    </div>
                </div>

            <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="appointmentsTable">
                            <thead>
                                <tr>
                                    <th>Student No.</th>
                                    <th>Full Name</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr data-search="<?php echo strtolower($appointment['student_id'] . ' ' . $appointment['last_name'] . ' ' . $appointment['first_name'] . ' ' . $appointment['middle_name']); ?>" 
                                    data-status="<?php echo $appointment['verification_status']; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($appointment['student_id']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['last_name'] . ', ' . $appointment['first_name'] . ' ' . $appointment['middle_name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $appointment['verification_status']; ?>">
                                            <?php echo ucfirst($appointment['verification_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                     <?php if ($appointment['receipt']): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewReceipt('<?= htmlspecialchars($appointment['receipt']) ?>')"><i class="fas fa-eye"></i> View</button>
                                         <?php else: ?>
                                            <span class="text-muted">No receipt</span>
                                         <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($appointment['verification_status'] !== 'verified'): ?>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-success" onclick="updateVerificationStatus(<?= $appointment['id'] ?>, 'verified')">
                                                    <i class="fas fa-check"></i> Verify
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="updateVerificationStatus(<?= $appointment['id'] ?>, 'non-verified')">
                                                    <i class="fas fa-times"></i> Non-verify
                                                </button>
                                            </div>
                                         <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Tab -->
        <div class="tab-pane fade" id="receipts" role="tabpanel">
            <div class="card">

                <!-- Search -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchReceipts" placeholder="Search by name or student number">
                                <button class="btn btn-outline-primary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="receiptsTable">
                            <thead>
                                <tr>
                                    <th>Student No.</th>
                                    <th>Full Name</th>
                                    <th>Contact Number</th>
                                    <th>Receipt</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receipts as $receipt): ?>
                                    <tr data-search="<?php echo strtolower($receipt['student_id'] . ' ' . $receipt['last_name'] . ' ' . $receipt['first_name'] . ' ' . $receipt['middle_name']); ?>" 
                                        data-student-type="<?php echo $receipt['student_type']; ?>" 
                                        data-status="<?php echo $receipt['status']; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($receipt['student_id']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($receipt['last_name'] . ', ' . $receipt['first_name'] . ' ' . $receipt['middle_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($receipt['contact_number']); ?>
                                        </td>
                                        <td>
                                            <?php if ($receipt['receipts']): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Uploaded</span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="fas fa-times-circle"></i> Not Uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($receipt['receipts']): ?>
                                                <button class="btn btn-sm btn-primary" onclick="viewReceipt('<?= htmlspecialchars($receipt['receipts']) ?>')"><i class="fas fa-eye"></i> View</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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


    $(document).ready(function () {
        // Initialize Appointments Table
        var appointmentsTable = $('#appointmentsTable').DataTable({
            pageLength: 10,
            order: [[1, 'asc']],  // Ordering by full name
            dom: 't<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        // Initialize Receipts Table
        var receiptsTable = $('#receiptsTable').DataTable({
            pageLength: 10,
            order: [[1, 'asc']],  // Ordering by full name
            dom: 't<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        // Confirm initialization
        console.log("Appointments table initialized:", appointmentsTable);
        console.log("Receipts table initialized:", receiptsTable);

        // Handle search for appointments table
        $('#searchStudent').on('keyup', function () {
            let val = $(this).val();
            console.log("Searching appointments for:", val);
            appointmentsTable.search(val).draw();
        });

        // Handle search for receipts table
        $('#searchReceipts').on('keyup', function () {
            let val = $(this).val();
            console.log("Searching receipts for:", val);
            receiptsTable.search(val).draw();
        });

        // Handle status filter for appointments
        $('#status').on('change', function () {
            const status = $(this).val();
            appointmentsTable
                .column(2) // Ensure this is the correct column index for "Status"
                .search(status === 'all' ? '' : status)
                .draw();
        });
    });

    function applyStudentFilters() {
        const keyword = $('#searchStudent').val();
        $('#appointmentsTable').DataTable().search(keyword).draw();
    }

    function viewReceipt(imagePath) {
        document.getElementById('receiptImage').src = '../uploads/receipts/' + imagePath;
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    }

    function updateVerificationStatus(appointmentId, status) {
        if (confirm('Are you sure you want to mark this as ' + status + '?')) {
            fetch('api/update_verification_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: appointmentId,
                    verification_status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status');
            });
        }
    }

</script>