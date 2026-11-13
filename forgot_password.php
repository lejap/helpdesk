<?php
// Start Session
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require 'config/db.php';
// LOAD YOUR HELPER
require 'includes/email_helper.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        // 2. Generate 6-digit OTP
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        
        // 3. Save OTP to Database
        $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $upd->bind_param("ssi", $otp, $expiry, $user['id']);
        
        if($upd->execute()) {
            
            // --- 4. SEND EMAIL USING YOUR HELPER ---
            $subject = "Password Reset Request";
            
            // This HTML body will be inserted into your template
            $email_body = "
                <div style='text-align: center;'>
                    <p style='font-size: 16px;'>You requested a password reset. Use the code below to verify your identity:</p>
                    <h1 style='background: #e9ecef; padding: 15px; display: inline-block; letter-spacing: 8px; color: #0077b6; border-radius: 8px;'>$otp</h1>
                    <p style='color: #666; font-size: 14px;'>This code is valid for 15 minutes.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999;'>If you did not request this, please ignore this email.</p>
                </div>
            ";

            // Call your function: (Email, Name, Subject, Body)
            $isSent = sendEmailNotification($email, $user['username'], $subject, $email_body);

            if ($isSent) {
                $_SESSION['reset_email'] = $email;
                header("Location: verify_otp.php");
                exit();
            } else {
                $msg = "<div class='alert alert-danger'>Failed to send email. Please try again later.</div>";
            }

        } else {
            $msg = "<div class='alert alert-danger'>Database error.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Email not found in our system.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - Bansalan Coop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/proflogo.png" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #023e8a 0%, #00b4d8 100%); /* Same Modern Blue Gradient */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .card { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.95);
        }
        .btn-primary {
            background: #0077b6; border-color: #0077b6; transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #023e8a; border-color: #023e8a; transform: translateY(-2px);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 180, 216, 0.25);
            border-color: #00b4d8;
        }
    </style>
</head>
<body>
    <div class="card p-4 mx-3" style="width: 100%; max-width: 400px;">
        
        <div class="text-center mb-4">
            <img src="assets/images/proflogo.png" alt="Logo" style="height: 60px; width: auto; margin-bottom: 20px;">
            
            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                <i class="fas fa-lock fa-3x" style="color: #0077b6;"></i>
            </div>
            
            <h4 class="fw-bold" style="color: #023e8a;">Forgot Password?</h4>
            <p class="text-muted small">Enter your registered email to receive a secure reset code.</p>
        </div>
        
        <?= $msg ?>
        
        <form method="POST">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0 py-2" placeholder="name@bansalancoop.com" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Send Reset Code</button>
        </form>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none text-muted small fw-bold hover-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>