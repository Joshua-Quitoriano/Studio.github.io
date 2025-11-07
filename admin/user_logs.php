<?php
$pageTitle = "User Logs";
require_once '../includes/header.php';
require_once '../config/database.php';

// Only allow access to main admin (id=1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    // Not the main admin, redirect to dashboard
    header('Location: dashboard.php');
    exit;
}

// Get user logs for today by default
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $conn->prepare("
    SELECT l.*, u.username as name, u.email, u.role 
    FROM user_login_summary l
    JOIN users u ON l.user_id = u.id
    WHERE u.id != 1 -- Exclude main admin
    AND l.login_date = ?
    ORDER BY l.first_login DESC
");

$stmt->bind_param("s", $date_filter);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-clock me-2"></i> User Logs</h2>
            <p class="text-muted">Track user login activities</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchLog" placeholder="Search by name or email">
                        <button class="btn btn-outline-primary" type="button" onclick="applyFilters()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Admins</option>
                        <option value="studio">Studio Staff</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateFilter" value="<?= $date_filter ?>" 
                           onchange="window.location.href='?date=' + this.value">
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="logsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>First Login</th>
                            <th>Last Logout</th>
                            <th>Login Count</th>
                            <th>IP Address</th>
                            <th>Device Info</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['name']) ?></td>
                                <td><?= htmlspecialchars($log['email']) ?></td>
                                <td>
                                    <span class="badge <?= $log['role'] === 'admin' ? 'bg-danger' : 'bg-info' ?>">
                                        <?= ucfirst($log['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('h:i A', strtotime($log['first_login'])) ?></td>
                                <td><?= $log['last_logout'] ? date('h:i A', strtotime($log['last_logout'])) : 'Active' ?></td>
                                <td><?= $log['login_count'] ?></td>
                                <td>
                                    <?php 
                                    $ip = $log['last_ip_address'];
                                    if ($ip === '::1' || $ip === '127.0.0.1') {
                                        echo '<span title="Local Computer">localhost</span>';
                                    } else {
                                        echo htmlspecialchars($ip);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    require_once '../includes/tracking.php';
                                    $device_info = getDeviceInfo($log['last_user_agent']);
                                    $icon = '';
                                    switch($device_info['device']) {
                                        case 'Mobile':
                                            $icon = '<i class="fas fa-mobile-alt"></i> ';
                                            break;
                                        case 'Tablet':
                                            $icon = '<i class="fas fa-tablet-alt"></i> ';
                                            break;
                                        default:
                                            $icon = '<i class="fas fa-desktop"></i> ';
                                    }
                                    echo "<span title='".htmlspecialchars($log['last_user_agent'])."'>";
                                    echo $icon . htmlspecialchars($device_info['device']) . ' - ' . 
                                         htmlspecialchars($device_info['os']) . ' - ' . 
                                         htmlspecialchars($device_info['browser']);
                                    echo "</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php if (!$log['last_logout']): ?>
                                        <span class="badge bg-success">Online</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Offline</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">No login records found for this date</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.getElementById('searchLog').value.toLowerCase();
    const role = document.getElementById('roleFilter').value.toLowerCase();
    
    document.querySelectorAll('#logsTable tbody tr').forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        const userRole = row.cells[2].textContent.toLowerCase();
        
        const matchesSearch = search === '' || name.includes(search) || email.includes(search);
        const matchesRole = role === '' || userRole.includes(role);
        
        row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });
}

// Initialize filters
document.getElementById('searchLog').addEventListener('keyup', applyFilters);
document.getElementById('roleFilter').addEventListener('change', applyFilters);
</script>