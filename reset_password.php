<?php
require 'config/db.php';

// FIX: Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) { 
    header("Location: index.php"); exit(); 
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // Server-side validation
    if ($pass !== $confirm) {
        $msg = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (strlen($pass) < 6 || !preg_match("/[A-Z]/", $pass) || !preg_match("/[0-9]/", $pass) || !preg_match("/[\W_]/", $pass)) {
        $msg = "<div class='alert alert-danger'>Password does not meet requirements.</div>";
    } else {
        // Update Password
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        
        if ($stmt->execute()) {
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            echo "<script>alert('Password updated successfully! Login now.'); window.location.href='index.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <link rel="icon" href="logo_ticketing.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background: linear-gradient(135deg, #caf0f8 0%, #90e0ef 50%, #00b4d8 100%); height: 100vh; }
        
        /* Checklist Styles */
        #password-checklist {
            display: none; 
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .checklist-item { font-size: 0.8rem; color: #dc3545; margin-bottom: 2px; transition: color 0.3s; }
        .checklist-item.valid { color: #198754; font-weight: 600; }
        .checklist-item i { width: 15px; }
        
        .input-group-text { cursor: pointer; background: white; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="card shadow-lg p-4" style="width: 450px; border-radius: 15px;">
        <h4 class="text-center fw-bold mb-3" style="color: #0077b6;">Set New Password</h4>
        <?= $msg ?>
        
        <form method="POST">
            <div class="mb-2">
                <label class="form-label small fw-bold text-muted">New Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter new password">
                    <span class="input-group-text" onclick="togglePassword('password', 'icon1')">
                        <i class="fas fa-eye text-muted" id="icon1"></i>
                    </span>
                </div>
            </div>

            <div id="password-checklist">
                <div class="checklist-item" id="req-len"><i class="fas fa-times"></i> At least 6 characters</div>
                <div class="checklist-item" id="req-up"><i class="fas fa-times"></i> At least 1 Uppercase Letter</div>
                <div class="checklist-item" id="req-num"><i class="fas fa-times"></i> At least 1 Number</div>
                <div class="checklist-item" id="req-spec"><i class="fas fa-times"></i> At least 1 Special Character</div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Confirm Password</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Retype password">
                    <span class="input-group-text" onclick="togglePassword('confirm_password', 'icon2')">
                        <i class="fas fa-eye text-muted" id="icon2"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold">Reset Password</button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none text-muted small fw-bold">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === "password") {
                field.type = "text";
                icon.className = "fas fa-eye-slash text-muted";
            } else {
                field.type = "password";
                icon.className = "fas fa-eye text-muted";
            }
        }

        // Checklist Logic
        const passInput = document.getElementById('password');
        const checklist = document.getElementById('password-checklist');

        passInput.addEventListener('focus', () => checklist.style.display = 'block');
        
        passInput.addEventListener('keyup', function() {
            const v = this.value;
            updateCheck('req-len', v.length >= 6);
            updateCheck('req-up', /[A-Z]/.test(v));
            updateCheck('req-num', /[0-9]/.test(v));
            updateCheck('req-spec', /[\W_]/.test(v));
        });

        function updateCheck(id, isValid) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i');
            if (isValid) {
                el.classList.add('valid');
                icon.className = 'fas fa-check';
            } else {
                el.classList.remove('valid');
                icon.className = 'fas fa-times';
            }
        }
    </script>
</body>
</html>