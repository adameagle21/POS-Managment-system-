<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

// Permission check
if(!hasPermission('categories_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// Handle add category
if(isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    // Check if category already exists
    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$name'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['cat_msg'] = "<div class='alert alert-warning'>⚠️ Category '$name' already exists!</div>";
    } else {
        $query = "INSERT INTO categories (name, description, icon, color, is_active, display_order) 
                  VALUES ('$name', '$description', '$icon', '$color', '$is_active', '$display_order')";
        if(mysqli_query($conn, $query)) {
            $_SESSION['cat_msg'] = "<div class='alert alert-success'>✓ Category added successfully!</div>";
        } else {
            $_SESSION['cat_msg'] = "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
        }
    }
    header("Location: categories.php");
    exit();
}

// Handle edit category
if(isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    // Check if name already exists for other category
    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$name' AND id != $id");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['cat_msg'] = "<div class='alert alert-warning'>⚠️ Category '$name' already exists!</div>";
    } else {
        $query = "UPDATE categories SET 
                  name='$name', 
                  description='$description', 
                  icon='$icon', 
                  color='$color', 
                  is_active='$is_active', 
                  display_order='$display_order' 
                  WHERE id=$id";
        if(mysqli_query($conn, $query)) {
            $_SESSION['cat_msg'] = "<div class='alert alert-success'>✓ Category updated successfully!</div>";
        } else {
            $_SESSION['cat_msg'] = "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
        }
    }
    header("Location: categories.php");
    exit();
}

// Handle toggle status (active/inactive)
if(isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM categories WHERE id = $id"));
    $new_status = $current['is_active'] ? 0 : 1;
    mysqli_query($conn, "UPDATE categories SET is_active = $new_status WHERE id = $id");
    $_SESSION['cat_msg'] = "<div class='alert alert-success'>✓ Category status updated!</div>";
    header("Location: categories.php");
    exit();
}

// Handle delete category
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if category has products
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = $id");
    $count = mysqli_fetch_assoc($check);
    
    if($count['count'] > 0) {
        $_SESSION['cat_msg'] = "<div class='alert alert-warning'>⚠️ Cannot delete: This category has {$count['count']} products. Move or delete products first.</div>";
    } else {
        mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
        $_SESSION['cat_msg'] = "<div class='alert alert-success'>✓ Category deleted successfully!</div>";
    }
    header("Location: categories.php");
    exit();
}

// Get all categories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY display_order ASC, name ASC");
$total_categories = mysqli_num_rows($categories);
$active_categories = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM categories WHERE is_active = 1"));

// Icon options
$icon_options = [
    'fa-tag', 'fa-tags', 'fa-car', 'fa-chair', 'fa-hand-peace', 'fa-wind', 
    'fa-spray-can', 'fa-battery-full', 'fa-oil-can', 'fa-tools', 'fa-broom',
    'fa-fan', 'fa-snowplow', 'fa-music', 'fa-plug', 'fa-mobile-alt', 'fa-clock'
];

