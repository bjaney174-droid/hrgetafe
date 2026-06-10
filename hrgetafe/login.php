<?php
/**
 * LOGIN PAGE
 * HRGetafe - Human Resources Information System
 */

require_once 'config/constants.php';
require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}

session_start();

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check credentials
        $query = "SELECT id, username, password, role, status FROM users WHERE username = ? AND status = 1";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Update last login
                    $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    
                    // Log the login action
                    $log_query = "INSERT INTO audit_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_query);
                    $action = 'User Login';
                    $module = 'Authentication';
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_stmt->bind_param("isss", $user['id'], $action, $module, $ip);
                    $log_stmt->execute();
                    
                    header('Location: ' . BASE_URL . 'dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_TITLE; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .login-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .login-form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            width: 100%;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .demo-credentials {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .demo-credentials h6 {
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }
        
        .demo-credentials p {
            margin: 5px 0;
        }
        
        .demo-credentials code {
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Header -->
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-building"></i>
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p><?php echo APP_TITLE; ?></p>
        </div>
        
        <!-- Login Form -->
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        placeholder="Enter your username" 
                        required 
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle"></i> Demo Credentials</h6>
                <p><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></p>
                <p><strong>HR Manager:</strong> <code>hrmanager</code> / <code>password123</code></p>
                <p><strong>HR Staff:</strong> <code>hrstaff</code> / <code>password123</code></p>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    <i class="fas fa-warning"></i> For demo purposes only. Change credentials after setup.
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
