<?php
/**
 * APPLICATION CONSTANTS
 * HRGetafe - Human Resources Information System
 */

// Application Settings
define('APP_NAME', 'HRGetafe');
define('APP_VERSION', '1.0.0');
define('APP_TITLE', 'HR Information System - Getafe LGU');
define('APP_DESCRIPTION', 'Human Resources Management System for Getafe Local Government Unit');

// Base URL
define('BASE_URL', 'http://localhost/HRGetafe/');

// File Upload Settings
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/HRGetafe/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// Session Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// User Roles
define('ROLE_ADMIN', 1);
define('ROLE_HR_MANAGER', 2);
define('ROLE_HR_STAFF', 3);
define('ROLE_SUPERVISOR', 4);

// Status Constants
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);

// Leave Status
define('LEAVE_PENDING', 'pending');
define('LEAVE_APPROVED', 'approved');
define('LEAVE_REJECTED', 'rejected');

// Messages
define('MSG_SUCCESS', 'Operation completed successfully!');
define('MSG_ERROR', 'An error occurred. Please try again.');
define('MSG_LOGIN_REQUIRED', 'Please log in to continue.');
define('MSG_ACCESS_DENIED', 'You do not have permission to access this page.');

?>
