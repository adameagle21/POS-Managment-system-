<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'adam_car_db';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
$error = '';

// Fix password to adam424
if(isset($_POST['fix_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password != $confirm_password) {
        $error = "Passwords do not match!";
    } elseif(strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters!";
    } else {
        $new_password_md5 = md5($new_password);
        
        // Update admin password
        $query = "UPDATE users SET password = '$new_password_md5' WHERE username = 'admin'";
        if(mysqli_query($conn, $query)) {
            $message = "Password has been updated successfully!<br>New password: <strong>" . htmlspecialchars($new_password) . "</strong>";
        } else {
            $error = "Failed to update password: " . mysqli_error($conn);
        }
    }
}

// Set password to adam424 directly
if(isset($_POST['set_adam424'])) {
    $new_password = 'adam424';
    $new_password_md5 = md5($new_password);
    
    $query = "UPDATE users SET password = '$new_password_md5' WHERE username = 'admin'";
    if(mysqli_query($conn, $query)) {
        $message = "Password has been set to: <strong>adam424</strong>";
    } else {
        $error = "Failed to update password: " . mysqli_error($conn);
    }
}

// Get current admin info
$admin_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, password FROM users WHERE username = 'admin'"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Password - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .fix-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        .btn-fix {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
        }
        .btn-set {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
        }
        .current-info {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="fix-card">
            <div class="text-center mb-4">
                <i class="fas fa-key fa-3x" style="color: #667eea;"></i>
                <h2 class="mt-3">Fix Password</h2>
                <p class="text-muted">Reset admin password</p>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="current-info">
                <small>
                    <i class="fas fa-database me-1"></i> Current admin username: <strong>admin</strong><br>
                    <i class="fas fa-lock me-1"></i> Current password hash: <code><?= substr($admin_info['password'] ?? '', 0, 20) ?>...</code>
                </small>
            </div>
            
            <!-- Option 1: Set to adam424 directly -->
            <form method="POST" class="mb-3">
                <button type="submit" name="set_adam424" class="btn-set">
                    <i class="fas fa-key me-2"></i> Set Password to "adam424"
                </button>
            </form>
            
            <hr class="my-4">
            
            <!-- Option 2: Custom password -->
            <form method="POST">
                <div class="mb-3">
                    <label>New Password</label>
                    <input type="text" name="new_password" class="form-control" placeholder="Enter new password" required>
                </div>
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input type="text" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                </div>
                <button type="submit" name="fix_password" class="btn-fix">
                    <i class="fas fa-save me-2"></i> Update Password
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <a href="login.php" class="btn btn-secondary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>