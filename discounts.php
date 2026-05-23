<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
if(!hasPermission('sales_access') && !hasRole('admin')) header("Location: index.php");

// ============================================
// HANDLE PRODUCT DISCOUNTS ONLY
// ============================================

// Handle add product discount
if(isset($_POST['add_product_discount'])) {
    $product_id = $_POST['product_id'];
    $discount_type = $_POST['discount_type'];
    $discount_amount = $_POST['discount_amount'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : 'NULL';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : 'NULL';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_selling FROM products WHERE id = $product_id"));
    $original_price = $product['price_selling'];
    
    if($discount_type == 'percentage') {
        $discount_price = $original_price - ($original_price * $discount_amount / 100);
    } else {
        $discount_price = $original_price - $discount_amount;
    }
    if($discount_price < 0) $discount_price = 0;
    
    $start_date_sql = ($start_date != 'NULL') ? "'$start_date'" : "NULL";
    $end_date_sql = ($end_date != 'NULL') ? "'$end_date'" : "NULL";
    
    $query = "INSERT INTO product_discounts (product_id, discount_type, discount_amount, start_date, end_date, is_active) 
              VALUES ('$product_id', '$discount_type', '$discount_amount', $start_date_sql, $end_date_sql, '$is_active')";
    
    if(mysqli_query($conn, $query)) {
        $discount_percent = ($discount_type == 'percentage') ? $discount_amount : 'NULL';
        $update = "UPDATE products SET 
                   discount_price = '$discount_price',
                   discount_percent = $discount_percent,
                   discount_expiry = $end_date_sql
                   WHERE id = '$product_id'";
        mysqli_query($conn, $update);
        $_SESSION['discount_msg'] = "<div class='alert alert-success'>✓ Product discount added successfully!</div>";
    } else {
        $_SESSION['discount_msg'] = "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
    }
    header("Location: discounts.php");
    exit();
}

// Handle edit product discount - FIXED
if(isset($_POST['edit_product_discount'])) {
    $discount_id = $_POST['discount_id'];
    $discount_type = $_POST['discount_type'];
    $discount_amount = $_POST['discount_amount'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : 'NULL';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : 'NULL';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get product_id from discount
    $discount_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_id FROM product_discounts WHERE id = $discount_id"));
    if($discount_info) {
        $product_id = $discount_info['product_id'];
        
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_selling FROM products WHERE id = $product_id"));
        $original_price = $product['price_selling'];
        
        if($discount_type == 'percentage') {
            $discount_price = $original_price - ($original_price * $discount_amount / 100);
        } else {
            $discount_price = $original_price - $discount_amount;
        }
        if($discount_price < 0) $discount_price = 0;
        
        $start_date_sql = ($start_date != 'NULL') ? "'$start_date'" : "NULL";
        $end_date_sql = ($end_date != 'NULL') ? "'$end_date'" : "NULL";
        
        $query = "UPDATE product_discounts SET 
                  discount_type='$discount_type', 
                  discount_amount='$discount_amount', 
                  start_date=$start_date_sql, 
                  end_date=$end_date_sql, 
                  is_active='$is_active' 
                  WHERE id=$discount_id";
        
        if(mysqli_query($conn, $query)) {
            $discount_percent = ($discount_type == 'percentage') ? $discount_amount : 'NULL';
            $update = "UPDATE products SET 
                       discount_price = '$discount_price',
                       discount_percent = $discount_percent,
                       discount_expiry = $end_date_sql
                       WHERE id = '$product_id'";
            mysqli_query($conn, $update);
            $_SESSION['discount_msg'] = "<div class='alert alert-success'>✓ Product discount updated successfully!</div>";
        } else {
            $_SESSION['discount_msg'] = "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $_SESSION['discount_msg'] = "<div class='alert alert-danger'>✗ Discount not found!</div>";
    }
    header("Location: discounts.php");
    exit();
}

// Handle delete product discount
if(isset($_GET['delete_product_discount'])) {
    $discount_id = (int)$_GET['delete_product_discount'];
    $discount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_id FROM product_discounts WHERE id = $discount_id"));
    if($discount) {
        $product_id = $discount['product_id'];
        mysqli_query($conn, "DELETE FROM product_discounts WHERE id = $discount_id");
        mysqli_query($conn, "UPDATE products SET discount_price = NULL, discount_percent = NULL, discount_expiry = NULL WHERE id = $product_id");
        $_SESSION['discount_msg'] = "<div class='alert alert-success'>✓ Product discount removed successfully!</div>";
    } else {
        $_SESSION['discount_msg'] = "<div class='alert alert-warning'>⚠ Discount not found!</div>";
    }
    header("Location: discounts.php");
    exit();
}

// Handle toggle product discount status
if(isset($_GET['toggle_product_discount'])) {
    $discount_id = (int)$_GET['toggle_product_discount'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active, product_id, discount_type, discount_amount, end_date FROM product_discounts WHERE id = $discount_id"));
    if($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        mysqli_query($conn, "UPDATE product_discounts SET is_active = $new_status WHERE id = $discount_id");
        
        if($new_status == 1) {
            $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_selling FROM products WHERE id = {$current['product_id']}"));
            $original_price = $product['price_selling'];
            if($current['discount_type'] == 'percentage') {
                $discount_price = $original_price - ($original_price * $current['discount_amount'] / 100);
            } else {
                $discount_price = $original_price - $current['discount_amount'];
            }
            if($discount_price < 0) $discount_price = 0;
            $end_date_sql = $current['end_date'] ? "'{$current['end_date']}'" : "NULL";
            mysqli_query($conn, "UPDATE products SET discount_price = '$discount_price', discount_percent = " . ($current['discount_type'] == 'percentage' ? $current['discount_amount'] : 'NULL') . ", discount_expiry = $end_date_sql WHERE id = {$current['product_id']}");
        } else {
            mysqli_query($conn, "UPDATE products SET discount_price = NULL, discount_percent = NULL, discount_expiry = NULL WHERE id = {$current['product_id']}");
        }
        $_SESSION['discount_msg'] = "<div class='alert alert-success'>✓ Discount status updated!</div>";
    }
    header("Location: discounts.php");
    exit();
}

// Get all product discounts
$product_discounts = mysqli_query($conn, "SELECT pd.*, p.product_name, p.sku, p.price_selling, p.image 
                                          FROM product_discounts pd 
                                          JOIN products p ON pd.product_id = p.id 
                                          ORDER BY pd.id DESC");
$product_discounts_count = mysqli_num_rows($product_discounts);

// Get products for dropdown
$products = mysqli_query($conn, "SELECT id, product_name, sku, price_selling, image FROM products WHERE quantity > 0 ORDER BY product_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Discounts - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;}
        
        /* Sidebar */
        .sidebar{width:280px;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);position:fixed;height:100vh;padding:20px 0;overflow-y:auto;z-index:100;}
        .sidebar-header{text-align:center;padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px;}
        .sidebar-header h3{color:#FFD700;font-weight:700;}
        .sidebar-header p{color:#94a3b8;font-size:0.75rem;}
        .sidebar-menu{padding:0 15px;}
        .menu-item{display:flex;align-items:center;gap:12px;padding:12px 18px;margin:5px 0;color:#cbd5e1;text-decoration:none;border-radius:12px;transition:0.3s;}
        .menu-item:hover{background:rgba(255,255,255,0.1);color:white;transform:translateX(5px);}
        .menu-item i{width:22px;}
        .menu-item.active{background:rgba(79,70,229,0.2);color:white;}
        .menu-divider{height:1px;background:rgba(255,255,255,0.1);margin:15px 18px;}
        
        /* Main Content */
        .main-content{margin-left:280px;padding:20px;min-height:100vh;}
        .top-bar{background:white;border-radius:20px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .page-title{font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;}
        .user-avatar{width:42px;height:42px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;}
        
        /* Stats */
        .stat-card{background:white;border-radius:20px;padding:25px;margin-bottom:20px;box-shadow:0 4px 15px rgba(0,0,0,0.05);text-align:center;transition:0.3s;}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
        .stat-value{font-size:2.5rem;font-weight:800;color:#8b5cf6;}
        .stat-label{color:#64748b;font-size:0.85rem;margin-top:5px;}
        
        /* Buttons */
        .btn-add{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:white;border:none;padding:12px 24px;border-radius:12px;font-weight:600;transition:0.3s;}
        .btn-add:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(139,92,246,0.4);}
        .btn-edit{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.7rem;cursor:pointer;transition:0.3s;}
        .btn-edit:hover{transform:translateY(-2px);box-shadow:0 3px 10px rgba(245,158,11,0.4);}
        .btn-delete{background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.7rem;cursor:pointer;transition:0.3s;}
        .btn-delete:hover{transform:translateY(-2px);box-shadow:0 3px 10px rgba(239,68,68,0.4);}
        .btn-toggle{background:#64748b;color:white;border:none;padding:6px 12px;border-radius:8px;font-size:0.7rem;cursor:pointer;transition:0.3s;}
        .btn-toggle:hover{transform:translateY(-2px);background:#475569;}
        
        /* Table */
        .data-table{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);}
        .data-table th{background:#f8fafc;padding:16px;font-weight:600;border-bottom:2px solid #e2e8f0;}
        .data-table td{padding:14px 16px;border-bottom:1px solid #e2e8f0;vertical-align:middle;}
        .data-table tr:hover{background:#f8fafc;}
        
        /* Badges */
        .badge-active{background:#d1fae5;color:#059669;padding:4px 12px;border-radius:30px;font-size:0.7rem;display:inline-block;}
        .badge-inactive{background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:30px;font-size:0.7rem;display:inline-block;}
        .discount-badge{background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:20px;font-size:0.65rem;font-weight:600;}
        .discount-price{color:#10b981;font-weight:bold;font-size:1rem;}
        .original-price{text-decoration:line-through;color:#94a3b8;font-size:0.8rem;}
        .product-img-small{width:45px;height:45px;object-fit:cover;border-radius:10px;background:#f1f5f9;}
        
        /* Modals */
        .modal-custom{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;}
        .modal-content{background:white;border-radius:24px;padding:30px;width:550px;max-width:90%;animation:slideIn 0.3s ease;max-height:85vh;overflow-y:auto;}
        @keyframes slideIn{from{transform:translateY(-30px);opacity:0;}to{transform:translateY(0);opacity:1;}}
        
        /* Mobile */
        .menu-toggle-btn{position:fixed;bottom:20px;right:20px;background:#4f46e5;color:white;border:none;width:50px;height:50px;border-radius:50%;display:none;z-index:1001;cursor:pointer;box-shadow:0 4px 12px rgba(79,70,229,0.4);}
        @media (max-width:992px){.sidebar{transform:translateX(-100%);}.main-content{margin-left:0;}.sidebar.active{transform:translateX(0);}.menu-toggle-btn{display:flex;align-items:center;justify-content:center;}}
        @media (max-width:768px){.data-table th,.data-table td{padding:10px;font-size:0.75rem;}}
        
        /* Price Preview */
        .price-preview{background:#f8fafc;border-radius:12px;padding:15px;margin-top:15px;}
        .preview-original{color:#64748b;}
        .preview-discounted{color:#10b981;font-weight:700;font-size:1.1rem;}
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
        <a href="discounts.php" class="menu-item active"><i class="fas fa-tag"></i> Discounts</a>
        <a href="categories.php" class="menu-item"><i class="fas fa-tags"></i> Categories</a>
        <a href="expenses.php" class="menu-item"><i class="fas fa-receipt"></i> Expenses</a>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<button class="menu-toggle-btn" id="menuToggle"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-tag me-2"></i>Product Discounts Management</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['discount_msg'])): echo $_SESSION['discount_msg']; unset($_SESSION['discount_msg']); endif; ?>
    
    <!-- Stats Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-value"><?= $product_discounts_count ?></div>
                <div class="stat-label">Products on Discount</div>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn-add" onclick="showAddDiscountModal()"><i class="fas fa-plus-circle me-2"></i> Add Product Discount</button>
        </div>
    </div>
    
    <!-- Product Discounts Table -->
    <div class="data-table mt-3">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Discount Type</th>
                        <th>Discount Amount</th>
                        <th>Original Price</th>
                        <th>Discounted Price</th>
                        <th>Valid Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($product_discounts && mysqli_num_rows($product_discounts) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($product_discounts)):
                            $discounted = $row['discount_type'] == 'percentage' 
                                ? $row['price_selling'] - ($row['price_selling'] * $row['discount_amount'] / 100)
                                : $row['price_selling'] - $row['discount_amount'];
                            $discounted = $discounted < 0 ? 0 : $discounted;
                            $saved = $row['price_selling'] - $discounted;
                        ?>
                        <tr>
                            <td class="fw-bold">#<?= $row['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if($row['image'] && file_exists('assets/uploads/'.$row['image'])): ?>
                                        <img src="assets/uploads/<?= $row['image'] ?>" class="product-img-small">
                                    <?php else: ?>
                                        <div class="product-img-small" style="display:flex;align-items:center;justify-content:center;background:#f1f5f9;"><i class="fas fa-box text-muted"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                        <br><small class="text-muted"><?= $row['sku'] ?></small>
                                    </div>
                                </div>
                            </tr>
                            <td>
                                <span class="badge" style="background:<?= $row['discount_type'] == 'percentage' ? '#f59e0b' : '#10b981' ?>20; color:<?= $row['discount_type'] == 'percentage' ? '#d97706' : '#059669' ?>">
                                    <?= $row['discount_type'] == 'percentage' ? '<i class="fas fa-percent"></i> Percentage' : '<i class="fas fa-dollar-sign"></i> Fixed' ?>
                                </span>
                            </td>
                            <td class="text-success fw-bold"><?= $row['discount_type'] == 'percentage' ? $row['discount_amount'].'%' : '$'.$row['discount_amount'] ?> OFF</td>
                            <td><span class="original-price">$<?= number_format($row['price_selling'],2) ?></span></td>
                            <td class="discount-price">$<?= number_format($discounted,2) ?> <span class="discount-badge">Save $<?= number_format($saved,2) ?></span></td>
                            <td>
                                <?php if($row['start_date'] && $row['start_date'] != '0000-00-00'): ?>
                                    <i class="fas fa-play-circle text-success"></i> <?= date('M d', strtotime($row['start_date'])) ?>
                                <?php else: ?>
                                    <i class="fas fa-play-circle text-muted"></i> Start
                                <?php endif; ?>
                                - 
                                <?php if($row['end_date'] && $row['end_date'] != '0000-00-00'): ?>
                                    <i class="fas fa-stop-circle text-danger"></i> <?= date('M d, Y', strtotime($row['end_date'])) ?>
                                <?php else: ?>
                                    <i class="fas fa-infinity"></i> No expiry
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['is_active']): ?>
                                    <span class="badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-toggle" onclick="toggleProductDiscount(<?= $row['id'] ?>)"><i class="fas fa-power-off"></i></button>
                                <button class="btn-edit" onclick="editProductDiscount(<?= $row['id'] ?>, <?= $row['product_id'] ?>, '<?= $row['discount_type'] ?>', <?= $row['discount_amount'] ?>, '<?= $row['start_date'] ?>', '<?= $row['end_date'] ?>', <?= $row['is_active'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteProductDiscount(<?= $row['id'] ?>, '<?= addslashes($row['product_name']) ?>')"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">
                            <i class="fas fa-tag fa-3x mb-3 opacity-50"></i>
                            <p>No product discounts added yet</p>
                            <button class="btn-add" onclick="showAddDiscountModal()">Add First Discount</button>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Discount Modal -->
<div id="addDiscountModal" class="modal-custom">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-plus-circle me-2 text-success"></i>Add Product Discount</h5>
            <button onclick="closeModal('addDiscountModal')" style="background:none;border:none;font-size:1.5rem;">&times;</button>
        </div>
        <form method="POST">
            <div class="mb-3">
                <label>Select Product *</label>
                <select name="product_id" id="discount_product_id" class="form-control" required>
                    <option value="">-- Select Product --</option>
                    <?php while($prod = mysqli_fetch_assoc($products)): ?>
                        <option value="<?= $prod['id'] ?>" data-price="<?= $prod['price_selling'] ?>" data-name="<?= htmlspecialchars($prod['product_name']) ?>">
                            <?= htmlspecialchars($prod['product_name']) ?> (<?= $prod['sku'] ?>) - $<?= number_format($prod['price_selling'],2) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Discount Type</label>
                <select name="discount_type" id="discount_type" class="form-control" onchange="updateDiscountLabel()">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="mb-3">
                <label id="discount_label">Discount Amount (%)</label>
                <input type="number" step="0.01" name="discount_amount" id="discount_amount" class="form-control" required onkeyup="updatePricePreview()">
            </div>
            <div class="row">
                <div class="col-md-6"><div class="mb-3"><label>Start Date</label><input type="date" name="start_date" id="start_date" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>End Date</label><input type="date" name="end_date" id="end_date" class="form-control"></div></div>
            </div>
            <div class="mb-3"><label><input type="checkbox" name="is_active" id="is_active" checked> Active</label></div>
            <div class="price-preview" id="pricePreview"><i class="fas fa-info-circle"></i> Select a product to see discounted price</div>
            <button type="submit" name="add_product_discount" class="btn btn-primary w-100 mt-3">Apply Discount</button>
        </form>
    </div>
</div>

<!-- Edit Discount Modal - FIXED -->
<div id="editDiscountModal" class="modal-custom">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-edit me-2 text-warning"></i>Edit Product Discount</h5>
            <button onclick="closeModal('editDiscountModal')" style="background:none;border:none;font-size:1.5rem;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="discount_id" id="edit_discount_id">
            <div class="mb-3">
                <label>Product</label>
                <input type="text" id="edit_product_name" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label>Discount Type</label>
                <select name="discount_type" id="edit_discount_type" class="form-control" onchange="updateEditPricePreview()">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="mb-3">
                <label id="edit_discount_label">Discount Amount</label>
                <input type="number" step="0.01" name="discount_amount" id="edit_discount_amount" class="form-control" required onkeyup="updateEditPricePreview()">
            </div>
            <div class="row">
                <div class="col-md-6"><div class="mb-3"><label>Start Date</label><input type="date" name="start_date" id="edit_start_date" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>End Date</label><input type="date" name="end_date" id="edit_end_date" class="form-control"></div></div>
            </div>
            <div class="mb-3"><label><input type="checkbox" name="is_active" id="edit_is_active"> Active</label></div>
            <div class="price-preview" id="editPricePreview"><i class="fas fa-info-circle"></i> Discount preview will appear here</div>
            <button type="submit" name="edit_product_discount" class="btn btn-primary w-100 mt-3">Update Discount</button>
        </form>
    </div>
</div>

<script>
let currentProductPrice = 0;
let currentProductName = '';

function showAddDiscountModal() {
    document.getElementById('addDiscountModal').style.display = 'flex';
    document.getElementById('discount_product_id').value = '';
    document.getElementById('discount_amount').value = '';
    document.getElementById('pricePreview').innerHTML = '<i class="fas fa-info-circle"></i> Select a product to see discounted price';
}

function closeModal(modalId) { 
    document.getElementById(modalId).style.display = 'none'; 
}

function updateDiscountLabel() {
    let type = document.getElementById('discount_type').value;
    document.getElementById('discount_label').innerHTML = type == 'percentage' ? 'Discount Amount (%)' : 'Discount Amount ($)';
}

function updatePricePreview() {
    let select = document.getElementById('discount_product_id');
    let selectedOption = select.options[select.selectedIndex];
    let price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    let discountType = document.getElementById('discount_type').value;
    let discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    if(price > 0 && discountAmount > 0) {
        let discounted = discountType == 'percentage' ? price - (price * discountAmount / 100) : price - discountAmount;
        if(discounted < 0) discounted = 0;
        let saved = price - discounted;
        document.getElementById('pricePreview').innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="preview-original">Original: <strong>$${price.toFixed(2)}</strong></span></div>
                <div><i class="fas fa-arrow-right text-muted"></i></div>
                <div><span class="preview-discounted">Discounted: <strong>$${discounted.toFixed(2)}</strong></span><br><small class="text-success">Save $${saved.toFixed(2)}</small></div>
            </div>
        `;
    } else {
        document.getElementById('pricePreview').innerHTML = '<i class="fas fa-info-circle"></i> Enter discount amount to see preview';
    }
}

// Add event listeners
document.getElementById('discount_product_id')?.addEventListener('change', function() {
    let selected = this.options[this.selectedIndex];
    currentProductPrice = parseFloat(selected.getAttribute('data-price')) || 0;
    currentProductName = selected.getAttribute('data-name') || '';
    updatePricePreview();
});

document.getElementById('discount_amount')?.addEventListener('keyup', updatePricePreview);

// Edit Product Discount - FIXED
function editProductDiscount(id, productId, type, amount, startDate, endDate, isActive) {
    // Set form values
    document.getElementById('edit_discount_id').value = id;
    document.getElementById('edit_discount_type').value = type;
    document.getElementById('edit_discount_amount').value = amount;
    document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_end_date').value = endDate;
    document.getElementById('edit_is_active').checked = isActive == 1;
    
    // Update label based on type
    let labelElem = document.getElementById('edit_discount_label');
    if(type == 'percentage') {
        labelElem.innerHTML = 'Discount Amount (%)';
    } else {
        labelElem.innerHTML = 'Discount Amount ($)';
    }
    
    // Get product info via AJAX
    $.ajax({
        url: 'get_product_info.php',
        method: 'GET',
        data: {id: productId},
        dataType: 'json',
        success: function(data) {
            document.getElementById('edit_product_name').value = data.product_name;
            currentProductPrice = parseFloat(data.price_selling);
            updateEditPricePreview();
        },
        error: function() {
            document.getElementById('edit_product_name').value = 'Product ID: ' + productId;
            currentProductPrice = 0;
        }
    });
    
    document.getElementById('editDiscountModal').style.display = 'flex';
}

function updateEditPricePreview() {
    let price = currentProductPrice;
    let discountType = document.getElementById('edit_discount_type').value;
    let discountAmount = parseFloat(document.getElementById('edit_discount_amount').value) || 0;
    
    if(price > 0 && discountAmount > 0) {
        let discounted = discountType == 'percentage' ? price - (price * discountAmount / 100) : price - discountAmount;
        if(discounted < 0) discounted = 0;
        let saved = price - discounted;
        document.getElementById('editPricePreview').innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="preview-original">Original: <strong>$${price.toFixed(2)}</strong></span></div>
                <div><i class="fas fa-arrow-right text-muted"></i></div>
                <div><span class="preview-discounted">Discounted: <strong>$${discounted.toFixed(2)}</strong></span><br><small class="text-success">Save $${saved.toFixed(2)}</small></div>
            </div>
        `;
    } else {
        document.getElementById('editPricePreview').innerHTML = '<i class="fas fa-info-circle"></i> Enter discount amount to see preview';
    }
}

// Add event listener for edit discount amount
document.getElementById('edit_discount_amount')?.addEventListener('keyup', updateEditPricePreview);
document.getElementById('edit_discount_type')?.addEventListener('change', function() {
    let type = this.value;
    let labelElem = document.getElementById('edit_discount_label');
    if(type == 'percentage') {
        labelElem.innerHTML = 'Discount Amount (%)';
    } else {
        labelElem.innerHTML = 'Discount Amount ($)';
    }
    updateEditPricePreview();
});

function toggleProductDiscount(id) {
    Swal.fire({
        title: 'Toggle Discount?',
        text: "This will enable/disable the discount",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#64748b',
        confirmButtonText: 'Yes, toggle'
    }).then((result) => {
        if(result.isConfirmed) { window.location.href = '?toggle_product_discount=' + id; }
    });
}

function deleteProductDiscount(id, name) {
    Swal.fire({
        title: 'Remove Discount?',
        text: `Remove discount from "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, remove'
    }).then((result) => {
        if(result.isConfirmed) { window.location.href = '?delete_product_discount=' + id; }
    });
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

window.onclick = function(e) { if(e.target.classList.contains('modal-custom')) e.target.style.display = 'none'; }
</script>
</body>
</html>