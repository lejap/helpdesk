<?php
require 'config/db.php';
session_start();

// 1. SECURITY: STRICT CHECK
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user || $user['email'] !== 'admin@gmail.com') {
    die("ACCESS DENIED.");
}

// 2. THE WIPE PROCESS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_wipe'])) {
    
    // Disable Checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // A. Wipe Tables
    $conn->query("TRUNCATE TABLE tickets");
    $conn->query("TRUNCATE TABLE categories");
    $conn->query("TRUNCATE TABLE logs"); 

    // B. Delete Everyone EXCEPT Admin
    $conn->query("DELETE FROM users WHERE email != 'admin@gmail.com'");

    // Re-enable Checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    
    header("Location: happy.php");
    exit();
}
?>