<?php
require_once __DIR__ . '/../config/database.php';

// Destroy session
session_destroy();

// Redirect to login
header("Location: " . BASE_URL . "auth/login.php");
exit;
?>
