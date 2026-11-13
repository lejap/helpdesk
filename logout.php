<?php
session_start();
session_destroy();

// Redirects explicitly to your main domain
header("Location: http://helpdesk.bansalancoop.com");
exit();
?>