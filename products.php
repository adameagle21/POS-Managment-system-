<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

// ============================================
// PERMISSION CHECKS
// ============================================
if(!hasPermission('products_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// ============================================
// DELETE ALL PRODUCTS (ONLY FOR ADMIN) - ONE CONFIRMATION
// ============================================
if(isset($_POST['delete_all']) && (hasRole('admin') || hasPermission('products_delete_all'))) {
    // Delete all products from database
    $delete_query = "DELETE FROM products";
    if(mysqli_query($conn, $delete_query)) {
        // Optional: Reset auto increment
        mysqli_query($conn, "ALTER TABLE products AUTO_INCREMENT = 1");
        echo "<script>alert('✅ All products have been deleted successfully!'); window.location.href='products.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Error deleting products: " . mysqli_error($conn) . "');</script>";
    }
}

// Get filter values
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$view_mode = $_GET['view'] ?? 'table';

// Build query with discount info
$products_query = "SELECT p.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
                   CASE 
                       WHEN p.discount_price IS NOT NULL AND p.discount_expiry >= CURDATE() THEN p.discount_price
                       ELSE NULL
                   END as active_discount_price,
                   CASE 
                       WHEN p.discount_price IS NOT NULL AND p.discount_expiry >= CURDATE() THEN p.discount_percent
                       ELSE NULL
                   END as active_discount_percent
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE 1=1";

if($search) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $products_query .= " AND (p.product_name LIKE '%$search_term%' OR p.sku LIKE '%$search_term%')";
}

if($category_filter) {
    $products_query .= " AND c.name = '$category_filter'";
}

$products_query .= " ORDER BY p.id DESC";
$products = mysqli_query($conn, $products_query);

// Get all categories for filter dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$total_products = mysqli_num_rows($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:linear-gradient(135deg, #f0f2f5 0%, #e8ecf1 100%);font-family:'Inter',sans-serif;}
        
        /* Sidebar Styles */
        .sidebar{width:280px;background:linear-gradient(180deg,#0f172a 0%,#1e1b4b 100%);position:fixed;height:100vh;padding:20px 0;overflow-y:auto;z-index:100;box-shadow:5px 0 25px rgba(0,0,0,0.1);}
        .sidebar-header{text-align:center;padding:0 20px 25px;border-bottom:1px solid rgba(255,255,255,0.08);margin-bottom:20px;}
        .sidebar-header h3{font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,#FFD700,#FFA500);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .sidebar-header p{color:#94a3b8;font-size:0.7rem;margin-top:5px;}
        .sidebar-menu{padding:0 15px;}
        .menu-item{display:flex;align-items:center;gap:12px;padding:12px 18px;margin:5px 0;color:#cbd5e1;text-decoration:none;border-radius:12px;transition:0.3s;font-weight:500;font-size:0.9rem;}
        .menu-item:hover{background:rgba(255,255,255,0.1);color:white;transform:translateX(5px);}
        .menu-item i{width:22px;}
        .menu-item.active{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;box-shadow:0 4px 12px rgba(79,70,229,0.3);}
        .menu-divider{height:1px;background:rgba(255,255,255,0.08);margin:15px 18px;}
        
        /* Main Content */
        .main-content{margin-left:280px;padding:25px;min-height:100vh;}
        
        /* Top Bar */
        .top-bar{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);border-radius:20px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid rgba(255,255,255,0.5);}
        .page-title{font-size:1.5rem;font-weight:700;background:linear-gradient(135deg,#1e293b,#334155);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0;}
        .user-avatar{width:42px;height:42px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1rem;box-shadow:0 4px 10px rgba(79,70,229,0.3);}
        
        /* Filter Section */
        .filter-section{background:white;border-radius:20px;padding:25px;margin-bottom:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.03);}
        .filter-input{border:2px solid #e2e8f0;border-radius:12px;padding:12px 16px;width:100%;transition:0.3s;font-size:0.9rem;}
        .filter-input:focus{border-color:#4f46e5;outline:none;box-shadow:0 0 0 3px rgba(79,70,229,0.1);}
        .btn-filter{background:linear-gradient(135deg,#4f46e5,#4338ca);color:white;border:none;padding:12px 24px;border-radius:12px;font-weight:600;transition:0.3s;}
        .btn-filter:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(79,70,229,0.4);}
        .btn-reset{background:#64748b;color:white;border:none;padding:12px 24px;border-radius:12px;font-weight:600;text-decoration:none;display:inline-block;text-align:center;transition:0.3s;}
        .btn-reset:hover{background:#475569;transform:translateY(-2px);}
        
        /* Action Buttons */
        .btn-add{background:linear-gradient(135deg,#10b981,#059669);color:white;padding:10px 20px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:0.3s;}
        .btn-add:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(16,185,129,0.4);color:white;}
        .btn-import{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:10px 20px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:0.3s;}
        .btn-import:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(245,158,11,0.4);color:white;}
        .btn-export{background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white;padding:10px 20px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:0.3s;}
        .btn-export:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(59,130,246,0.4);color:white;}
        .btn-delete-all{background:linear-gradient(135deg,#dc2626,#991b1b);color:white;padding:10px 20px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:0.3s;border:none;cursor:pointer;}
        .btn-delete-all:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(220,38,38,0.4);}
        
        /* View Toggle */
        .view-toggle{background:#f1f5f9;border-radius:12px;padding:4px;display:inline-flex;gap:5px;}
        .view-btn{padding:6px 15px;border-radius:10px;border:none;background:transparent;cursor:pointer;transition:0.3s;font-weight:500;}
        .view-btn.active{background:white;box-shadow:0 2px 8px rgba(0,0,0,0.1);color:#4f46e5;}
        
        /* Table View */
        .data-table{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);}
        .data-table th{background:#f8fafc;padding:16px;font-weight:600;border-bottom:2px solid #e2e8f0;color:#1e293b;}
        .data-table td{padding:14px 16px;vertical-align:middle;border-bottom:1px solid #f1f5f9;}
        .data-table tr:hover{background:#f8fafc;}
        
        /* Grid View */
        .product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:20px;}
        .product-card{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);transition:0.3s;position:relative;}
        .product-card:hover{transform:translateY(-5px);box-shadow:0 15px 35px rgba(0,0,0,0.1);}
        .product-img-card{height:180px;display:flex;align-items:center;justify-content:center;background:#f8fafc;position:relative;}
        .product-img-card img{height:140px;width:auto;object-fit:contain;}
        .discount-ribbon{position:absolute;top:10px;right:10px;background:#ef4444;color:white;padding:4px 10px;border-radius:30px;font-size:0.7rem;font-weight:600;}
        .product-info-card{padding:15px;}
        .product-name-card{font-weight:700;font-size:1rem;margin-bottom:5px;color:#1e293b;}
        .product-sku-card{font-size:0.7rem;color:#64748b;margin-bottom:10px;}
        .product-price-card{font-size:1.2rem;font-weight:800;color:#4f46e5;margin-bottom:10px;}
        .product-stock-card{margin-bottom:15px;}
        .product-actions-card{display:flex;gap:8px;border-top:1px solid #e2e8f0;padding-top:12px;}
        
        /* Action Buttons */
        .btn-view{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:white;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:0.75rem;display:inline-flex;align-items:center;gap:6px;transition:0.3s;border:none;cursor:pointer;}
        .btn-view:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(139,92,246,0.4);}
        .btn-edit{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:0.75rem;display:inline-flex;align-items:center;gap:6px;transition:0.3s;border:none;cursor:pointer;}
        .btn-edit:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(245,158,11,0.4);}
        .btn-delete{background:linear-gradient(135deg,#ef4444,#dc2626);color:white;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:0.75rem;display:inline-flex;align-items:center;gap:6px;transition:0.3s;border:none;cursor:pointer;}
        .btn-delete:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(239,68,68,0.4);}
        
        /* Badges */
        .stock-good{background:#d1fae5;color:#059669;padding:4px 12px;border-radius:30px;font-size:0.7rem;display:inline-block;font-weight:600;}
        .stock-low{background:#fed7aa;color:#c2410c;padding:4px 12px;border-radius:30px;font-size:0.7rem;display:inline-block;font-weight:600;}
        .stock-out{background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:30px;font-size:0.7rem;display:inline-block;font-weight:600;}
        .discount-badge{background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:20px;font-size:0.65rem;font-weight:600;display:inline-block;}
        .category-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.7rem;font-weight:500;}
        .product-img{width:50px;height:50px;object-fit:cover;border-radius:10px;background:#f1f5f9;}
        
        /* Mobile */
        .menu-toggle-btn{position:fixed;bottom:20px;right:20px;background:#4f46e5;color:white;border:none;width:50px;height:50px;border-radius:50%;display:none;z-index:1001;cursor:pointer;box-shadow:0 4px 15px rgba(79,70,229,0.4);}
        @media (max-width:992px){.sidebar{transform:translateX(-100%);}.main-content{margin-left:0;}.sidebar.active{transform:translateX(0);}.menu-toggle-btn{display:flex;align-items:center;justify-content:center;}}
        @media (max-width:768px){.main-content{padding:15px;}.product-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <h3>🚗 Adam Car</h3>
        <p>Accessories System</p>
    </div>
    <div class="sidebar-menu">
        <?php if(hasPermission('dashboard_access') || hasRole('admin')): ?>
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php endif; ?>
        <?php if(hasPermission('pos_access') || hasRole('admin')): ?>
        <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
        <?php endif; ?>
        <?php if(hasPermission('products_access') || hasRole('admin')): ?>
        <a href="products.php" class="menu-item active"><i class="fas fa-boxes"></i> Products</a>
        <?php endif; ?>
        <?php if(hasPermission('sales_access') || hasRole('admin')): ?>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
        <?php endif; ?>
        <?php if(hasPermission('discounts_access') || hasRole('admin')): ?>
        <a href="discounts.php" class="menu-item"><i class="fas fa-tag"></i> Discounts</a>
        <?php endif; ?>
        <?php if(hasPermission('categories_access') || hasRole('admin')): ?>
        <a href="categories.php" class="menu-item"><i class="fas fa-tags"></i> Categories</a>
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

<button class="menu-toggle-btn" id="menuToggle"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-boxes me-2"></i>Products Management</h1>
        <div style="display:flex;align-items:center;gap:15px;">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold"><i class="fas fa-search me-1"></i>Search Product</label>
                <input type="text" name="search" class="filter-input" placeholder="Search by name or SKU..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold"><i class="fas fa-filter me-1"></i>Filter by Category</label>
                <select name="category" class="filter-input">
                    <option value="">All Categories</option>
                    <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category_filter == $cat['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter w-100"><i class="fas fa-filter me-2"></i>Filter</button>
                    <a href="products.php" class="btn-reset w-100"><i class="fas fa-sync-alt me-2"></i>Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0"><i class="fas fa-database me-1"></i> All Products <span class="text-muted">(<?= $total_products ?> items)</span></h5>
            <div class="view-toggle">
                <button type="button" class="view-btn <?= $view_mode == 'table' ? 'active' : '' ?>" onclick="setView('table')"><i class="fas fa-table"></i> Table</button>
                <button type="button" class="view-btn <?= $view_mode == 'grid' ? 'active' : '' ?>" onclick="setView('grid')"><i class="fas fa-th-large"></i> Grid</button>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if(hasPermission('products_import') || hasRole('admin')): ?>
            <a href="import_products.php" class="btn-import"><i class="fas fa-file-import"></i> Import</a>
            <?php endif; ?>
            <?php if(hasPermission('products_export') || hasRole('admin')): ?>
            <a href="export_products.php" class="btn-export"><i class="fas fa-file-export"></i> Export</a>
            <?php endif; ?>
            <?php if(hasPermission('products_add') || hasRole('admin')): ?>
            <a href="add_product.php" class="btn-add"><i class="fas fa-plus"></i> Add Product</a>
            <?php endif; ?>
            <?php if(hasRole('admin') || hasPermission('products_delete_all')): ?>
            <form method="POST" onsubmit="return confirmDeleteAll()" style="display:inline;">
                <button type="submit" name="delete_all" class="btn-delete-all" id="deleteAllBtn"><i class="fas fa-trash-alt"></i> Delete All</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if($view_mode == 'table'): ?>
    <!-- Table View -->
    <div class="data-table">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Info</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Prices</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($products) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($products)):
                            $stock_class = $row['quantity'] <= 0 ? 'stock-out' : ($row['quantity'] <= $row['alert_quantity'] ? 'stock-low' : 'stock-good');
                            $stock_text = $row['quantity'] <= 0 ? 'Out of Stock' : ($row['quantity'] <= $row['alert_quantity'] ? 'Low Stock' : 'In Stock');
                            $has_discount = $row['active_discount_price'] && $row['active_discount_price'] < $row['price_selling'];
                            $display_price = $has_discount ? $row['active_discount_price'] : $row['price_selling'];
                        ?>
                        <tr>
                            <td>
                                <?php if($row['image'] && file_exists('assets/uploads/'.$row['image'])): ?>
                                    <img src="assets/uploads/<?= $row['image'] ?>" class="product-img">
                                <?php else: ?>
                                    <div class="product-img" style="display:flex;align-items:center;justify-content:center;background:#f1f5f9;">
                                        <i class="fas fa-box text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                <br><small class="text-muted"><i class="fas fa-barcode"></i> <?= $row['sku'] ?></small>
                                <br><small class="text-muted"><i class="fas fa-cube"></i> <?= ucfirst($row['unit']) ?></small>
                            </td>
                            <td>
                                <?php if($row['category_color']): ?>
                                    <span class="category-badge" style="background:<?= $row['category_color'] ?>20; color:<?= $row['category_color'] ?>;">
                                        <i class="fas <?= $row['category_icon'] ?? 'fa-tag' ?>"></i> <?= $row['category_name'] ?? 'Uncategorized' ?>
                                    </span>
                                <?php else: ?>
                                    <?= $row['category_name'] ?? 'Uncategorized' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= $stock_class ?>"><?= $row['quantity'] ?> units</span>
                                <br><small><?= $stock_text ?></small>
                                <?php if($row['quantity'] <= $row['alert_quantity']): ?>
                                    <br><a href="edit_product.php?id=<?= $row['id'] ?>" class="text-danger small"><i class="fas fa-truck"></i> Restock</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($has_discount): ?>
                                    <span class="discount-badge"><i class="fas fa-tag"></i> <?= $row['active_discount_percent'] ?>% OFF</span><br>
                                    <span style="text-decoration:line-through; color:#94a3b8; font-size:0.7rem;">$<?= number_format($row['price_selling'],2) ?></span><br>
                                    <strong class="text-danger">$<?= number_format($display_price,2) ?></strong>
                                <?php else: ?>
                                    <strong class="text-primary">$<?= number_format($row['price_selling'],2) ?></strong>
                                <?php endif; ?>
                                <br><small class="text-muted">Reg: $<?= number_format($row['price_regular'],2) ?></small>
                            </td>
                            <td>
                                <a href="view_product.php?id=<?= $row['id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View</a>
                                <?php if(hasPermission('products_edit') || hasRole('admin')): ?>
                                <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <?php endif; ?>
                                <?php if(hasPermission('products_delete') || hasRole('admin')): ?>
                                <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-box-open fa-3x mb-3 opacity-50"></i><p>No products found</p><?php if(hasPermission('products_add') || hasRole('admin')): ?><a href="add_product.php" class="btn-add">Add First Product</a><?php endif; ?></td>?</p><?php if(hasRole('admin') && $total_products == 0): ?><p class="mt-2 small text-muted">Table is empty. Click "Add Product" to get started.</p><?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <!-- Grid View -->
    <div class="product-grid">
        <?php if(mysqli_num_rows($products) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($products)):
                $stock_class = $row['quantity'] <= 0 ? 'stock-out' : ($row['quantity'] <= $row['alert_quantity'] ? 'stock-low' : 'stock-good');
                $stock_text = $row['quantity'] <= 0 ? 'Out of Stock' : ($row['quantity'] <= $row['alert_quantity'] ? 'Low Stock' : 'In Stock');
                $has_discount = $row['active_discount_price'] && $row['active_discount_price'] < $row['price_selling'];
                $display_price = $has_discount ? $row['active_discount_price'] : $row['price_selling'];
            ?>
            <div class="product-card">
                <?php if($has_discount): ?>
                    <div class="discount-ribbon"><?= $row['active_discount_percent'] ?>% OFF</div>
                <?php endif; ?>
                <div class="product-img-card">
                    <?php if($row['image'] && file_exists('assets/uploads/'.$row['image'])): ?>
                        <img src="assets/uploads/<?= $row['image'] ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-box fa-4x text-muted"></i>
                    <?php endif; ?>
                </div>
                <div class="product-info-card">
                    <div class="product-name-card"><?= htmlspecialchars($row['product_name']) ?></div>
                    <div class="product-sku-card"><i class="fas fa-barcode"></i> <?= $row['sku'] ?> | <i class="fas fa-cube"></i> <?= ucfirst($row['unit']) ?></div>
                    <?php if($row['category_name']): ?>
                        <div class="mb-2"><span class="category-badge" style="background:<?= $row['category_color'] ?>20; color:<?= $row['category_color'] ?>;"><i class="fas <?= $row['category_icon'] ?? 'fa-tag' ?>"></i> <?= $row['category_name'] ?></span></div>
                    <?php endif; ?>
                    <div class="product-price-card">
                        <?php if($has_discount): ?>
                            <span style="text-decoration:line-through; font-size:0.8rem; color:#94a3b8;">$<?= number_format($row['price_selling'],2) ?></span><br>
                            $<?= number_format($display_price,2) ?>
                        <?php else: ?>
                            $<?= number_format($row['price_selling'],2) ?>
                        <?php endif; ?>
                    </div>
                    <div class="product-stock-card">
                        <span class="<?= $stock_class ?>"><i class="fas fa-boxes"></i> <?= $row['quantity'] ?> units - <?= $stock_text ?></span>
                    </div>
                    <div class="product-actions-card">
                        <a href="view_product.php?id=<?= $row['id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View</a>
                        <?php if(hasPermission('products_edit') || hasRole('admin')): ?>
                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <?php endif; ?>
                        <?php if(hasPermission('products_delete') || hasRole('admin')): ?>
                        <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5" style="grid-column:1/-1;"><i class="fas fa-box-open fa-3x mb-3 opacity-50"></i><p>No products found</p><a href="add_product.php" class="btn-add">Add First Product</a><?php if(hasRole('admin') && $total_products == 0): ?><p class="mt-2 small text-muted">Table is empty. Click "Add Product" to get started.</p><?php endif; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Delete All confirmation - ONE TIME ONLY
function confirmDeleteAll() {
    return confirm('⚠️ Are you sure you want to DELETE ALL products? This action CANNOT be undone!');
}

// View mode toggle
function setView(view) {
    let url = new URL(window.location.href);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
}

// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');
function checkScreenSize() {
    if (window.innerWidth <= 992) {
        if(menuToggle) menuToggle.style.display = 'flex';
        if(sidebar) sidebar.classList.remove('active');
    } else {
        if(menuToggle) menuToggle.style.display = 'none';
        if(sidebar) sidebar.classList.add('active');
    }
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
if(menuToggle) {
    menuToggle.addEventListener('click', function() { sidebar.classList.toggle('active'); });
}
document.addEventListener('click', function(event) {
    if(window.innerWidth <= 992) {
        if(sidebar && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});
</script>
</body>
</html>