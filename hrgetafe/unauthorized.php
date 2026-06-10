<?php
/**
 * 403 UNAUTHORIZED PAGE
 * HRGetafe - Human Resources Information System
 */

require_once 'config/constants.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - <?php echo APP_TITLE; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            text-align: center;
            color: white;
        }
        
        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .error-code {
            font-size: 72px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .error-title {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .btn-home {
            background: white;
            color: #667eea;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        
        <div class="error-code">403</div>
        
        <h1 class="error-title">Access Denied</h1>
        
        <p class="error-message">
            You do not have permission to access this resource.
        </p>
        
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn-home">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
