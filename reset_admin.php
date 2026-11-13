<?php
require 'config/db.php';

$email = 'admin@gmail.com';
$password = 'admin';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $conn->query("SELECT id FROM users WHERE email = '$email'");

if ($check->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'super_admin', is_active = 1 WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    $stmt->execute();
    echo "<h1>Success!</h1><p>Admin password reset to: <strong>admin123</strong></p>";
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, is_active) VALUES ('Super Admin', ?, ?, 'super_admin', 1)");
    $stmt->bind_param("ss", $email, $hashed_password);
    $stmt->execute();
    echo "<h1>Success!</h1><p>Admin account created.</p><br>Email: $email<br>Password: $password";
}

echo "<br><a href='index.php'>Go to Login</a>";
?>