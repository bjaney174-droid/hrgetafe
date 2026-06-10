<?php
/**
 * LOGOUT PAGE
 * HRGetafe - Human Resources Information System
 */

session_start();

require_once 'config/constants.php';
require_once 'config/database.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $action = 'User Logout';
    $module = 'Authentication';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO audit_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $action, $module, $ip);
    $stmt->execute();
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . 'login.php');
exit();
?>
