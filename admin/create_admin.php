<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if there's already an admin user
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    die('Admin account already exists. Registration is disabled.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $username = sanitize_input($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validation checks
        if (!$username || !$email || !$password) {
            throw new Exception('All fields are required');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        if (!preg_match('/^[A-Za-z0-9_]{3,}$/', $username)) {
            throw new Exception('Username must contain only letters, numbers, and underscores');
        }

        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Username already exists');
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new admin user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role, created_at)
            VALUES (?, ?, ?, 'admin', NOW())
        ");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        $stmt->execute();

        // Redirect to login page with success message
        header('Location: ../index.php?message=' . urlencode('Admin registration successful. Please login.'));
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Admin Registration</h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required 
                                       pattern="[A-Za-z0-9_]{3,}" placeholder="Enter username">
                                <div class="invalid-feedback">Username must be at least 3 characters (letters, numbers, underscore only)</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="Enter email address">
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required 
                                       minlength="8" placeholder="Enter password">
                                <div class="invalid-feedback">Password must be at least 8 characters long</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                       placeholder="Confirm your password">
                                <div class="invalid-feedback">Passwords do not match</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register Admin</button>
                                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        const username = document.querySelector('#username')
        const email = document.querySelector('#email')
        const password = document.querySelector('#password')
        const confirmPassword = document.querySelector('#confirm_password')

        username.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.setCustomValidity('Username is required')
            } else if (!this.value.match(/^[A-Za-z0-9_]{3,}$/)) {
                this.setCustomValidity('Username must be at least 3 characters (letters, numbers, underscore only)')
            } else {
                this.setCustomValidity('')
            }
        })

        email.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.setCustomValidity('Email is required')
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                this.setCustomValidity('Please enter a valid email address')
            } else {
                this.setCustomValidity('')
            }
        })

        password.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.setCustomValidity('Password is required')
            } else if (this.value.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters long')
            } else {
                this.setCustomValidity('')
            }
            // Check confirm password match
            if (confirmPassword.value !== this.value) {
                confirmPassword.setCustomValidity('Passwords do not match')
            } else {
                confirmPassword.setCustomValidity('')
            }
        })

        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Passwords do not match')
            } else {
                this.setCustomValidity('')
            }
        })

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>
