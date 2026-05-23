<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

if(isset($_POST['reset'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters!";
    } elseif($new_password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $new_password_md5 = md5($new_password);
        $query = "UPDATE users SET password = '$new_password_md5' WHERE role = 'admin' OR username = 'admin'";
        
        if(mysqli_query($conn, $query)) {
            $message = "Admin password has been reset to: <strong>$new_password</strong>";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Get current admin info
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, username, role FROM users WHERE role = 'admin' OR username = 'admin' LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .reset-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            max-width: 450px;
            margin: 0 auto;
        }
        .btn-reset { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px; border-radius: 15px; font-weight: 600; width: 100%; }
        .form-control { border: 2px solid #e2e8f0; border-radius: 15px; padding: 12px 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="reset-card">
        <div class="text-center mb-4">
            <i class="fas fa-key fa-3x" style="color: #667eea;"></i>
            <h3 class="mt-3">Reset Admin Password</h3>
            <?php if($admin): ?>
                <p class="text-muted">For: <strong><?= $admin['username'] ?></strong> (<?= $admin['role'] ?>)</p>
            <?php endif; ?>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <a href="login.php" class="btn-reset d-block text-center text-decoration-none">Go to Login</a>
        <?php elseif($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <form method="POST">
                <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="mb-3"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" name="reset" class="btn-reset">Reset Password</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="mb-3"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" name="reset" class="btn-reset">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <hr class="mt-4">
        <div class="text-center">
            <a href="login.php" class="text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>