<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL . FAVICON_PATH; ?>">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 1rem;
            align-self: center;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 16.666667%; /* col-lg-2 width */
            height: 100vh;
            background: #212529;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%; /* Remaining width */
            transition: all 0.3s ease;
        }
        @media (max-width: 991px) { /* lg breakpoint */
            .sidebar {
                width: 25%; /* col-md-3 width */
            }
            .main-content {
                margin-left: 25%;
                width: 75%;
            }
        }
        @media (max-width: 768px) { /* md breakpoint */
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
        }
        .sidebar .nav-link:hover {
            color: rgba(255,255,255,1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
        }
        .main-content {
            padding: 20px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background: red;
            color: white;
        }
        /* Dropdown styles */
        .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        .dropdown-menu {
            margin-top: 0.5rem !important;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item i {
            width: 1.5em;
        }
        /* Fix for dropdown in dark sidebar */
        .sidebar .dropdown-toggle {
            background: transparent !important;
            border: none;
            padding: 0.5rem 1rem;
        }
        .sidebar .dropdown-toggle:hover,
        .sidebar .dropdown-toggle:focus {
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Initialize Bootstrap components -->
    <script>
        window.addEventListener('load', function() {
            // Initialize all dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.forEach(function(dropdownToggleEl) {
                new bootstrap.Dropdown(dropdownToggleEl, {
                    offset: [0, 10],
                    popperConfig: function(defaultConfig) {
                        return {
                            ...defaultConfig,
                            strategy: 'fixed'
                        }
                    }
                });
            });

            // Add active class to current nav item
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    if (link && link.getAttribute('href') && link.getAttribute('href').includes(currentPage)) {
                        link.classList.add('active');
                    }
                });
            }
        });
    </script>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="d-flex flex-column p-3">
                    <a href="<?php 
                        if ($_SESSION['role'] === 'admin') {
                            echo BASE_URL . 'admin/dashboard.php';
                        } elseif ($_SESSION['role'] === 'studio') {
                            echo BASE_URL . 'studio/dashboard.php';
                        } else {
                            echo BASE_URL . 'student/dashboard.php';
                        }
                    ?>" class="d-flex flex-column align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none text-center">
                        <img src="<?php echo BASE_URL; ?>includes/bcp_logo.png" alt="Logo" class="logo">
                        <span class="fs-4">Graduation Pictorial</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <?php if ($_SESSION['role'] === 'student'): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>student/appointments.php" class="nav-link">
                                <i class="fas fa-calendar me-2"></i>
                                Appointments
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>student/gallery.php" class="nav-link">
                                <i class="fas fa-images me-2"></i>
                                My Gallery
                            </a>
                        </li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>admin/manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>admin/completed_sessions.php"><i class="fas fa-check-circle me-2"></i> Completed Sessions</a>
                        </li>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>admin/user_logs.php"><i class="fas fa-user-clock me-2"></i> User Logs</a>
                        </li>
                        <?php endif; ?>
                        <?php else: ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>studio/dashboard.php" class="nav-link">
                                <i class="fas fa-calendar-check me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <hr>
                    <div class="d-flex align-items-center">
                        <div class="dropdown ms-auto">
                            <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php
                                $profile_picture = BASE_URL . 'uploads/profiles/' . ($_SESSION['profile_picture'] ?? '');
                                if (empty($_SESSION['profile_picture']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url($profile_picture, PHP_URL_PATH))) {
                                    $profile_picture = BASE_URL . 'includes/default.png';
                                }
                                
                                // Get the full name
                                $full_name = $_SESSION['full_name'] ?? 'User';
                                $name_parts = explode(' ', $full_name);
                                
                                // Determine if we should split the display
                                $should_split = count($name_parts) > 2 || strlen($full_name) > 15;
                                ?>
                                <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                                     class="rounded-circle me-2" 
                                     width="32" 
                                     height="32" 
                                     style="object-fit: cover;">
                                <?php if ($should_split): 
                                    $first_parts = array_slice($name_parts, 0, -1);
                                    $last_part = end($name_parts);
                                ?>
                                <div class="d-flex flex-column" style="line-height: 1.2;">
                                    <span class="text-truncate text-start" style="max-width: 140px;"><?php echo htmlspecialchars(implode(' ', $first_parts)); ?></span>
                                    <span class="text-truncate text-start" style="max-width: 140px;"><?php echo htmlspecialchars($last_part); ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-truncate" style="max-width: 140px;"><?php echo htmlspecialchars($full_name); ?></span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">

            <!-- Edit Profile Modal -->
            <div class="modal fade" id="editProfileModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-user-edit me-2"></i>
                                Edit Profile
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editProfileForm" class="needs-validation" novalidate enctype="multipart/form-data">
                            <div class="modal-body">
                                <div id="alertPlaceholder"></div>
                                <div class="mb-3 text-center">
                                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                                         class="rounded-circle mb-3" 
                                         width="150" 
                                         height="150" 
                                         style="object-fit: cover;"
                                         id="profilePreview">
                                    <div>
                                        <label for="profile_picture" class="btn btn-outline-primary">
                                            <i class="fas fa-camera me-2"></i>Change Picture
                                        </label>
                                        <input type="file" 
                                               class="d-none" 
                                               id="profile_picture" 
                                               name="profile_picture" 
                                               accept="image/*"
                                               onchange="previewImage(this)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a username</div>
                                </div>
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your full name</div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email</div>
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
            function previewImage(input) {
                const preview = document.getElementById('profilePreview');
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            document.getElementById('editProfileForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear previous alerts
                const alertPlaceholder = document.getElementById('alertPlaceholder');
                alertPlaceholder.innerHTML = '';
                
                // Clear previous validation states
                this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                
                const formData = new FormData(this);
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                
                fetch('<?php echo BASE_URL; ?>user/edit_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Create alert
                    const alert = document.createElement('div');
                    alert.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;
                    alert.innerHTML = data.message;
                    alertPlaceholder.appendChild(alert);
                    
                    if (data.success) {
                        // Update profile image in header
                        const headerProfileImg = document.querySelector('#userMenu img');
                        if (data.data && data.data.profile_picture) {
                            headerProfileImg.src = '<?php echo BASE_URL; ?>uploads/profiles/' + data.data.profile_picture;
                        }
                        
                        // Update displayed name
                        if (data.data && data.data.full_name) {
                            const userMenuBtn = document.querySelector('#userMenu');
                            // Find the text node (last child)
                            for (let i = userMenuBtn.childNodes.length - 1; i >= 0; i--) {
                                if (userMenuBtn.childNodes[i].nodeType === 3) {
                                    userMenuBtn.childNodes[i].textContent = ' ' + data.data.full_name;
                                    break;
                                }
                            }
                        }
                        
                        // Close modal and reload after delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                            modal.hide();
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = 'An error occurred. Please try again.';
                    alertPlaceholder.appendChild(alert);
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
            </script>
