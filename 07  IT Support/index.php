<?php
require_once __DIR__ . '/config/database.php';

// Redirect based on login status
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($role === 'technician') {
        redirect('technician/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
} else {
    redirect('auth/login.php');
}
?>
