<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and has permission to add users
if(!isset($_SESSION['user_id'])) header("Location: login.php");
if(!hasPermission('users_add') && !hasRole('admin')) header("Location: users.php");

// All available features/modules in the system - GRANULAR PERMISSIONS
$all_permissions = [
    // ========== DASHBOARD & MAIN ==========
    'dashboard_access' => ['name' => 'Dashboard Access', 'icon' => 'tachometer-alt', 'group' => 'Dashboard', 'desc' => 'Can view main dashboard'],
    'dashboard_stats_view' => ['name' => 'View Statistics', 'icon' => 'chart-line', 'group' => 'Dashboard', 'desc' => 'See sales/profit stats'],
    'dashboard_chart_view' => ['name' => 'View Charts', 'icon' => 'chart-bar', 'group' => 'Dashboard', 'desc' => 'See sales charts'],
    
    // ========== POS ==========
    'pos_access' => ['name' => 'POS Access', 'icon' => 'shopping-cart', 'group' => 'POS', 'desc' => 'Access POS system'],
    'pos_add_to_cart' => ['name' => 'Add to Cart', 'icon' => 'cart-plus', 'group' => 'POS', 'desc' => 'Add products to cart'],
    'pos_checkout' => ['name' => 'Checkout', 'icon' => 'credit-card', 'group' => 'POS', 'desc' => 'Complete sale'],
    'pos_change_price' => ['name' => 'Change Price', 'icon' => 'tag', 'group' => 'POS', 'desc' => 'Manually change selling price'],
    
    // ========== PRODUCTS - FULL CRUD ==========
    'products_access' => ['name' => 'Products Page Access', 'icon' => 'boxes', 'group' => 'Products', 'desc' => 'View products page'],
    'products_view' => ['name' => 'View Products', 'icon' => 'eye', 'group' => 'Products', 'desc' => 'See all products'],
    'products_add' => ['name' => 'Add Product', 'icon' => 'plus-circle', 'group' => 'Products', 'desc' => 'Create new products'],
    'products_edit' => ['name' => 'Edit Product', 'icon' => 'edit', 'group' => 'Products', 'desc' => 'Modify products'],
    'products_delete' => ['name' => 'Delete Product', 'icon' => 'trash', 'group' => 'Products', 'desc' => 'Remove products'],
    'products_import' => ['name' => 'Import Products', 'icon' => 'file-import', 'group' => 'Products', 'desc' => 'Import from CSV'],
    'products_export' => ['name' => 'Export Products', 'icon' => 'file-export', 'group' => 'Products', 'desc' => 'Export to CSV'],
    'products_stock_adjust' => ['name' => 'Adjust Stock', 'icon' => 'boxes', 'group' => 'Products', 'desc' => 'Manually adjust quantity'],
    
    // ========== CATEGORIES - FULL CRUD ==========
    'categories_access' => ['name' => 'Categories Access', 'icon' => 'tags', 'group' => 'Categories', 'desc' => 'View categories page'],
    'categories_view' => ['name' => 'View Categories', 'icon' => 'eye', 'group' => 'Categories', 'desc' => 'See all categories'],
    'categories_add' => ['name' => 'Add Category', 'icon' => 'plus-circle', 'group' => 'Categories', 'desc' => 'Create categories'],
    'categories_edit' => ['name' => 'Edit Category', 'icon' => 'edit', 'group' => 'Categories', 'desc' => 'Modify categories'],
    'categories_delete' => ['name' => 'Delete Category', 'icon' => 'trash', 'group' => 'Categories', 'desc' => 'Remove categories'],
    
    // ========== SALES - FULL CRUD ==========
    'sales_access' => ['name' => 'Sales Page Access', 'icon' => 'chart-line', 'group' => 'Sales', 'desc' => 'View sales page'],
    'sales_view' => ['name' => 'View Sales', 'icon' => 'eye', 'group' => 'Sales', 'desc' => 'See sales history'],
    'sales_add' => ['name' => 'Add Sale', 'icon' => 'plus-circle', 'group' => 'Sales', 'desc' => 'Create manual sale'],
    'sales_edit' => ['name' => 'Edit Sale', 'icon' => 'edit', 'group' => 'Sales', 'desc' => 'Modify sales'],
    'sales_delete' => ['name' => 'Delete Sale', 'icon' => 'trash', 'group' => 'Sales', 'desc' => 'Remove sales'],
    'sales_print_invoice' => ['name' => 'Print Invoice', 'icon' => 'print', 'group' => 'Sales', 'desc' => 'Print sale invoice'],
    
    // ========== USERS - FULL CRUD ==========
    'users_access' => ['name' => 'Users Page Access', 'icon' => 'users', 'group' => 'Users', 'desc' => 'View users page'],
    'users_view' => ['name' => 'View Users', 'icon' => 'eye', 'group' => 'Users', 'desc' => 'See all users'],
    'users_add' => ['name' => 'Add User', 'icon' => 'user-plus', 'group' => 'Users', 'desc' => 'Create new users'],
    'users_edit' => ['name' => 'Edit User', 'icon' => 'user-edit', 'group' => 'Users', 'desc' => 'Modify users'],
    'users_delete' => ['name' => 'Delete User', 'icon' => 'user-minus', 'group' => 'Users', 'desc' => 'Remove users'],
    'users_permissions' => ['name' => 'Manage Permissions', 'icon' => 'key', 'group' => 'Users', 'desc' => 'Change user permissions'],
    
    // ========== REPORTS ==========
    'reports_access' => ['name' => 'Reports Access', 'icon' => 'file-alt', 'group' => 'Reports', 'desc' => 'View reports page'],
    'reports_sales' => ['name' => 'Sales Reports', 'icon' => 'chart-line', 'group' => 'Reports', 'desc' => 'View sales reports'],
    'reports_profit' => ['name' => 'Profit Reports', 'icon' => 'chart-line', 'group' => 'Reports', 'desc' => 'View profit reports'],
    'reports_stock' => ['name' => 'Stock Reports', 'icon' => 'boxes', 'group' => 'Reports', 'desc' => 'View stock reports'],
    'reports_export' => ['name' => 'Export Reports', 'icon' => 'file-export', 'group' => 'Reports', 'desc' => 'Export to CSV'],
    
    // ========== EXPENSES - FULL CRUD ==========
    'expenses_access' => ['name' => 'Expenses Access', 'icon' => 'receipt', 'group' => 'Expenses', 'desc' => 'View expenses page'],
    'expenses_view' => ['name' => 'View Expenses', 'icon' => 'eye', 'group' => 'Expenses', 'desc' => 'See all expenses'],
    'expenses_add' => ['name' => 'Add Expense', 'icon' => 'plus-circle', 'group' => 'Expenses', 'desc' => 'Create expenses'],
    'expenses_edit' => ['name' => 'Edit Expense', 'icon' => 'edit', 'group' => 'Expenses', 'desc' => 'Modify expenses'],
    'expenses_delete' => ['name' => 'Delete Expense', 'icon' => 'trash', 'group' => 'Expenses', 'desc' => 'Remove expenses'],
    
    // ========== SETTINGS ==========
    'settings_access' => ['name' => 'Settings Access', 'icon' => 'cog', 'group' => 'Settings', 'desc' => 'View settings page'],
    'settings_general' => ['name' => 'General Settings', 'icon' => 'sliders-h', 'group' => 'Settings', 'desc' => 'Change settings'],
    'settings_backup' => ['name' => 'Backup Database', 'icon' => 'database', 'group' => 'Settings', 'desc' => 'Backup system data']
];

