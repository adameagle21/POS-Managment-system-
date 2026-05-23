<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') header("Location: users.php");

$user_id = $_GET['id'] ?? 0;
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
if(!$user) header("Location: users.php");

$message = '';
$error = '';

if(isset($_POST['submit'])) {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $query = "UPDATE users SET firstname='$firstname', lastname='$lastname', email='$email', role='$role', is_active='$is_active' WHERE id=$user_id";
    
    if(mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>✓ User updated successfully!</div>";
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Reset password
if(isset($_POST['reset_password'])) {
    $new_password = md5($_POST['new_password']);
    mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id=$user_id");
    $message = "<div class='alert alert-success'>✓ Password has been reset!</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: fixed; height: 100vh; padding: 20px 0; }
        .sidebar-header { text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { color: #FFD700; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .menu-item i { width: 22px; }
        .main-content { margin-left: 280px; padding: 20px; }
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .form-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; display: block; }
        .form-control { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; width: 100%; }
        .form-control:focus { border-color: #4f46e5; outline: none; }
        .btn-save { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 600; }
        .btn-cancel { background: #64748b; color: white; padding: 12px 30px; border-radius: 12px; text-decoration: none; margin-left: 10px; }
        .btn-reset { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 8px 20px; border: none; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h3>🚗 Adam Car</h3></div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
            <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
            <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
            <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
            <div class="menu-divider"></div>
            <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title"><i class="fas fa-edit me-2"></i>Edit User</h1>
            <div><div class="user-avatar" style="width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div></div>
        </div>
        
        <div class="form-container">
            <?= $message ?>
            <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>First Name</label><input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($user['firstname']) ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Last Name</label><input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($user['lastname']) ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Username</label><input type="text" class="form-control" value="<?= $user['username'] ?>" disabled></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Role</label><select name="role" class="form-control"><option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option><option value="cashier" <?= $user['role']=='cashier'?'selected':'' ?>>Cashier</option><option value="stock_manager" <?= $user['role']=='stock_manager'?'selected':'' ?>>Stock Manager</option></select></div></div>
                    <div class="col-md-6"><div class="form-group"><label class="d-flex align-items-center"><input type="checkbox" name="is_active" <?= $user['is_active']?'checked':'' ?> style="width:20px;height:20px;margin-right:10px;"><span>Active Account</span></label></div></div>
                </div>
                <div class="mt-4"><button type="submit" name="submit" class="btn-save"><i class="fas fa-save me-2"></i>Update User</button><a href="users.php" class="btn-cancel"><i class="fas fa-times me-2"></i>Cancel</a></div>
            </form>
            
            <hr class="my-4">
            
            <h6><i class="fas fa-key me-2"></i>Reset Password</h6>
            <form method="POST" class="row g-3">
                <div class="col-md-4"><input type="password" name="new_password" class="form-control" placeholder="New password" required></div>
                <div class="col-md-4"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required></div>
                <div class="col-md-4"><button type="submit" name="reset_password" class="btn-reset"><i class="fas fa-sync-alt me-2"></i>Reset Password</button></div>
            </form>
        </div>
    </div>
</body>
</html>