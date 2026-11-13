<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config/db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['is_active'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Log the login event
                $log_msg = "User logged in successfully.";
                $conn->query("INSERT INTO ticket_logs (user_id, ticket_id, action_type, message) VALUES ({$user['id']}, NULL, 'login', '$log_msg')");

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Account is pending admin approval.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bansalan Coop Helpdesk - Login</title>
    <link rel="icon" href="assets/images/proflogo.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #00a8cc; 
            --brand-dark: #007ea7;
            --text-dark: #2b2d42;
            --text-muted: #6c757d;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('bcbldg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
        }

        /* Dark Overlay with Blur */
        body::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); 
            z-index: -1;
            backdrop-filter: blur(8px); 
        }

        /* --- NEW: Floating Back to Portal Button --- */
        .back-to-portal-btn {
            position: absolute;
            top: 25px;
            left: 30px;
            z-index: 1050;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 10px 22px;
            border-radius: 50px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-portal-btn:hover {
            background: var(--brand-primary);
            color: white;
            border-color: var(--brand-primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 168, 204, 0.35);
        }

        @media (max-width: 768px) {
            .back-to-portal-btn {
                top: 15px;
                left: 15px;
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
        /* ------------------------------------------ */

        /* --- MAIN GLASS CARD --- */
        .login-wrapper {
            display: flex;
            width: 1000px;
            max-width: 95%;
            min-height: 580px;
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2); 
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            animation: fadeIn 0.8s ease-out;
            margin-top: 40px; /* Slight margin so it doesn't clip the new button on small screens */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- LEFT SIDE (Brand) --- */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(0, 153, 204, 0.9) 0%, rgba(0, 198, 255, 0.9) 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            position: relative; z-index: 2;
        }

        .brand-logo {
            height: 60px;
            width: auto;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .brand-desc {
            font-size: 1.05rem;
            opacity: 0.95;
            line-height: 1.6;
            margin-bottom: 40px;
            position: relative; z-index: 2;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative; z-index: 2;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            font-size: 1rem;
            font-weight: 500;
        }

        .feature-icon {
            background: rgba(255,255,255,0.25);
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-right: 15px;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* --- RIGHT SIDE (Form) --- */
        .login-panel {
            flex: 1;
            background: rgba(255, 255, 255, 0.75); 
            backdrop-filter: blur(25px);
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--brand-primary);
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        /* Input Styling */
        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-dark);
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s;
        }

        .form-control-custom {
            width: 100%;
            padding: 14px 15px 14px 50px; 
            border: 1px solid rgba(0,0,0,0.1); 
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.6); 
            backdrop-filter: blur(5px);
        }

        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(0, 168, 204, 0.15);
            outline: none;
        }
        
        .form-control-custom:focus + .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: var(--brand-primary);
        }

        /* Password Toggle Eye */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            z-index: 2;
            transition: color 0.2s;
        }
        .password-toggle:hover { color: var(--brand-primary); }

        .forgot-link {
            display: block;
            text-align: right;
            font-size: 0.9rem;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--brand-primary); text-decoration: none; }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00a8cc 0%, #007ea7 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0, 168, 204, 0.3);
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(0, 168, 204, 0.4);
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--text-muted);
        }
        .register-text a {
            color: var(--brand-primary);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }
        .register-text a:hover { color: var(--brand-dark); text-decoration: underline; }

        .alert-error {
            background: rgba(255, 229, 229, 0.9);
            color: #d63031;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            border: 1px solid rgba(214, 48, 49, 0.2);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-wrapper { flex-direction: column; height: auto; }
            .brand-panel { padding: 40px; text-align: center; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
            .brand-header { justify-content: center; }
            .feature-list { display: none; } 
            .login-panel { padding: 40px 25px; background: rgba(255,255,255,0.85); }
        }
    </style>
</head>
<body>

    <a href="https://portal.bansalancoop.com" class="back-to-portal-btn animate__animated animate__fadeInDown">
        <i class="fa-solid fa-arrow-left"></i> Back to Main Portal
    </a>

    <div class="login-wrapper">
        
        <div class="brand-panel">
            <div class="brand-header">
                <img src="assets/images/proflogo.png" alt="Logo" class="brand-logo">
                <div class="brand-title">Bansalan<br>Coop Helpdesk</div>
            </div>
            
            <p class="brand-desc">
                Streamlining support for our branches. Report issues, track tickets, and get back to work faster.
            </p>
            
            <ul class="feature-list">
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-rocket"></i></div>
                    Fast Ticket Resolution
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    Secure & Reliable
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    Real-Time Customer Support
                </li>
            </ul>
        </div>

        <div class="login-panel">
            
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please enter your credentials to access the portal.</p>
            </div>

            <?php if($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group-custom">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control-custom" placeholder="name@company.com" required>
                    </div>
                </div>

                <div class="mb-1">
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="passwordInput" class="form-control-custom" placeholder="••••••••" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                </div>

                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>

                <button type="submit" name="login_btn" class="btn-login">Login to Portal</button>
            </form>

            <div class="register-text">
                New employee? <a href="register.php">Create an Account</a>
            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            var passwordInput = document.getElementById("passwordInput");
            var icon = document.querySelector(".password-toggle");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>