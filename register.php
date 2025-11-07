<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Graduation Pictorial System</title>
    <link rel="icon" type="image/png" href="./includes/bcp_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('./includes/socmed_bg_clean.png') no-repeat center center/cover;
        }
        .register-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            color: #ffffff; /* Pure white */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="logo">
                <img src="./includes/bcp_logo.png" alt="BCP Logo" width="75">
                <h2>Student Registration</h2>
                <p class="text-white">Create your account</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <form action="auth/register.php" method="POST" class="row g-3 needs-validation" novalidate>
                <!-- Personal Information -->
                <div class="col-md-4">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                    <div class="invalid-feedback">Please enter your first name</div>
                </div>
                <div class="col-md-4">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                </div>
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                    <div class="invalid-feedback">Please enter your last name</div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">Please enter a valid email</div>
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                    <div class="invalid-feedback">Please enter your contact number</div>
                </div>

                <div class="col-md-6">
                    <label for="birthday" class="form-label">Birthday</label>
                    <input type="date" class="form-control" id="birthday" name="birthday" required>
                    <div class="invalid-feedback">Please select your birthday</div>
                </div>
                <div class="col-md-6">
                    <label for="civil_status" class="form-label">Civil Status</label>
                    <select class="form-select" id="civil_status" name="civil_status" required>
                        <option value="">Select Civil Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                        <option value="separated">Separated</option>
                    </select>
                    <div class="invalid-feedback">Please select your civil status</div>
                </div>

                <!-- Academic Information -->
                <div class="col-12">
                    <hr>
                    <h5>Academic Information</h5>
                </div>

                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" required>
                    <div class="invalid-feedback">Please enter your student ID</div>
                </div>

                <div class="col-md-6">
                    <label for="student_type" class="form-label">Student Type</label>
                    <select class="form-select" id="student_type" name="student_type" required>
                        <option value="">Select Student Type</option>
                        <option value="regular_college">Regular College</option>
                        <option value="octoberian">Octoberian</option>
                        <option value="senior_high">Senior High</option>
                    </select>
                    <div class="invalid-feedback">Please select your student type</div>
                </div>

                <div class="col-md-6">
                    <label for="course_strand" class="form-label">Course/Strand</label>
                    <select class="form-select" id="course_strand" name="course_strand" required disabled>
                        <option value="">Select Student Type First</option>
                    </select>
                    <div class="invalid-feedback">Please select your course/strand</div>
                </div>

                <div class="col-md-6">
                    <label for="section" class="form-label">Section</label>
                    <input type="text" class="form-control" id="section" name="section" required>
                    <div class="invalid-feedback">Please enter your section</div>
                </div>

                <div class="col-md-6">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="2nd">2nd Semester</option>
                    </select>
                    <div class="invalid-feedback">Please select a semester</div>
                </div>

                <!-- Account Security -->
                <div class="col-12">
                    <hr>
                    <h5>Account Security</h5>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Please enter a password</div>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="invalid-feedback">Please confirm your password</div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </div>
                <div class="col-12 text-center">
                    <p>Already have an account? <a href="index.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            const studentTypeSelect = document.getElementById('student_type');
            const courseStrandSelect = document.getElementById('course_strand');
            const semesterSelect = document.getElementById('semester');

            // Form validation
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Handle student type change
            studentTypeSelect.addEventListener('change', function() {
                courseStrandSelect.disabled = false;
                courseStrandSelect.innerHTML = '<option value="">Loading...</option>';
                
                let endpoint;
                if (this.value === 'senior_high') {
                    semesterSelect.disabled = true;
                    semesterSelect.value = '';
                    semesterSelect.required = false;
                    endpoint = 'api/get_shs_strands.php';
                } else if (this.value === 'regular_college') {
                    semesterSelect.disabled = false;
                    semesterSelect.required = true;
                    endpoint = 'api/get_college_courses.php';
                } else if (this.value === 'octoberian') {
                    semesterSelect.disabled = false;
                    semesterSelect.required = true;
                    endpoint = 'api/get_octoberian_courses.php';
                }

                // Fetch courses/strands
                fetch(endpoint)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            courseStrandSelect.innerHTML = '<option value="">Select Course/Strand</option>';
                            result.data.forEach(item => {
                                courseStrandSelect.innerHTML += `<option value="${item.id}">${item.code} - ${item.name}</option>`;
                            });
                            courseStrandSelect.disabled = false;
                        } else {
                            throw new Error(result.message || 'Failed to load options');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        courseStrandSelect.innerHTML = '<option value="">Error loading options</option>';
                        courseStrandSelect.disabled = true;
                    });
            });

            // Log form data before submit
            form.addEventListener('submit', function(event) {
                const formData = new FormData(form);
                console.log('Form data before submit:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
            });
        });
    </script>
</body>
</html>
