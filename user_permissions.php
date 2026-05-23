<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
if(!hasRole('admin')) header("Location: index.php");

$user_id = $_GET['id'] ?? 0;
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
if(!$user) header("Location: users.php");

// ============================================
// COMPLETE PERMISSIONS - ALL FEATURES INCLUDING NEW UPDATES
// ============================================

$all_permissions = [
    // ========== DASHBOARD - MAIN CARDS ==========
    'dashboard_access' => ['name' => 'Dashboard Access', 'icon' => 'tachometer-alt', 'group' => 'Dashboard', 'description' => 'Can view main dashboard page'],
    'dashboard_total_sales_view' => ['name' => 'Dashboard: View Total Sales', 'icon' => 'dollar-sign', 'group' => 'Dashboard', 'description' => 'See total sales amount on dashboard'],
    'dashboard_total_profit_view' => ['name' => 'Dashboard: View Total Profit', 'icon' => 'chart-line', 'group' => 'Dashboard', 'description' => 'See total profit amount on dashboard'],
    'dashboard_total_expenses_view' => ['name' => 'Dashboard: View Total Expenses', 'icon' => 'receipt', 'group' => 'Dashboard', 'description' => 'See total expenses amount on dashboard'],
    'dashboard_today_sales_view' => ['name' => 'Dashboard: View Today\'s Sales', 'icon' => 'calendar-day', 'group' => 'Dashboard', 'description' => 'See today\'s sales amount'],
    'dashboard_today_profit_view' => ['name' => 'Dashboard: View Today\'s Profit', 'icon' => 'calendar-check', 'group' => 'Dashboard', 'description' => 'See today\'s profit amount'],
    'dashboard_total_products_view' => ['name' => 'Dashboard: View Total Products', 'icon' => 'boxes', 'group' => 'Dashboard', 'description' => 'See total products count'],
    'dashboard_total_users_view' => ['name' => 'Dashboard: View Total Users', 'icon' => 'users', 'group' => 'Dashboard', 'description' => 'See total users count'],
    'dashboard_low_stock_view' => ['name' => 'Dashboard: View Low Stock Alert', 'icon' => 'exclamation-triangle', 'group' => 'Dashboard', 'description' => 'See low stock alert count'],
    
    // ========== DASHBOARD - CHARTS & STATS ==========
    'dashboard_chart_view' => ['name' => 'Dashboard: View Sales Chart', 'icon' => 'chart-line', 'group' => 'Dashboard', 'description' => 'See sales & profit trend chart'],
    'dashboard_quick_stats_view' => ['name' => 'Dashboard: View Quick Stats', 'icon' => 'chart-pie', 'group' => 'Dashboard', 'description' => 'See quick statistics box'],
    'dashboard_recent_sales_view' => ['name' => 'Dashboard: View Recent Sales', 'icon' => 'history', 'group' => 'Dashboard', 'description' => 'See recent sales table'],
    'dashboard_calculator' => ['name' => 'Dashboard: Use Calculator', 'icon' => 'calculator', 'group' => 'Dashboard', 'description' => 'Use built-in calculator'],
    'dashboard_excel_uploads_view' => ['name' => 'Dashboard: View Excel Uploads', 'icon' => 'file-excel', 'group' => 'Dashboard', 'description' => 'See Excel uploads section on dashboard'],
    
    // ========== QUICK ACTIONS ==========
    'quick_start_sale' => ['name' => 'Quick Action: Start New Sale', 'icon' => 'shopping-cart', 'group' => 'Quick Actions', 'description' => 'Quick action button to POS'],
    'quick_add_product' => ['name' => 'Quick Action: Add Product', 'icon' => 'plus-circle', 'group' => 'Quick Actions', 'description' => 'Quick action button to add product'],
    
    // ========== POS ==========
    'pos_access' => ['name' => 'POS: Access', 'icon' => 'shopping-cart', 'group' => 'POS', 'description' => 'Access POS system'],
    'pos_add_to_cart' => ['name' => 'POS: Add to Cart', 'icon' => 'cart-plus', 'group' => 'POS', 'description' => 'Add products to cart'],
    'pos_remove_from_cart' => ['name' => 'POS: Remove from Cart', 'icon' => 'cart-arrow-down', 'group' => 'POS', 'description' => 'Remove items from cart'],
    'pos_checkout' => ['name' => 'POS: Checkout', 'icon' => 'credit-card', 'group' => 'POS', 'description' => 'Complete sale'],
    'pos_change_price' => ['name' => 'POS: Change Price', 'icon' => 'tag', 'group' => 'POS', 'description' => 'Manually change price'],
    'pos_import_excel' => ['name' => 'POS: Import Excel', 'icon' => 'file-import', 'group' => 'POS', 'description' => 'Upload Excel files from POS page'],
    
    // ========== PRODUCTS ==========
    'products_access' => ['name' => 'Products: Page Access', 'icon' => 'boxes', 'group' => 'Products', 'description' => 'View products page'],
    'products_view' => ['name' => 'Products: View', 'icon' => 'eye', 'group' => 'Products', 'description' => 'See all products'],
    'products_add' => ['name' => 'Products: Add', 'icon' => 'plus-circle', 'group' => 'Products', 'description' => 'Create products'],
    'products_edit' => ['name' => 'Products: Edit', 'icon' => 'edit', 'group' => 'Products', 'description' => 'Modify products'],
    'products_delete' => ['name' => 'Products: Delete', 'icon' => 'trash', 'group' => 'Products', 'description' => 'Remove products'],
    'products_delete_all' => ['name' => 'Products: Delete All', 'icon' => 'trash-alt', 'group' => 'Products', 'description' => '⚠️ DELETE all products at once (dangerous)'],
    'products_import' => ['name' => 'Products: Import', 'icon' => 'file-import', 'group' => 'Products', 'description' => 'Import from CSV'],
    'products_export' => ['name' => 'Products: Export', 'icon' => 'file-export', 'group' => 'Products', 'description' => 'Export to CSV'],
    'products_stock_adjust' => ['name' => 'Products: Adjust Stock', 'icon' => 'boxes', 'group' => 'Products', 'description' => 'Manually adjust quantity'],
    
    // ========== CATEGORIES ==========
    'categories_access' => ['name' => 'Categories: Page Access', 'icon' => 'tags', 'group' => 'Categories', 'description' => 'View categories page'],
    'categories_view' => ['name' => 'Categories: View', 'icon' => 'eye', 'group' => 'Categories', 'description' => 'See all categories'],
    'categories_add' => ['name' => 'Categories: Add', 'icon' => 'plus-circle', 'group' => 'Categories', 'description' => 'Create categories'],
    'categories_edit' => ['name' => 'Categories: Edit', 'icon' => 'edit', 'group' => 'Categories', 'description' => 'Modify categories'],
    'categories_delete' => ['name' => 'Categories: Delete', 'icon' => 'trash', 'group' => 'Categories', 'description' => 'Remove categories'],
    
    // ========== SALES ==========
    'sales_access' => ['name' => 'Sales: Page Access', 'icon' => 'chart-line', 'group' => 'Sales', 'description' => 'View sales page'],
    'sales_view' => ['name' => 'Sales: View', 'icon' => 'eye', 'group' => 'Sales', 'description' => 'See all sales'],
    'sales_add' => ['name' => 'Sales: Add', 'icon' => 'plus-circle', 'group' => 'Sales', 'description' => 'Create manual sale'],
    'sales_edit' => ['name' => 'Sales: Edit', 'icon' => 'edit', 'group' => 'Sales', 'description' => 'Modify sales'],
    'sales_delete' => ['name' => 'Sales: Delete', 'icon' => 'trash', 'group' => 'Sales', 'description' => 'Remove sales'],
    'sales_print_invoice' => ['name' => 'Sales: Print Invoice', 'icon' => 'print', 'group' => 'Sales', 'description' => 'Print invoice'],
    
    // ========== REPORTS - SEPARATED (NEW) ==========
    'reports_access' => ['name' => 'Reports: Page Access', 'icon' => 'file-alt', 'group' => 'Reports', 'description' => 'View reports page'],
    'reports_total_sales_view' => ['name' => 'Reports: View Total Sales', 'icon' => 'dollar-sign', 'group' => 'Reports', 'description' => 'See total sales amount in reports (separate permission)'],
    'reports_total_profit_view' => ['name' => 'Reports: View Total Profit', 'icon' => 'chart-line', 'group' => 'Reports', 'description' => 'See total profit amount in reports (separate permission)'],
    'reports_sales_details' => ['name' => 'Reports: View Sales Details', 'icon' => 'table', 'group' => 'Reports', 'description' => 'See detailed sales report'],
    'reports_profit_details' => ['name' => 'Reports: View Profit Details', 'icon' => 'chart-pie', 'group' => 'Reports', 'description' => 'See detailed profit report'],
    'reports_stock' => ['name' => 'Reports: Stock Reports', 'icon' => 'boxes', 'group' => 'Reports', 'description' => 'View stock reports'],
    'reports_export' => ['name' => 'Reports: Export', 'icon' => 'file-export', 'group' => 'Reports', 'description' => 'Export reports to CSV'],
    
    // ========== USERS ==========
    'users_access' => ['name' => 'Users: Page Access', 'icon' => 'users', 'group' => 'Users', 'description' => 'View users page'],
    'users_view' => ['name' => 'Users: View', 'icon' => 'eye', 'group' => 'Users', 'description' => 'See all users'],
    'users_add' => ['name' => 'Users: Add', 'icon' => 'user-plus', 'group' => 'Users', 'description' => 'Create users'],
    'users_edit' => ['name' => 'Users: Edit', 'icon' => 'user-edit', 'group' => 'Users', 'description' => 'Modify users'],
    'users_delete' => ['name' => 'Users: Delete', 'icon' => 'user-minus', 'group' => 'Users', 'description' => 'Remove users'],
    'users_permissions' => ['name' => 'Users: Manage Permissions', 'icon' => 'key', 'group' => 'Users', 'description' => 'Change permissions'],
    
    // ========== EXPENSES ==========
    'expenses_access' => ['name' => 'Expenses: Page Access', 'icon' => 'receipt', 'group' => 'Expenses', 'description' => 'View expenses page'],
    'expenses_view' => ['name' => 'Expenses: View', 'icon' => 'eye', 'group' => 'Expenses', 'description' => 'See all expenses'],
    'expenses_add' => ['name' => 'Expenses: Add', 'icon' => 'plus-circle', 'group' => 'Expenses', 'description' => 'Create expenses'],
    'expenses_edit' => ['name' => 'Expenses: Edit', 'icon' => 'edit', 'group' => 'Expenses', 'description' => 'Modify expenses'],
    'expenses_delete' => ['name' => 'Expenses: Delete', 'icon' => 'trash', 'group' => 'Expenses', 'description' => 'Remove expenses'],
    
    // ========== EXCEL UPLOADS (NEW - FULL MANAGEMENT) ==========
    'excel_uploads_access' => ['name' => 'Excel: Page Access', 'icon' => 'file-excel', 'group' => 'Excel Uploads', 'description' => 'View Excel uploads management page'],
    'excel_uploads_view' => ['name' => 'Excel: View Files', 'icon' => 'eye', 'group' => 'Excel Uploads', 'description' => 'See all uploaded Excel files'],
    'excel_uploads_upload' => ['name' => 'Excel: Upload Files', 'icon' => 'upload', 'group' => 'Excel Uploads', 'description' => 'Upload new Excel files from POS'],
    'excel_uploads_edit' => ['name' => 'Excel: Edit/Rename Files', 'icon' => 'edit', 'group' => 'Excel Uploads', 'description' => 'Rename uploaded Excel files'],
    'excel_uploads_delete' => ['name' => 'Excel: Delete Files', 'icon' => 'trash', 'group' => 'Excel Uploads', 'description' => 'Delete uploaded Excel files'],
    'excel_uploads_view_all' => ['name' => 'Excel: View All Uploads', 'icon' => 'folder-open', 'group' => 'Excel Uploads', 'description' => 'See uploads from all users (Admin)'],
];

