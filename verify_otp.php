<?php
require 'config/db.php';

// FIX: Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['reset_email'])) { header("Location: forgot_password.php"); exit(); }

$msg = "";

// --- UPDATED MASKING LOGIC ---
$full_email = $_SESSION['reset_email'];
$parts = explode("@", $full_email);
$name_part = $parts[0];
$domain_part = $parts[1];

// Show the first 2 letters of the name, then masked x's
$visible_count = 2;
$visible_str = substr($name_part, 0, $visible_count);
$masked_email = $visible_str . "xxxxxx@" . $domain_part;
// -----------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = cleanInput($_POST['otp']);
    $email = $_SESSION['reset_email']; 
    
    // Check OTP and Expiry
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("ss", $email, $otp_input);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // Success
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $msg = "<div class='alert alert-danger'>Invalid or Expired OTP.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP</title>
    <link rel="icon" href="logo_ticketing.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #00b4d8;
            --dark-blue: #0077b6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #caf0f8 0%, #90e0ef 50%, #00b4d8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
        }

        .icon-container {
            width: 80px;
            height: 80px;
            background: rgba(0, 180, 216, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-container i {
            color: var(--dark-blue);
            font-size: 2.5rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.8rem;
            transition: 0.3s;
            font-weight: 700;
            color: var(--dark-blue);
        }

        .form-control:focus {
            background-color: white;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.1);
        }

        .btn-verify {
            background: var(--primary-blue);
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }

        .btn-verify:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
        }

        .letter-spacing-2 {
            letter-spacing: 0.5em; /* Spacing for OTP digits */
        }
    </style>
</head>
<body>
    <div class="card">
        
        <div class="text-center mb-3">
            <img src="bclogo.png" alt="Logo" style="max-width: 350px; height: auto;">
        </div>

        <div class="icon-container">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h3 class="text-center fw-bold" style="color: var(--dark-blue);">Verify Security Code</h3>
        <p class="text-center text-muted small mb-4">
            We sent a 6-digit code to <br> 
            <span class="fw-bold text-dark"><?= htmlspecialchars($masked_email) ?></span>
        </p>
        
        <?= $msg ?>
        
        <form method="POST">
            <div class="mb-4">
                <input type="text" name="otp" 
                       class="form-control text-center fs-3 letter-spacing-2" 
                       placeholder="######" maxlength="6" required autocomplete="off">
            </div>
            
            <button type="submit" class="btn btn-verify">Verify Code</button>
        </form>

        <div class="text-center mt-4">
            <a href="forgot_password.php" class="text-decoration-none text-muted small fw-bold">
                <i class="fas fa-arrow-left me-1"></i> Resend / Change Email
            </a>
        </div>
    </div>
</body>
</html>