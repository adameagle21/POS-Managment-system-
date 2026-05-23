<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check permission - only users with users_view or admin can access
if(!hasPermission('users_view') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// Get all users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Adam Car</title>
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
            z-index: 100;
        }
        .sidebar-header { text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-header h3 { color: #FFD700; font-weight: 700; }
        .sidebar-header p { color: #94a3b8; font-size: 0.75rem; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; }
        .menu-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 15px 18px; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .btn-add { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.4); color: white; }
        .data-table { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .data-table th { background: #f8fafc; padding: 15px; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #e2e8f0; }
        .data-table tr:hover { background: #f8fafc; }
        .btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; margin: 2px; transition: 0.3s; }
        .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 3px 10px rgba(245,158,11,0.4); }
        .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; margin: 2px; transition: 0.3s; }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 3px 10px rgba(239,68,68,0.4); }
        .btn-permissions { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; margin: 2px; transition: 0.3s; }
        .btn-permissions:hover { transform: translateY(-2px); box-shadow: 0 3px 10px rgba(139,92,246,0.4); }
        .badge-admin { background: #4f46e5; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .badge-cashier { background: #10b981; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .badge-stock_manager { background: #f59e0b; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .status-active { background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .status-inactive { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .actions-cell { white-space: nowrap; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
            .sidebar.active { transform: translateX(0); }
        }
        @media (max-width: 768px) {
            .data-table th, .data-table td { padding: 8px 10px; font-size: 0.8rem; }
            .btn-edit, .btn-delete, .btn-permissions { padding: 4px 8px; font-size: 0.65rem; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>🚗 Adam Car</h3>
            <p>Accessories System</p>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
            <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
            <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
            <a href="users.php" class="menu-item active"><i class="fas fa-users"></i> Users</a>
            <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
            <div class="menu-divider"></div>
            <a href="logout.php" class="menu-item" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title"><i class="fas fa-users me-2"></i>User Management</h1>
            <div class="user-info">
                <span class="badge bg-primary"><?= ucfirst($_SESSION['role'] ?? 'Admin') ?></span>
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
                <span class="fw-semibold"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">All System Users</h5>
            <?php if(hasPermission('users_add') || hasRole('admin')): ?>
                <a href="add_user.php" class="btn-add"><i class="fas fa-user-plus"></i> Add New User</a>
            <?php endif; ?>
        </div>
        
        <div class="data-table">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="actions-cell">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($users) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></strong>
                                    <br><small class="text-muted">Created: <?= date('M d, Y', strtotime($row['created_at'] ?? 'now')) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <?php if($row['role'] == 'admin'): ?>
                                        <span class="badge-admin"><i class="fas fa-crown me-1"></i> Admin</span>
                                    <?php elseif($row['role'] == 'cashier'): ?>
                                        <span class="badge-cashier"><i class="fas fa-user-tie me-1"></i> Cashier</span>
                                    <?php else: ?>
                                        <span class="badge-stock_manager"><i class="fas fa-boxes me-1"></i> Stock Manager</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php 
                                        $perm_count = $row['permissions'] ? count(explode(',', $row['permissions'])) : 0;
                                        echo $perm_count . ' permissions';
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if($row['is_active']): ?>
                                        <span class="status-active"><i class="fas fa-check-circle me-1"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-inactive"><i class="fas fa-times-circle me-1"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <?php if(hasPermission('users_edit') || hasRole('admin')): ?>
                                        <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <?php endif; ?>
                                    
                                    <?php if(hasPermission('users_permissions') || hasRole('admin')): ?>
                                        <a href="user_permissions.php?id=<?= $row['id'] ?>" class="btn-permissions"><i class="fas fa-key"></i> Permissions</a>
                                    <?php endif; ?>
                                    
                                    <?php if(($row['id'] != $_SESSION['user_id']) && (hasPermission('users_delete') || hasRole('admin'))): ?>
                                        <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Delete this user? This action cannot be undone!')"><i class="fas fa-trash"></i> Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                                    <p>No users found</p>
                                    <a href="add_user.php" class="btn-add">Add First User</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Stats Footer -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Total Users:</strong> <?= mysqli_num_rows($users) ?>
                    </div>
                    <div>
                        <i class="fas fa-key me-2"></i>
                        <strong>Permission System:</strong> Granular CRUD-based permissions
                    </div>
                    <div>
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Admin:</strong> Full access to everything
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu toggle button -->
    <button id="menuToggle" style="position: fixed; bottom: 20px; right: 20px; background: #4f46e5; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; display: none; z-index: 1001; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <i class="fas fa-bars"></i>
    </button>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        function checkScreenSize() {
            if (window.innerWidth <= 992) {
                menuToggle.style.display = 'block';
                sidebar.classList.remove('active');
            } else {
                menuToggle.style.display = 'none';
                sidebar.classList.add('active');
            }
        }
        
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
        
        if(menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
    </script>
</body>
</html>