$groups = ['Dashboard', 'Quick Actions', 'POS', 'Products', 'Categories', 'Sales', 'Reports', 'Users', 'Expenses', 'Excel Uploads'];

$current_permissions = $user['permissions'] ? explode(',', $user['permissions']) : [];

if(isset($_POST['save_permissions'])) {
    $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
    $update = mysqli_query($conn, "UPDATE users SET permissions = '$permissions' WHERE id = $user_id");
    
    if($update) {
        // IMPORTANT: Update session if this is the current logged in user
        if($user_id == $_SESSION['user_id']) {
            $_SESSION['permissions'] = explode(',', $permissions);
        }
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Permissions updated successfully!',
                confirmButtonColor: '#4f46e5'
            }).then(() => {
                window.location.href = 'user_permissions.php?id=$user_id';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error!', 'Failed to update permissions!', 'error');</script>";
    }
    $current_permissions = explode(',', $permissions);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: fixed; height: 100vh; padding: 20px 0; overflow-y: auto; }
        .sidebar-header { text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { color: #FFD700; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .form-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .permission-group { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .permission-group h6 { margin-bottom: 15px; font-weight: 700; color: #1e293b; border-left: 3px solid #4f46e5; padding-left: 10px; }
        .permission-checkbox { display: inline-flex; align-items: center; gap: 8px; background: white; padding: 8px 15px; border-radius: 30px; margin: 5px; border: 1px solid #e2e8f0; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .permission-checkbox:hover { background: #eef2ff; border-color: #4f46e5; }
        .permission-checkbox input { cursor: pointer; margin-right: 5px; width: 16px; height: 16px; }
        .btn-save { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.4); }
        .user-info-card { background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 20px; border-radius: 16px; margin-bottom: 25px; }
        .select-all-btn { background: #f1f5f9; border: none; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; margin-left: 10px; cursor: pointer; }
        .group-select-all { background: #e2e8f0; border: none; padding: 3px 10px; border-radius: 15px; font-size: 0.7rem; margin-left: 15px; cursor: pointer; }
        .btn-secondary-custom { background: #64748b; color: white; padding: 12px 30px; border-radius: 12px; text-decoration: none; display: inline-block; }
        .permission-count-badge { background: #4f46e5; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; margin-left: 10px; }
        .badge-new { background: #10b981; color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
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
        <?php if(hasPermission('excel_uploads_access') || hasRole('admin')): ?>
        <a href="excel_uploads.php" class="menu-item"><i class="fas fa-file-excel"></i> 📊 Excel Uploads</a>
        <?php endif; ?>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-key me-2"></i>User Permissions - Full Control</h1>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
    </div>
    
    <div class="user-info-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h5>
                <p class="mb-0 opacity-75">@<?= $user['username'] ?></p>
            </div>
            <div>
                <span class="badge bg-white text-dark">ID: <?= $user['id'] ?></span>
                <?php if($user_id == $_SESSION['user_id']): ?>
                    <span class="badge bg-warning text-dark ms-2">You (Current User)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="form-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="fas fa-check-square me-2"></i>Granular Permissions</h5>
                <p class="text-muted small mt-1">✅ Check = Allow | ❌ Uncheck = Deny | <strong class="text-danger">⚠️ Save then logout/login to see changes</strong></p>
                <p class="text-muted small"><i class="fas fa-info-circle me-1"></i> <strong>New permissions (Reports separated):</strong> Total Sales, Total Profit, Sales Details, Profit Details are now separate permissions!</p>
            </div>
            <div>
                <button type="button" class="select-all-btn" onclick="selectAllPermissions()"><i class="fas fa-check-square me-1"></i> Select All</button>
                <button type="button" class="select-all-btn" onclick="deselectAllPermissions()"><i class="fas fa-square me-1"></i> Deselect All</button>
            </div>
        </div>
        
        <form method="POST">
            <?php foreach($groups as $group): ?>
                <?php 
                $group_perms = array_filter($all_permissions, function($p) use ($group) { return $p['group'] == $group; });
                if(count($group_perms) == 0) continue;
                $group_icon = $group == 'Dashboard' ? 'tachometer-alt' : ($group == 'Quick Actions' ? 'bolt' : ($group == 'POS' ? 'shopping-cart' : ($group == 'Products' ? 'boxes' : ($group == 'Categories' ? 'tags' : ($group == 'Sales' ? 'chart-line' : ($group == 'Users' ? 'users' : ($group == 'Reports' ? 'file-alt' : ($group == 'Expenses' ? 'receipt' : 'file-excel'))))))));
                ?>
                <div class="permission-group">
                    <h6><i class="fas fa-<?= $group_icon ?> me-2"></i><?= $group ?>
                        <button type="button" class="group-select-all" onclick="selectGroup('<?= $group ?>')">Select All</button>
                        <span class="permission-count-badge"><?= count($group_perms) ?></span>
                    </h6>
                    <?php foreach($group_perms as $key => $feat): 
                        $checked = in_array($key, $current_permissions) ? 'checked' : '';
                        $isNew = in_array($key, ['reports_total_sales_view', 'reports_total_profit_view', 'reports_sales_details', 'reports_profit_details']);
                    ?>
                        <label class="permission-checkbox" title="<?= $feat['description'] ?>">
                            <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= $checked ?> data-group="<?= $group ?>">
                            <i class="fas fa-<?= $feat['icon'] ?>"></i> <?= $feat['name'] ?>
                            <small class="text-muted">(<?= $feat['description'] ?>)</small>
                            <?php if($isNew): ?>
                                <span class="badge-new">NEW</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="mt-4">
                <button type="submit" name="save_permissions" class="btn-save"><i class="fas fa-save me-2"></i>Save All Permissions</button>
                <a href="users.php" class="btn-secondary-custom ms-2"><i class="fas fa-arrow-left me-2"></i>Back to Users</a>
            </div>
        </form>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Important:</strong> After changing permissions, the user must <strong>logout and login again</strong> for the changes to take effect.
        </div>
        
        <div class="alert alert-warning mt-2">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Dangerous Permissions:</strong> "Delete All Products" and "Delete Excel Files" are high-risk actions. Only give these to trusted users.
        </div>
        
        <div class="alert alert-success mt-2">
            <i class="fas fa-chart-line me-2"></i>
            <strong>Reports Permissions (Separated):</strong>
            <ul class="mb-0 mt-1">
                <li><strong>reports_total_sales_view</strong> - Can view <strong>Total Sales</strong> in reports page</li>
                <li><strong>reports_total_profit_view</strong> - Can view <strong>Total Profit</strong> in reports page</li>
                <li><strong>reports_sales_details</strong> - Can view detailed <strong>Sales Report</strong> table</li>
                <li><strong>reports_profit_details</strong> - Can view detailed <strong>Profit Report</strong> table</li>
            </ul>
            <small class="text-muted">Note: Dashboard Total Sales and Total Profit are separate permissions (dashboard_total_sales_view, dashboard_total_profit_view)</small>
        </div>
        
        <div class="alert alert-primary mt-2">
            <i class="fas fa-file-excel me-2"></i>
            <strong>Excel Uploads Permissions:</strong>
            <ul class="mb-0 mt-1">
                <li><strong>excel_uploads_access</strong> - Can see "Excel Uploads" menu and page</li>
                <li><strong>excel_uploads_view</strong> - Can view uploaded files</li>
                <li><strong>excel_uploads_upload</strong> - Can upload new Excel files from POS</li>
                <li><strong>excel_uploads_edit</strong> - Can rename files</li>
                <li><strong>excel_uploads_delete</strong> - Can delete files</li>
            </ul>
        </div>
    </div>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
}
function deselectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
}
function selectGroup(groupName) {
    document.querySelectorAll('input[name="permissions[]"][data-group="' + groupName + '"]').forEach(cb => cb.checked = true);
}
</script>
</body>
</html>