$groups = ['Dashboard', 'POS', 'Products', 'Categories', 'Sales', 'Users', 'Reports', 'Expenses', 'Settings'];

$message = '';
$error = '';

if(isset($_POST['submit'])) {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $custom_role = mysqli_real_escape_string($conn, $_POST['custom_role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get permissions from checkboxes
    $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
    
    // Check if username exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Username already exists!";
    } else {
        $query = "INSERT INTO users (firstname, lastname, email, username, password, role, is_active, permissions) 
                  VALUES ('$firstname', '$lastname', '$email', '$username', '$password', '$custom_role', '$is_active', '$permissions')";
        
        if(mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success'>✓ User added successfully! <a href='users.php'>View Users</a></div>";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar-header { text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { color: #FFD700; }
        .sidebar-header p { color: #94a3b8; font-size: 0.75rem; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: rgba(79,70,229,0.2); color: white; }
        .menu-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 15px 18px; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .form-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; display: block; color: #1e293b; }
        .form-control { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; width: 100%; }
        .form-control:focus { border-color: #4f46e5; outline: none; }
        .btn-save { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.4); }
        .btn-cancel { background: #64748b; color: white; padding: 12px 30px; border-radius: 12px; text-decoration: none; margin-left: 10px; display: inline-block; }
        .permission-group { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .permission-group h6 { margin-bottom: 15px; color: #1e293b; border-left: 3px solid #4f46e5; padding-left: 10px; }
        .permission-checkbox { display: inline-flex; align-items: center; gap: 8px; background: white; padding: 8px 15px; border-radius: 30px; margin: 5px; border: 1px solid #e2e8f0; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .permission-checkbox:hover { background: #eef2ff; border-color: #4f46e5; }
        .permission-checkbox input { margin-right: 5px; cursor: pointer; }
        .group-select-all { background: #e2e8f0; border: none; padding: 3px 10px; border-radius: 15px; font-size: 0.7rem; margin-left: 15px; cursor: pointer; }
        .group-select-all:hover { background: #cbd5e1; }
        .select-all-btn { background: #f1f5f9; border: none; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; margin-left: 10px; cursor: pointer; }
        .select-all-btn:hover { background: #e2e8f0; }
        .permission-count-badge { background: #4f46e5; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; margin-left: 10px; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>🚗 Adam Car</h3><p>Accessories System</p></div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
        <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
        <a href="users.php" class="menu-item active"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-user-plus me-2"></i>Add New User</h1>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
    </div>
    
    <div class="form-container">
        <?= $message ?>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Permission System:</strong> Check = Allow | Uncheck = Deny. Users can only access features you check.
        </div>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>First Name *</label><input type="text" name="firstname" class="form-control" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Last Name *</label><input type="text" name="lastname" class="form-control" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Username *</label><input type="text" name="username" class="form-control" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Password *</label><input type="password" name="password" class="form-control" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" class="form-control" required></div></div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tag me-2"></i>Role / Position (Custom)</label>
                <input type="text" name="custom_role" class="form-control" placeholder="e.g., Manager, Cashier, Stock Clerk, Sales Rep, Guest..." value="Staff">
                <small class="text-muted">You can write any role name you want. This is just for display.</small>
            </div>
            
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label><i class="fas fa-key me-2"></i>Granular Permissions</label>
                    <div>
                        <button type="button" class="select-all-btn" onclick="selectAllPermissions()"><i class="fas fa-check-square me-1"></i> Select All</button>
                        <button type="button" class="select-all-btn" onclick="deselectAllPermissions()"><i class="fas fa-square me-1"></i> Deselect All</button>
                    </div>
                </div>
                <p class="text-muted small mb-3">Check = Allow | Uncheck = Deny. User will only see/access checked features.</p>
                
                <?php foreach($groups as $group): ?>
                    <?php 
                    $group_perms = array_filter($all_permissions, function($p) use ($group) {
                        return isset($p['group']) && $p['group'] == $group;
                    });
                    if(count($group_perms) == 0) continue;
                    
                    // Determine icon for each group
                    $group_icon = 'cog';
                    if($group == 'Dashboard') $group_icon = 'tachometer-alt';
                    elseif($group == 'POS') $group_icon = 'shopping-cart';
                    elseif($group == 'Products') $group_icon = 'boxes';
                    elseif($group == 'Categories') $group_icon = 'tags';
                    elseif($group == 'Sales') $group_icon = 'chart-line';
                    elseif($group == 'Users') $group_icon = 'users';
                    elseif($group == 'Reports') $group_icon = 'file-alt';
                    elseif($group == 'Expenses') $group_icon = 'receipt';
                    elseif($group == 'Settings') $group_icon = 'cog';
                    ?>
                    <div class="permission-group">
                        <h6>
                            <i class="fas fa-<?= $group_icon ?> me-2"></i>
                            <?= $group ?> Permissions
                            <button type="button" class="group-select-all" onclick="selectGroup('<?= $group ?>')">Select All</button>
                            <span class="permission-count-badge"><?= count($group_perms) ?></span>
                        </h6>
                        <?php foreach($group_perms as $key => $perm): ?>
                            <label class="permission-checkbox" title="<?= $perm['desc'] ?>">
                                <input type="checkbox" name="permissions[]" value="<?= $key ?>" data-group="<?= $group ?>">
                                <i class="fas fa-<?= $perm['icon'] ?>"></i>
                                <?= $perm['name'] ?>
                                <small class="text-muted">(<?= $perm['desc'] ?>)</small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-group">
                <label class="d-flex align-items-center">
                    <input type="checkbox" name="is_active" checked style="width:20px;height:20px;margin-right:10px;">
                    <span>Active Account (User can login)</span>
                </label>
            </div>
            
            <div class="mt-4">
                <button type="submit" name="submit" class="btn-save"><i class="fas fa-save me-2"></i>Create User</button>
                <a href="users.php" class="btn-cancel"><i class="fas fa-times me-2"></i>Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function selectAllPermissions() {
    let checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
}

function deselectAllPermissions() {
    let checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
}

function selectGroup(groupName) {
    let checkboxes = document.querySelectorAll('input[name="permissions[]"][data-group="' + groupName + '"]');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
}
</script>
</body>
</html>