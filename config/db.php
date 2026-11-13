<?php
// 1. SECURITY: Disable Error Reporting for Live Site
// This prevents PHP from showing file paths or database errors to users (hackers).
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0); // Turn off all error reporting to the screen

// =======================================================
// 2. DATABASE CREDENTIALS (UPDATED FOR BLUEHOST)
// =======================================================

$host   = 'localhost';         // KEEP THIS AS 'localhost' (Do not put an IP)
$user   = 'bansala1_jap';     // CHANGE THIS to the User you created (check cPanel if unsure)
$pass   = 'Bansalancoop@2026'; // CHANGE THIS to the password you created for that user
$dbname = 'bansala1_helpdesk'; // CHANGE THIS to the Database Name (e.g., bansala1_helpdesk)

// =======================================================

// 3. SECURE CONNECTION
try {
    // Create connection
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Check connection without printing specific errors to screen
    if ($conn->connect_error) {
        // Log the error securely to a file on the server instead of showing it
        error_log("Database Connection Failed: " . $conn->connect_error);
        
        // Kill the script with a generic message so hackers don't get info
        die("System Maintenance: Unable to connect to the database. Please try again later."); 
    }

    // 4. SECURITY: Set Charset to prevent SQL injection via encoding attacks
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
        exit();
    }

} catch (Exception $e) {
    // Catch any other unexpected database errors
    error_log($e->getMessage());
    die("System Error.");
}

// 5. SESSION MANAGEMENT
// Only start the session if one hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Security: Enforce strict session settings before starting
    ini_set('session.cookie_httponly', 1); 
    ini_set('session.use_only_cookies', 1);
    
    // Optional: Force secure cookies since you are on Bluehost (HTTPS)
    // ini_set('session.cookie_secure', 1); 

    session_start();
}

// Set your timezone
date_default_timezone_set('Asia/Manila'); 

// 6. HELPER FUNCTIONS

// Helper: Check Login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Helper: Secure inputs (Prevents XSS and basic SQL Injection)
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($conn->real_escape_string($data), ENT_QUOTES, 'UTF-8');
}
?>