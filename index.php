<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Social Media</title>
    <link rel="icon" type="image/png" href="./includes/bcp_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: url('./includes/socmed_bg.png') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            color: #ffffff; /* Pure white */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .logo i {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .logo h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .logo p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .form-control {
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            padding: 0.75rem;
        }
        .error-alert {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .form-label {
            font-weight: 500;
        }
        .register-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .invalid-feedback {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <img src="./includes/bcp_logo.png" alt="BCP Logo" width="75">
                <h2>Social Media</h2>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
            <div class="error-alert">
                Invalid email or password
            </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <form action="auth/login.php" method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="Enter your email">
                    <div class="invalid-feedback">Please enter a valid email address</div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           minlength="8" placeholder="Enter your password">
                    <div class="invalid-feedback">Password must be at least 8 characters long</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </div>
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        const emailInput = document.querySelector('#email')
        const passwordInput = document.querySelector('#password')

        emailInput.addEventListener('input', function () {
            if (this.value.trim() === '') {
                this.setCustomValidity('Email is required')
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                this.setCustomValidity('Please enter a valid email address')
            } else {
                this.setCustomValidity('')
            }
        })

        passwordInput.addEventListener('input', function () {
            if (this.value.trim() === '') {
                this.setCustomValidity('Password is required')
            } else if (this.value.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters long')
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
