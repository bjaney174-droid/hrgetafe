<?php
/**
 * AUTHENTICATION & SESSION HANDLER
 * HRGetafe - Human Resources Information System
 */

session_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check user role/permission
 */
function hasRole($required_role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] == $required_role || $_SESSION['role'] == ROLE_ADMIN;
}

/**
 * Check minimum role level
 */
function hasMinimumRole($min_role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] <= $min_role;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

/**
 * Redirect if not authorized
 */
function requireRole($required_role) {
    requireLogin();
    if (!hasRole($required_role)) {
        header('Location: ' . BASE_URL . 'unauthorized.php');
        exit();
    }
}

/**
 * Get current user information
 */
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) return null;
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT u.*, e.* FROM users u 
              LEFT JOIN employees e ON u.id = e.user_id 
              WHERE u.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get role name
 */
function getRoleName($role_id) {
    $roles = array(
        ROLE_ADMIN => 'Administrator',
        ROLE_HR_MANAGER => 'HR Manager',
        ROLE_HR_STAFF => 'HR Staff',
        ROLE_SUPERVISOR => 'Supervisor'
    );
    return $roles[$role_id] ?? 'Unknown';
}

/**
 * Log audit action
 */
function logAudit($action, $module, $record_id = null, $old_values = null, $new_values = null) {
    global $conn;
    
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO audit_logs (user_id, action, module, record_id, old_values, new_values, ip_address) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $old_json = $old_values ? json_encode($old_values) : null;
    $new_json = $new_values ? json_encode($new_values) : null;
    
    $stmt->bind_param("ississs", $user_id, $action, $module, $record_id, $old_json, $new_json, $ip_address);
    return $stmt->execute();
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

?>
