<?php
$pageTitle = "Manage Users";
require_once '../includes/header.php';
require_once '../config/database.php';

// Get all users except current admin and original admin (id=1)
$stmt = $conn->prepare("
    SELECT u.* 
    FROM users u
    WHERE u.id != ? 
    AND u.role != 'user'
    AND u.id != 1 -- Always hide original admin (id=1)
    ORDER BY u.created_at DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<div class="container py-4">
<div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-users me-2"></i> User Management</h2>
            <p class="text-muted">Manage registered users</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchUser" placeholder="Search by name or email">
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
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-role="<?php echo $user['role']; ?>"
                                data-search="<?php echo strtolower($user['full_name'] . ' ' . $user['email']); ?>">
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'studio' ? 'info' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($_SESSION['user_id'] === 1 || $user['role'] !== 'admin'): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" 
                                            onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                        <i class="fas <?php echo $user['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm" onsubmit="return handleUserSubmit(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="studio">Studio Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" onsubmit="return handleUserSubmit(event, true)">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="studio">Studio Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.getElementById('searchUser').value.toLowerCase();
    const role = document.getElementById('roleFilter').value.toLowerCase();
    
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        const searchText = row.dataset.search;
        const userRole = row.dataset.role;
        
        const searchMatch = !search || searchText.includes(search);
        const roleMatch = !role || userRole === role;
        
        row.style.display = searchMatch && roleMatch ? '' : 'none';
    });
}

function toggleUserStatus(userId, currentStatus) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to ${currentStatus === 'active' ? 'deactivate' : 'activate'} this user.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then((result) => {
        if (!result.isConfirmed) return;

        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        fetch('process_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_status',
                user_id: userId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'Error updating user status', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Error updating user status', 'error');
        });
    });
}


function handleUserSubmit(event, isEdit = false) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {};
    formData.forEach((value, key) => {
        if (value.trim() !== '' || key !== 'password') {
            data[key] = value;
        }
    });
    
    fetch('process_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: isEdit ? 'edit' : 'add',
            ...data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
    
    return false;
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value = user.role;
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function deleteUser(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('process_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
}

document.getElementById('roleFilter').addEventListener('change', applyFilters);
document.getElementById('searchUser').addEventListener('input', applyFilters);
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>