// Color options
$color_options = [
    '#4f46e5' => 'Indigo',
    '#3b82f6' => 'Blue',
    '#10b981' => 'Green',
    '#f59e0b' => 'Amber',
    '#ef4444' => 'Red',
    '#8b5cf6' => 'Purple',
    '#ec489a' => 'Pink',
    '#06b6d4' => 'Cyan',
    '#14b8a6' => 'Teal',
    '#f97316' => 'Orange'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;}
        .sidebar{width:280px;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);position:fixed;height:100vh;padding:20px 0;overflow-y:auto;}
        .sidebar-header{text-align:center;padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px;}
        .sidebar-header h3{color:#FFD700;}
        .sidebar-header p{color:#94a3b8;font-size:0.75rem;}
        .sidebar-menu{padding:0 15px;}
        .menu-item{display:flex;align-items:center;gap:12px;padding:12px 18px;margin:5px 0;color:#cbd5e1;text-decoration:none;border-radius:12px;transition:0.3s;}
        .menu-item:hover{background:rgba(255,255,255,0.1);color:white;transform:translateX(5px);}
        .menu-item i{width:22px;}
        .menu-item.active{background:rgba(79,70,229,0.2);color:white;}
        .menu-divider{height:1px;background:rgba(255,255,255,0.1);margin:15px 18px;}
        .main-content{margin-left:280px;padding:20px;min-height:100vh;}
        .top-bar{background:white;border-radius:16px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .page-title{font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;}
        .user-avatar{width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;}
        .stat-card{background:white;border-radius:20px;padding:20px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);transition:0.3s;}
        .stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
        .stat-value{font-size:2rem;font-weight:800;}
        .stat-label{color:#64748b;font-size:0.85rem;}
        .btn-add{background:#10b981;color:white;border:none;padding:10px 20px;border-radius:12px;display:inline-flex;align-items:center;gap:8px;}
        .btn-add:hover{background:#059669;color:white;}
        .btn-edit{background:#f59e0b;color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.75rem;margin:2px;cursor:pointer;}
        .btn-edit:hover{background:#d97706;}
        .btn-delete{background:#ef4444;color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.75rem;margin:2px;cursor:pointer;}
        .btn-delete:hover{background:#dc2626;}
        .btn-toggle{background:#64748b;color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.75rem;margin:2px;cursor:pointer;}
        .data-table{background:white;border-radius:20px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .data-table th{background:#f8fafc;padding:15px;font-weight:600;border-bottom:2px solid #e2e8f0;}
        .data-table td{padding:12px 15px;border-bottom:1px solid #e2e8f0;vertical-align:middle;}
        .badge-product-count{background:#e0e7ff;color:#4f46e5;padding:4px 10px;border-radius:30px;font-size:0.7rem;}
        .badge-active{background:#d1fae5;color:#059669;padding:4px 10px;border-radius:30px;font-size:0.7rem;}
        .badge-inactive{background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:30px;font-size:0.7rem;}
        .category-icon{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;}
        .modal-custom{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;}
        .modal-content{background:white;border-radius:24px;padding:25px;width:550px;max-width:90%;animation:slideIn 0.3s ease;max-height:85vh;overflow-y:auto;}
        @keyframes slideIn{from{transform:translateY(-30px);opacity:0;}to{transform:translateY(0);opacity:1;}}
        .color-option{width:30px;height:30px;border-radius:8px;display:inline-block;margin:5px;cursor:pointer;border:2px solid transparent;}
        .color-option.selected{border-color:#1e293b;transform:scale(1.1);}
        @media (max-width:992px){.sidebar{transform:translateX(-100%);position:fixed;z-index:1000;}.main-content{margin-left:0;}.sidebar.active{transform:translateX(0);}}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>🚗 Adam Car</h3><p>Accessories System</p></div>
    <div class="sidebar-menu">
        <?php if(hasPermission('dashboard_access') || hasRole('admin')): ?>
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php endif; ?>
        <?php if(hasPermission('pos_access') || hasRole('admin')): ?>
        <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
        <?php endif; ?>
        <?php if(hasPermission('products_access') || hasRole('admin')): ?>
        <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
        <?php endif; ?>
        <?php if(hasPermission('sales_access') || hasRole('admin')): ?>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
        <?php endif; ?>
        <?php if(hasPermission('discounts_access') || hasRole('admin')): ?>
        <a href="discounts.php" class="menu-item"><i class="fas fa-tag"></i> Discounts</a>
        <?php endif; ?>
        <?php if(hasPermission('categories_access') || hasRole('admin')): ?>
        <a href="categories.php" class="menu-item active"><i class="fas fa-tags"></i> Categories</a>
        <?php endif; ?>
        <?php if(hasPermission('expenses_access') || hasRole('admin')): ?>
        <a href="expenses.php" class="menu-item"><i class="fas fa-receipt"></i> Expenses</a>
        <?php endif; ?>
        <?php if(hasPermission('users_access') || hasRole('admin')): ?>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <?php endif; ?>
        <?php if(hasPermission('reports_access') || hasRole('admin')): ?>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <?php endif; ?>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-tags me-2"></i>Categories Management</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['cat_msg'])): echo $_SESSION['cat_msg']; unset($_SESSION['cat_msg']); endif; ?>
    
    <!-- Stats Row -->
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color:#4f46e5;"><?= $total_categories ?></div>
                <div class="stat-label">Total Categories</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color:#10b981;"><?= $active_categories ?></div>
                <div class="stat-label">Active Categories</div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn-add" onclick="showAddModal()"><i class="fas fa-plus"></i> Add Category</button>
        </div>
    </div>
    
    <!-- Categories Table -->
    <div class="data-table">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Icon</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($categories) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($categories)): 
                            $product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = {$row['id']}"))['count'];
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <div class="category-icon" style="background:<?= $row['color'] ?>20; color:<?= $row['color'] ?>;">
                                    <i class="fas <?= $row['icon'] ?>"></i>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong>
                                <br><small class="text-muted">Order: <?= $row['display_order'] ?></small>
                            </td>
                            <td><?= htmlspecialchars(substr($row['description'] ?? '', 0, 50)) ?><?= strlen($row['description'] ?? '') > 50 ? '...' : '' ?></td>
                            <td><span class="badge-product-count"><i class="fas fa-box me-1"></i><?= $product_count ?></span></td>
                            <td>
                                <?php if($row['is_active']): ?>
                                    <span class="badge-active"><i class="fas fa-check-circle me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive"><i class="fas fa-times-circle me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['display_order'] ?></td>
                            <td>
                                <button class="btn-edit" onclick="editCategory(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', '<?= addslashes($row['description']) ?>', '<?= $row['icon'] ?>', '<?= $row['color'] ?>', <?= $row['is_active'] ?>, <?= $row['display_order'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-toggle" onclick="toggleStatus(<?= $row['id'] ?>)"><i class="fas fa-power-off"></i> <?= $row['is_active'] ? 'Disable' : 'Enable' ?></button>
                                <?php if($product_count == 0): ?>
                                    <button class="btn-delete" onclick="deleteCategory(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')"><i class="fas fa-trash"></i> Delete</button>
                                <?php else: ?>
                                    <button class="btn-delete" disabled style="opacity:0.5; cursor:not-allowed;" title="Has <?= $product_count ?> products"><i class="fas fa-trash"></i> Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">No categories found. Click "Add Category" to create one.<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="modal-custom">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-plus-circle me-2 text-success"></i>Add New Category</h5>
            <button onclick="closeModal('addModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
            <div class="mb-3">
                <label>Category Name *</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g., Car Mats">
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Category description..."></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Icon</label>
                        <select name="icon" class="form-control">
                            <?php foreach($icon_options as $icon): ?>
                                <option value="<?= $icon ?>"><i class="fas <?= $icon ?>"></i> <?= $icon ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label>Color</label>
                <div class="d-flex flex-wrap">
                    <?php foreach($color_options as $code => $name): ?>
                        <div class="color-option" style="background:<?= $code ?>;" onclick="selectColor(this, '<?= $code ?>')"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="color" id="selectedColor" value="#4f46e5">
            </div>
            <div class="mb-3">
                <label class="d-flex align-items-center">
                    <input type="checkbox" name="is_active" checked style="width:20px;height:20px;margin-right:10px;">
                    <span>Active (Visible in POS and Products)</span>
                </label>
            </div>
            <button type="submit" name="add_category" class="btn btn-primary w-100">Save Category</button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal-custom">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-edit me-2 text-warning"></i>Edit Category</h5>
            <button onclick="closeModal('editModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
                <label>Category Name *</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Icon</label>
                        <select name="icon" id="edit_icon" class="form-control">
                            <?php foreach($icon_options as $icon): ?>
                                <option value="<?= $icon ?>"><i class="fas <?= $icon ?>"></i> <?= $icon ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" class="form-control">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label>Color</label>
                <div class="d-flex flex-wrap" id="colorOptions">
                    <?php foreach($color_options as $code => $name): ?>
                        <div class="color-option" style="background:<?= $code ?>;" onclick="selectEditColor(this, '<?= $code ?>')"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="color" id="edit_color" value="#4f46e5">
            </div>
            <div class="mb-3">
                <label class="d-flex align-items-center">
                    <input type="checkbox" name="is_active" id="edit_is_active" style="width:20px;height:20px;margin-right:10px;">
                    <span>Active</span>
                </label>
            </div>
            <button type="submit" name="edit_category" class="btn btn-primary w-100">Update Category</button>
        </form>
    </div>
</div>

<script>
// Color selection for add modal
function selectColor(element, colorCode) {
    document.querySelectorAll('#addModal .color-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedColor').value = colorCode;
}

// Color selection for edit modal
function selectEditColor(element, colorCode) {
    document.querySelectorAll('#editModal .color-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('edit_color').value = colorCode;
}

// Show add modal
function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    // Reset color selection
    setTimeout(() => {
        let firstColor = document.querySelector('#addModal .color-option');
        if(firstColor) selectColor(firstColor, '#4f46e5');
    }, 100);
}

// Edit category function
function editCategory(id, name, description, icon, color, isActive, displayOrder) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('edit_display_order').value = displayOrder;
    document.getElementById('edit_color').value = color;
    document.getElementById('edit_is_active').checked = isActive == 1;
    
    // Highlight selected color in edit modal
    document.querySelectorAll('#editModal .color-option').forEach(opt => {
        if(opt.style.backgroundColor === color || opt.style.background === color) {
            opt.classList.add('selected');
        } else {
            opt.classList.remove('selected');
        }
    });
    
    document.getElementById('editModal').style.display = 'flex';
}

// Toggle status
function toggleStatus(id) {
    Swal.fire({
        title: 'Change Status?',
        text: "This will enable/disable the category",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        confirmButtonText: 'Yes, change'
    }).then((result) => {
        if(result.isConfirmed) {
            window.location.href = '?toggle_status=' + id;
        }
    });
}

// Delete category
function deleteCategory(id, name) {
    Swal.fire({
        title: 'Delete Category?',
        text: `Are you sure you want to delete "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if(result.isConfirmed) {
            window.location.href = '?delete=' + id;
        }
    });
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if(event.target.classList.contains('modal-custom')) {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>