<?php
$page_title = 'Roles & Permissions';
require_once 'config/connection.php';
require_once 'includes/functions.php';

// Check if user is admin
if(!hasRole('admin')) {
    redirect('index.php');
}

include 'includes/header.php';
include 'includes/sidebar.php';

// Handle permission update
if(isset($_POST['update_permissions'])) {
    $user_id = $_POST['user_id'];
    $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
    
    $query = "UPDATE users SET permissions = '$permissions' WHERE id = $user_id";
    if(mysqli_query($conn, $query)) {
        echo "<script>Swal.fire('Success!', 'Permissions updated successfully!', 'success');</script>";
    }
}

// Handle role update
if(isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    $query = "UPDATE users SET role = '$role' WHERE id = $user_id";
    if(mysqli_query($conn, $query)) {
        echo "<script>Swal.fire('Success!', 'Role updated successfully!', 'success');</script>";
    }
}

// Get all users except current user
$users = getRows("SELECT * FROM users WHERE id != {$_SESSION['user_id']} ORDER BY id DESC");

// Define all available features
$all_features = [
    'dashboard' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'description' => 'View dashboard and statistics'],
    'pos' => ['icon' => 'shopping-cart', 'label' => 'POS', 'description' => 'Access point of sale system'],
    'products' => ['icon' => 'box', 'label' => 'Products', 'description' => 'Manage products (add/edit/delete)'],
    'sales' => ['icon' => 'chart-line', 'label' => 'Sales', 'description' => 'View sales history'],
    'add_sale' => ['icon' => 'plus-circle', 'label' => 'Add Sale', 'description' => 'Create new sales'],
    'expenses' => ['icon' => 'receipt', 'label' => 'Expenses', 'description' => 'Manage expenses'],
    'users' => ['icon' => 'users', 'label' => 'Users', 'description' => 'Manage system users'],
    'roles' => ['icon' => 'key', 'label' => 'Roles', 'description' => 'Manage roles and permissions'],
    'reports' => ['icon' => 'file-alt', 'label' => 'Reports', 'description' => 'View and export reports'],
    'settings' => ['icon' => 'cog', 'label' => 'Settings', 'description' => 'System settings']
];

// Role definitions
$roles = [
    'admin' => [
        'name' => 'Administrator',
        'color' => 'danger',
        'icon' => 'crown',
        'description' => 'Full access to all features',
        'permissions' => array_keys($all_features)
    ],
    'cashier' => [
        'name' => 'Cashier',
        'color' => 'primary',
        'icon' => 'user-tie',
        'description' => 'Limited access for sales staff',
        'permissions' => ['dashboard', 'pos', 'sales', 'add_sale', 'reports']
    ],
    'stock_manager' => [
        'name' => 'Stock Manager',
        'color' => 'success',
        'icon' => 'boxes',
        'description' => 'Manage products and inventory',
        'permissions' => ['dashboard', 'products', 'reports']
    ]
];
?>

