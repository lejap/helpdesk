<?php
require 'config/db.php';

// Fetch Branches
$branches = $conn->query("SELECT * FROM branch ORDER BY name ASC");

// Initialize variables
$username = $email = $phone = $branch_id = "";
$usernameErr = $emailErr = $phoneErr = $passErr = $branchErr = "";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capture User Input
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']); 
    $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : "";
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $isValid = true;

    // 2. Validate Branch
    if (empty($branch_id)) {
        $branchErr = "Please select a branch.";
        $isValid = false;
    }

    // 3. Validate Phone
    if (empty($phone)) {
        $phoneErr = "Phone number is required.";
        $isValid = false;
    }

    // 4. Validate Password Complexity
    if (empty($password)) {
        $passErr = "Password is required.";
        $isValid = false;
    } elseif ($password !== $confirm_password) {
        $passErr = "Passwords do not match.";
        $isValid = false;
    } else {
        // Enforce: 6+ chars, 1 Uppercase, 1 Number, 1 Special Char
        if (strlen($password) < 6 || 
            !preg_match("/[A-Z]/", $password) || 
            !preg_match("/[0-9]/", $password) || 
            !preg_match("/[\W_]/", $password)) {
            
            $passErr = "Password must be 6+ chars, with 1 uppercase, 1 number, and 1 special char.";
            $isValid = false;
        }
    }

    // 5. Validate Email
    if (empty($email)) {
        $emailErr = "Email is required.";
        $isValid = false;
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $emailErr = "This email is already registered.";
            $isValid = false;
        }
    }

    // 6. Insert into DB
    if ($isValid) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, branch_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $username, $email, $phone, $hashed_password, $branch_id);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>Registration successful! Please wait for Admin approval. <br><a href='index.php' class='fw-bold'>Login here</a></div>";
            $username = $email = $phone = $branch_id = ""; 
        } else {
            $msg = "<div class='alert alert-danger'>Database Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - Bansalan Coop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/images/proflogo.png" type="image/png">

    <style>
        :root {
            --primary-blue: #00b4d8;
            --light-blue: #caf0f8;
            --dark-blue: #0077b6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #caf0f8 0%, #90e0ef 50%, #00b4d8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-in-out;
        }

        .brand-logo { height: 50px; width: auto; margin-right: 10px; }
        .register-title { color: var(--dark-blue); font-weight: 700; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; font-size: 1.5rem; }

        .form-control, .form-select {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            background-color: white;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.1);
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }
        
        .invalid-feedback {
            display: block;
            font-size: 0.85em;
            margin-top: 5px;
            font-weight: 500;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
        }
        
        .is-invalid + .input-group-text { border-color: #dc3545; }
        .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }

        .btn-custom {
            background: var(--primary-blue);
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-custom:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
        }

        /* Password Checklist Styles */
        #password-checklist {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-top: 5px;
            display: none; 
        }
        
        .checklist-item {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
            transition: color 0.3s ease;
        }

        .checklist-item.valid { color: #198754; font-weight: 600; }
        .checklist-item.invalid { color: #dc3545; }
        
        .checklist-item i { width: 15px; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile Adjustments */
        @media (max-width: 576px) {
            .register-card { padding: 1.5rem; }
            .brand-logo { height: 40px; }
            .register-title { font-size: 1.3rem; flex-direction: column; text-align: center; }
            .register-title img { margin-bottom: 10px; margin-right: 0; }
        }
    </style>
</head>
<body>
    <div class="register-card">
        
        <h3 class="register-title">
            <img src="assets/images/proflogo.png" alt="Logo" class="brand-logo">
            Create Account
        </h3>
        
        <p class="text-center text-muted mb-4">Join the IT Support Portal</p>

        <?= $msg ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">FULL NAME</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required placeholder="e.g. Juan dela Cruz">
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control <?= $emailErr ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($email) ?>" required placeholder="name@company.com">
                <?php if($emailErr): ?>
                    <div class="invalid-feedback"><?= $emailErr ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">PHONE NUMBER</label>
                <input type="text" name="phone" class="form-control <?= $phoneErr ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($phone) ?>" required placeholder="e.g. 09123456789">
                <?php if($phoneErr): ?>
                    <div class="invalid-feedback"><?= $phoneErr ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">SELECT BRANCH</label>
                <select name="branch_id" class="form-select <?= $branchErr ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Choose your Branch --</option>
                    <?php 
                    if ($branches && $branches->num_rows > 0) {
                        $branches->data_seek(0); 
                        while($row = $branches->fetch_assoc()): 
                            $selected = ($branch_id == $row['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $row['id'] ?>" <?= $selected ?>><?= htmlspecialchars($row['name']) ?></option>
                    <?php 
                        endwhile; 
                    } 
                    ?>
                </select>
                <?php if($branchErr): ?>
                    <div class="invalid-feedback"><?= $branchErr ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control <?= $passErr ? 'is-invalid' : '' ?>" required placeholder="Create a strong password">
                    <span class="input-group-text" onclick="togglePassword('password', 'icon1')">
                        <i class="fa fa-eye text-muted" id="icon1"></i>
                    </span>
                </div>
                
                <div id="password-checklist">
                    <div class="checklist-item" id="req-length"><i class="fas fa-times"></i> At least 6 characters</div>
                    <div class="checklist-item" id="req-upper"><i class="fas fa-times"></i> At least 1 Uppercase Letter</div>
                    <div class="checklist-item" id="req-number"><i class="fas fa-times"></i> At least 1 Number</div>
                    <div class="checklist-item" id="req-special"><i class="fas fa-times"></i> At least 1 Special Character</div>
                </div>

                <?php if($passErr): ?>
                    <div class="invalid-feedback"><?= $passErr ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">CONFIRM PASSWORD</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control <?= $passErr ? 'is-invalid' : '' ?>" required placeholder="Retype password">
                    <span class="input-group-text" onclick="togglePassword('confirm_password', 'icon2')">
                        <i class="fa fa-eye text-muted" id="icon2"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-custom w-100">Register</button>
        </form>
        
        <div class="text-center mt-4">
            <span class="text-muted">Already have an account?</span>
            <a href="index.php" class="text-decoration-none fw-bold" style="color: var(--primary-blue);">Login here</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // --- PASSWORD CHECKLIST LOGIC ---
        const passwordInput = document.getElementById('password');
        const checklist = document.getElementById('password-checklist');
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');

        // Show checklist on focus
        passwordInput.addEventListener('focus', function() {
            checklist.style.display = 'block';
        });

        // Hide checklist on blur (optional)
        passwordInput.addEventListener('blur', function() {
            // checklist.style.display = 'none'; 
        });

        // Validate on typing
        passwordInput.addEventListener('keyup', function() {
            const val = passwordInput.value;

            // 1. Check Length (>= 6)
            if (val.length >= 6) { setValid(reqLength); } else { setInvalid(reqLength); }

            // 2. Check Uppercase
            if (/[A-Z]/.test(val)) { setValid(reqUpper); } else { setInvalid(reqUpper); }

            // 3. Check Number
            if (/[0-9]/.test(val)) { setValid(reqNumber); } else { setInvalid(reqNumber); }

            // 4. Check Special Character (Symbol)
            if (/[\W_]/.test(val)) { setValid(reqSpecial); } else { setInvalid(reqSpecial); }
        });

        function setValid(element) {
            element.classList.add('valid');
            element.classList.remove('invalid');
            element.querySelector('i').classList.remove('fa-times');
            element.querySelector('i').classList.add('fa-check');
        }

        function setInvalid(element) {
            element.classList.add('invalid');
            element.classList.remove('valid');
            element.querySelector('i').classList.remove('fa-check');
            element.querySelector('i').classList.add('fa-times');
        }
    </script>
</body>
</html>