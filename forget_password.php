<?php
session_start();
require_once __DIR__ . '/config/db.php';

$step = 1;
$error = '';
$success = '';

// Step 1: Enter username/email
if(isset($_POST['check_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND role = 'admin'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_username'] = $user['username'];
        $step = 2;
    } else {
        $error = "Admin username not found! Please contact system administrator.";
    }
}

// Step 2: Verify security (simple security question - you can modify)
if(isset($_POST['verify_security'])) {
    $security_answer = strtolower(trim($_POST['security_answer']));
    
    // Default security question and answer (you can change these in database)
    // Question: "What is the name of this store?"
    // Answer: "Adam Car" or "adam car"
    if($security_answer == 'adam car' || $security_answer == 'adam car accessories') {
        $step = 3;
    } else {
        $error = "Incorrect answer! Please try again.";
    }
}

// Step 3: Reset password
if(isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters!";
    } elseif($new_password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $new_password_md5 = md5($new_password);
        $user_id = $_SESSION['reset_user_id'];
        
        $query = "UPDATE users SET password = '$new_password_md5' WHERE id = $user_id";
        if(mysqli_query($conn, $query)) {
            $success = "Password has been reset successfully!";
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_username']);
            $step = 4;
        } else {
            $error = "Error resetting password: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .reset-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .reset-header { text-align: center; margin-bottom: 30px; }
        .reset-header h2 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        .reset-header p { color: #6b7280; font-size: 0.9rem; margin-top: 8px; }
        .form-control-custom {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 12px 20px;
            transition: 0.3s;
            width: 100%;
        }
        .form-control-custom:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
            cursor: pointer;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-back:hover { background: #475569; color: white; }
        .alert-custom {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-danger-custom {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .alert-success-custom {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .info-box {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step-number {
            width: 35px;
            height: 35px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: bold;
            color: #64748b;
        }
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .step.completed .step-number {
            background: #10b981;
            color: white;
        }
        .step-label { font-size: 0.7rem; color: #64748b; }
        .step.active .step-label { color: #667eea; font-weight: 600; }
        .step.completed .step-label { color: #10b981; }
    </style>
</head>
<body>
<div class="container">
    <div class="reset-card">
        <div class="reset-header">
            <i class="fas fa-key fa-3x" style="color: #667eea;"></i>
            <h2 class="mt-3">Forgot Password?</h2>
            <p>Reset your admin account password</p>
        </div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div class="step-label">Verify User</div>
            </div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="step-number">2</div>
                <div class="step-label">Security Check</div>
            </div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                <div class="step-number">3</div>
                <div class="step-label">Reset Password</div>
            </div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label">Done</div>
            </div>
        </div>
        
        <?php if($error): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle me-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if($step == 1): ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Admin Username</label>
                    <input type="text" name="username" class="form-control-custom" placeholder="Enter your admin username" required autofocus>
                </div>
                <button type="submit" name="check_user" class="btn-reset mb-3">
                    <i class="fas fa-search me-2"></i> Verify Account
                </button>
                <a href="login.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                </a>
            </form>
        <?php endif; ?>
        
        <?php if($step == 2): ?>
            <div class="info-box">
                <i class="fas fa-shield-alt me-2 text-primary"></i>
                <strong>Security Question:</strong><br>
                "What is the name of this store?"
            </div>
            <form method="POST">
                <div class="mb-3">
                    <label>Your Answer</label>
                    <input type="text" name="security_answer" class="form-control-custom" placeholder="Enter your answer" required autofocus>
                    <small class="text-muted">Hint: "Adam Car" or "Adam Car Accessories"</small>
                </div>
                <button type="submit" name="verify_security" class="btn-reset mb-3">
                    <i class="fas fa-check-circle me-2"></i> Verify Answer
                </button>
                <a href="login.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                </a>
            </form>
        <?php endif; ?>
        
        <?php if($step == 3): ?>
            <div class="info-box">
                <i class="fas fa-user me-2 text-primary"></i>
                <strong>Resetting password for:</strong> <?= htmlspecialchars($_SESSION['reset_username'] ?? 'Admin') ?>
            </div>
            <form method="POST">
                <div class="mb-3">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control-custom" placeholder="Enter new password" required autofocus>
                </div>
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control-custom" placeholder="Confirm new password" required>
                </div>
                <button type="submit" name="reset_password" class="btn-reset mb-3">
                    <i class="fas fa-save me-2"></i> Reset Password
                </button>
                <a href="login.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                </a>
            </form>
        <?php endif; ?>
        
        <?php if($step == 4): ?>
            <div class="text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <p>Your password has been reset successfully!</p>
                <a href="login.php" class="btn-reset mt-3 d-block text-center text-decoration-none">
                    <i class="fas fa-sign-in-alt me-2"></i> Login Now
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>