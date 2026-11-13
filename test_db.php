<?php
include 'db_connect.php'; // Path to your main connection script

if ($conn->ping()) {
    echo "<h1>Connected Successfully!</h1>";
    echo "Server version: " . $conn->server_info;
} else {
    echo "Error: " . $conn->error;
}
?>