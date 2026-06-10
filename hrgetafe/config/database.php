<?php
/**
 * DATABASE CONFIGURATION
 * HRGetafe - Human Resources Information System
 */

// Database Connection Details
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');            // MySQL username (default: root for XAMPP)
define('DB_PASS', '');                // MySQL password (default: blank for XAMPP)
define('DB_NAME', 'hrgetafe_db');     // Database name
define('DB_PORT', 3307);              // MySQL port (changed to 3307)

// Create connection with error handling
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    // Log the error
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // Display user-friendly error
    die("
        <div style='margin: 50px; padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>
            <h3>⚠️ Database Connection Failed</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
            <p><strong>Server:</strong> " . DB_HOST . ":" . DB_PORT . "</p>
            <p><strong>Database:</strong> " . DB_NAME . "</p>
            <hr>
            <p><strong>Troubleshooting:</strong></p>
            <ul>
                <li>✅ Make sure MySQL is running in XAMPP Control Panel</li>
                <li>✅ Verify the database 'hrgetafe_db' exists in phpMyAdmin</li>
                <li>✅ Check that username is 'root' and password is blank</li>
                <li>✅ Ensure MySQL port is 3307 (or your custom port)</li>
            </ul>
            <p><a href='http://localhost/phpmyadmin' style='color: #721c24; text-decoration: underline;'>Open phpMyAdmin</a></p>
        </div>
    ");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Return connection object
?>