<style>
.role-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s;
    border: 1px solid #e2e8f0;
}
.role-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.permission-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}
.permission-badge {
    background: #e2e8f0;
    color: #1e293b;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}
.permission-checkbox {
    margin-right: 8px;
}
.user-card {
    background: white;
    border-radius: 16px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s;
}
.user-card:hover {
    border-color: #4f46e5;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
</style>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-key me-2"></i>Roles & Permissions</h3>
        <div>
            <span class="badge bg-danger"><i class="fas fa-crown me-1"></i> Admin</span>
            <span class="badge bg-primary ms-2"><i class="fas fa-user-tie me-1"></i> Cashier</span>
            <span class="badge bg-success ms-2"><i class="fas fa-boxes me-1"></i> Stock Manager</span>
        </div>
    </div>
    
    <!-- Roles Overview -->
    <div class="row mb-4">
        <?php foreach($roles as $role_key => $role): ?>
        <div class="col-md-4">
            <div class="role-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="role-icon bg-<?= $role['color'] ?> bg-opacity-10 me-3">
                        <i class="fas fa-<?= $role['icon'] ?> text-<?= $role['color'] ?>"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?= $role['name'] ?></h5>
                        <small class="text-muted"><?= $role['description'] ?></small>
                    </div>
                </div>
                <div class="permission-list">
                    <?php foreach($role['permissions'] as $perm): ?>
                        <span class="permission-badge">
                            <i class="fas fa-<?= $all_features[$perm]['icon'] ?? 'check' ?> me-1"></i>
                            <?= $all_features[$perm]['label'] ?? $perm ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- All Features List -->
    <div class="summary-card mb-4">
        <h5 class="mb-3"><i class="fas fa-list me-2"></i>All Available Features</h5>
        <div class="row">
            <?php foreach($all_features as $key => $feature): ?>
            <div class="col-md-3 mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-<?= $feature['icon'] ?> text-primary me-2" style="width: 20px;"></i>
                    <span><?= $feature['label'] ?></span>
                    <small class="text-muted ms-2">- <?= $feature['description'] ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- User Permissions Management -->
    <div class="summary-card">
        <h5 class="mb-3"><i class="fas fa-users me-2"></i>User Permissions Management</h5>
        <p class="text-muted mb-3">Assign specific permissions to each user. Admin users have full access.</p>
        
        <?php if(count($users) > 0): ?>
            <?php foreach($users as $user): 
                $user_perms = $user['permissions'] ? explode(',', $user['permissions']) : [];
            ?>
            <div class="user-card">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar-sm me-2" style="width: 45px; height:45px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <?= strtoupper(substr($user['firstname'] ?? $user['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></strong>
                                <br>
                                <small class="text-muted">@<?= $user['username'] ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="role" class="form-control form-control-sm" onchange="this.form.submit()" style="width: 130px;">
                                <option value="cashier" <?= $user['role'] == 'cashier' ? 'selected' : '' ?>>👤 Cashier</option>
                                <option value="stock_manager" <?= $user['role'] == 'stock_manager' ? 'selected' : '' ?>>📦 Stock Manager</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>👑 Admin</option>
                            </select>
                            <input type="hidden" name="update_role" value="1">
                        </form>
                    </div>
                    <div class="col-md-5">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($all_features as $key => $feature): ?>
                                    <label class="permission-checkbox">
                                        <input type="checkbox" name="permissions[]" value="<?= $key ?>" 
                                            <?= in_array($key, $user_perms) ? 'checked' : '' ?>
                                            <?= $user['role'] == 'admin' ? 'disabled' : '' ?>>
                                        <i class="fas fa-<?= $feature['icon'] ?>"></i>
                                        <?= $feature['label'] ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if($user['role'] != 'admin'): ?>
                            <button type="submit" name="update_permissions" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-save me-1"></i> Save Permissions
                            </button>
                            <?php else: ?>
                            <small class="text-muted d-block mt-2">Admin users have full access to all features</small>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'cashier' ? 'primary' : 'success') ?>">
                            <i class="fas fa-<?= $user['role'] == 'admin' ? 'crown' : ($user['role'] == 'cashier' ? 'user-tie' : 'boxes') ?> me-1"></i>
                            <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                        </span>
                        <br>
                        <span class="badge <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?> mt-1">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                <p>No other users found</p>
                <a href="add_user.php" class="btn btn-primary btn-sm">Add New User</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Permission Info -->
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Permission System:</strong> Users can only access features they have permission for. 
        Admin users automatically have full access to all features regardless of checkbox selection.
    </div>
</div>

<style>
.user-avatar-sm {
    transition: all 0.3s;
}
.permission-checkbox {
    background: #f8fafc;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: all 0.2s;
}
.permission-checkbox:hover {
    background: #e2e8f0;
}
.permission-checkbox input {
    margin-right: 3px;
}
</style>

<?php include 'includes/footer.php'; ?>