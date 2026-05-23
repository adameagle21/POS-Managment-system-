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

// Check if admin exists
$check_admin = mysqli_query($conn, "SELECT * FROM users WHERE username = 'admin'");
$admin_exists = mysqli_num_rows($check_admin) > 0;

// Create admin user
if(isset($_POST['create_admin'])) {
    $username = 'admin';
    $password = 'adam424';
    $password_md5 = md5($password);
    $firstname = 'Admin';
    $lastname = 'User';
    $email = 'admin@adamcar.com';
    $role = 'admin';
    $is_active = 1;
    
    // First, delete if exists
    mysqli_query($conn, "DELETE FROM users WHERE username = 'admin'");
    
    // Insert new admin
    $query = "INSERT INTO users (username, password, firstname, lastname, email, role, is_active) 
              VALUES ('$username', '$password_md5', '$firstname', '$lastname', '$email', '$role', '$is_active')";
    
    if(mysqli_query($conn, $query)) {
        $message = "Admin user created successfully!<br>
                    Username: <strong>admin</strong><br>
                    Password: <strong>adam424</strong>";
        $admin_exists = true;
    } else {
        $error = "Failed to create admin: " . mysqli_error($conn);
    }
}

// Check if users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
$table_exists = mysqli_num_rows($table_check) > 0;

// Get all users
$users = mysqli_query($conn, "SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .admin-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            max-width: 550px;
            margin: 0 auto;
        }
        .btn-create {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            font-size: 1rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .info-box {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        .error-box {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-card">
            <div class="text-center mb-4">
                <i class="fas fa-user-shield fa-3x" style="color: #667eea;"></i>
                <h2 class="mt-3">Create Admin User</h2>
                <p class="text-muted">Fix missing admin account</p>
            </div>
            
            <?php if($message): ?>
                <div class="info-box success-box">
                    <i class="fas fa-check-circle me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="info-box error-box">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if(!$table_exists): ?>
                <div class="info-box error-box mb-3">
                    <i class="fas fa-database me-2"></i> Users table does not exist!
                    <br><small>Please run setup_database.php first.</small>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h6><i class="fas fa-info-circle me-2"></i>Current Status:</h6>
                <ul class="mb-0">
                    <li>Database: <strong><?= $dbname ?></strong> <?= $conn ? '✓ Connected' : '✗' ?></li>
                    <li>Users table: <?= $table_exists ? '✓ Exists' : '✗ Missing' ?></li>
                    <li>Admin user: <?= $admin_exists ? '✓ Exists' : '✗ Missing' ?></li>
                </ul>
            </div>
            
            <?php if(!$admin_exists || isset($_POST['create_admin'])): ?>
                <form method="POST">
                    <button type="submit" name="create_admin" class="btn-create">
                        <i class="fas fa-user-plus me-2"></i> Create Admin User
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if($admin_exists): ?>
                <div class="info-box success-box mt-3">
                    <i class="fas fa-key me-2"></i>
                    <strong>Login Credentials:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>adam424</code>
                </div>
                
                <a href="login.php" class="btn-login mt-3">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                </a>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <?php if(mysqli_num_rows($users) > 0): ?>
                <h6><i class="fas fa-list me-2"></i>All Users in Database:</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= $u['username'] ?></td>
                                <td><?= $u['role'] ?></td>
                                <td><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>