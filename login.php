<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    // Check if connection exists
    if(!$conn) {
        $error = "Database connection failed!";
    } else {
        $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND is_active = 1";
        $result = mysqli_query($conn, $query);
        
        // Check if query was successful
        if(!$result) {
            $error = "Query error: " . mysqli_error($conn);
        } else if(mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];      // Custom role name (e.g., "Manager", "Staff")
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            
            // ============================================
            // IMPORTANT: Load permissions into session
            // ============================================
            if(!empty($user['permissions'])) {
                $_SESSION['permissions'] = explode(',', $user['permissions']);
            } else {
                $_SESSION['permissions'] = [];
            }
            
            // Debug: Log permissions (remove after testing)
            error_log("User $username logged in with permissions: " . print_r($_SESSION['permissions'], true));
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease;
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
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
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .alert-custom {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-car fa-3x" style="color: #667eea;"></i>
                        <h2 class="mt-3">Adam Car Accessories</h2>
                        <p class="text-muted">Login to your account</p>
                    </div>
                    
                    <?php if($error != ''): ?>
                        <div class="alert-custom">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control-custom" placeholder="Enter username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control-custom" placeholder="Enter password" required>
                        </div>
                        <button type="submit" name="login" class="btn-login mt-2">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </form>
                    
                    <hr class="mt-4">
                    <p class="text-center text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Contact administrator for credentials
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>