<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

// ============================================
// PERMISSION CHECK
// ============================================
if(!hasPermission('pos_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// ============================================
// HANDLE EXCEL UPLOAD (Save to database)
// ============================================
if(isset($_POST['upload_excel'])) {
    if(isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $file_name = $_FILES['excel_file']['name'];
        $file_tmp = $_FILES['excel_file']['tmp_name'];
        $file_size = $_FILES['excel_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['xlsx', 'xls', 'csv'];
        
        if(in_array($file_ext, $allowed_ext)) {
            // Create directory if not exists
            $upload_dir = 'uploads/excel/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Create unique filename
            $unique_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
            $file_path = $upload_dir . $unique_name;
            
            if(move_uploaded_file($file_tmp, $file_path)) {
                // Save to database
                $uploaded_by = $_SESSION['username'] ?? 'Unknown';
                $user_id = $_SESSION['user_id'];
                $upload_date = date('Y-m-d H:i:s');
                $file_size_kb = round($file_size / 1024, 2) . ' KB';
                
                $query = "INSERT INTO excel_uploads (file_name, original_name, file_size, uploaded_by, user_id, upload_date) 
                          VALUES ('$unique_name', '$file_name', '$file_size_kb', '$uploaded_by', '$user_id', '$upload_date')";
                mysqli_query($conn, $query);
                
                $_SESSION['upload_success'] = "✓ Excel file uploaded successfully: " . htmlspecialchars($file_name);
            } else {
                $_SESSION['upload_error'] = "❌ Failed to move uploaded file.";
            }
        } else {
            $_SESSION['upload_error'] = "❌ Invalid file type! Please upload .xlsx, .xls, or .csv only.";
        }
    } else {
        $_SESSION['upload_error'] = "❌ Please select a file to upload.";
    }
    header("Location: pos.php");
    exit();
}

// Add to cart (same as before)
if(isset($_POST['add_to_cart'])) {
    if(!hasPermission('pos_add_to_cart') && !hasRole('admin')) {
        $_SESSION['pos_error'] = "You don't have permission to add items to cart!";
        header("Location: pos.php");
        exit();
    }
    
    $product_id = $_POST['product_id'];
    $selling_price = $_POST['price'];
    $qty = $_POST['qty'];
    
    $product_check = mysqli_query($conn, "SELECT price_last, quantity, product_name, price_selling, discount_price, discount_expiry FROM products WHERE id = $product_id");
    $product_data = mysqli_fetch_assoc($product_check);
    $last_price = $product_data['price_last'];
    $current_stock = $product_data['quantity'];
    
    if($last_price > 0 && $selling_price < $last_price) {
        $_SESSION['pos_error'] = "price_validation";
        header("Location: pos.php");
        exit();
    }
    elseif($qty > $current_stock) {
        $_SESSION['pos_error'] = "stock";
        header("Location: pos.php");
        exit();
    }
    else {
        $cart = $_SESSION['cart'] ?? [];
        $found = false;
        
        foreach($cart as $key => $item) {
            if($item['product_id'] == $product_id && $item['price'] == $selling_price) {
                $cart[$key]['qty'] += $qty;
                $found = true;
                break;
            }
        }
        
        if(!$found) {
            $cart[] = [
                'product_id' => $product_id,
                'product_name' => $_POST['product_name'],
                'price' => $selling_price,
                'cost' => $_POST['cost'],
                'qty' => $qty
            ];
        }
        $_SESSION['cart'] = $cart;
        $_SESSION['pos_success_temp'] = "✓ Item added to cart successfully!";
    }
    header("Location: pos.php");
    exit();
}

// Remove from cart
if(isset($_GET['remove'])) {
    if(!hasPermission('pos_remove_from_cart') && !hasRole('admin')) {
        $_SESSION['pos_error'] = "You don't have permission to remove items from cart!";
        header("Location: pos.php");
        exit();
    }
    unset($_SESSION['cart'][$_GET['remove']]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: pos.php");
    exit();
}

// Update cart quantity
if(isset($_POST['update_qty'])) {
    $cart = $_SESSION['cart'];
    $cart[$_POST['cart_id']]['qty'] = $_POST['qty'];
    $_SESSION['cart'] = $cart;
    header("Location: pos.php");
    exit();
}

// Clear cart
if(isset($_POST['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: pos.php");
    exit();
}

// Select payment method
if(isset($_POST['select_payment'])) {
    $_SESSION['selected_payment'] = $_POST['payment_method'];
    $_SESSION['show_complete_btn'] = true;
    header("Location: pos.php");
    exit();
}

// Cancel payment selection
if(isset($_POST['cancel_payment'])) {
    unset($_SESSION['selected_payment']);
    unset($_SESSION['show_complete_btn']);
    header("Location: pos.php");
    exit();
}

// Complete sale
if(isset($_POST['complete_sale'])) {
    if(!hasPermission('pos_checkout') && !hasRole('admin')) {
        $_SESSION['pos_error'] = "You don't have permission to complete sales!";
        header("Location: pos.php");
        exit();
    }
    
    $payment_method = $_SESSION['selected_payment'];
    $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $sale_date = date('Y-m-d');
    $total_sale_amount = 0;
    $total_profit_amount = 0;
    
    foreach($_SESSION['cart'] as $item) {
        $total = $item['qty'] * $item['price'];
        $profit = $total - ($item['cost'] * $item['qty']);
        $total_sale_amount += $total;
        $total_profit_amount += $profit;
        
        mysqli_query($conn, "UPDATE products SET quantity = quantity - {$item['qty']} WHERE id = {$item['product_id']}");
        
        $query = "INSERT INTO sales (invoice_no, sale_date, product_id, quantity, unit_price, total, profit) 
                  VALUES ('$invoice_no', '$sale_date', '{$item['product_id']}', '{$item['qty']}', '{$item['price']}', '$total', '$profit')";
        mysqli_query($conn, $query);
    }
    
    unset($_SESSION['cart']);
    unset($_SESSION['selected_payment']);
    unset($_SESSION['show_complete_btn']);
    
    $_SESSION['pos_success'] = [
        'invoice' => $invoice_no,
        'total' => $total_sale_amount,
        'profit' => $total_profit_amount,
        'payment_method' => $payment_method
    ];
    header("Location: pos.php");
    exit();
}

// Calculate cart total
$cart_total = 0;
$cart_items_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item) {
        $cart_total += $item['qty'] * $item['price'];
        $cart_items_count += $item['qty'];
    }
}

// Get products
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$products_query = "SELECT p.*, c.name as category_name, c.color as category_color,
                   CASE 
                       WHEN p.discount_price IS NOT NULL AND p.discount_expiry >= CURDATE() THEN p.discount_price
                       ELSE p.price_selling
                   END as current_price
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.quantity > 0";

if($search) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $products_query .= " AND (p.product_name LIKE '%$search_term%' OR p.sku LIKE '%$search_term%')";
}

if($category_filter) {
    $products_query .= " AND c.name = '$category_filter'";
}

$products_query .= " ORDER BY p.product_name LIMIT 50";
$products = mysqli_query($conn, $products_query);

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        .swal2-container { z-index: 10000 !important; }
        .swal2-popup { z-index: 10001 !important; }
        
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
        .sidebar-header h3 { color: #FFD700; }
        .sidebar-header p { color: #94a3b8; font-size: 0.75rem; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: rgba(79,70,229,0.2); color: white; }
        .menu-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 15px 18px; }
        
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 12px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-title { font-size: 1.3rem; font-weight: 700; color: #1e293b; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        
        .pos-container { display: flex; gap: 20px; }
        .products-section { flex: 2; background: white; border-radius: 20px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .cart-section { flex: 1.2; background: white; border-radius: 20px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 20px; height: fit-content; }
        
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input { flex: 1; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 15px; font-size: 0.9rem; }
        .search-input:focus { border-color: #4f46e5; outline: none; }
        .category-filter select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; width: 180px; }
        
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; max-height: 550px; overflow-y: auto; padding: 5px; }
        .product-card { background: white; border-radius: 14px; padding: 10px; cursor: pointer; transition: all 0.3s; border: 1px solid #e2e8f0; text-align: center; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #4f46e5; }
        .product-img { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; margin-bottom: 8px; background: #f1f5f9; }
        .product-img-placeholder { width: 70px; height: 70px; border-radius: 10px; margin-bottom: 8px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); display: flex; align-items: center; justify-content: center; margin-left: auto; margin-right: auto; }
        .product-img-placeholder i { font-size: 2rem; color: #94a3b8; }
        .discount-badge { position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 20px; font-weight: 600; }
        .product-name { font-weight: 600; font-size: 0.75rem; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-price { font-size: 0.9rem; font-weight: 700; color: #4f46e5; }
        .old-price { font-size: 0.65rem; text-decoration: line-through; color: #94a3b8; }
        .product-stock { font-size: 0.6rem; color: #10b981; margin-top: 3px; }
        
        .cart-header { display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 0.8rem; }
        .cart-items { max-height: 350px; overflow-y: auto; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 0.8rem; }
        .cart-item-info { flex: 2; }
        .cart-item-name { font-weight: 600; font-size: 0.75rem; }
        .cart-item-price { font-size: 0.65rem; color: #64748b; }
        .cart-item-qty { display: flex; align-items: center; gap: 5px; }
        .cart-item-qty input { width: 45px; text-align: center; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px; font-size: 0.7rem; }
        .cart-item-total { font-weight: 700; color: #4f46e5; min-width: 60px; text-align: right; font-size: 0.75rem; }
        .cart-item-remove { color: #ef4444; cursor: pointer; margin-left: 8px; font-size: 0.7rem; }
        
        .cart-totals { margin-top: 15px; padding-top: 12px; border-top: 2px solid #e2e8f0; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.85rem; }
        .grand-total { font-size: 1.1rem; font-weight: 800; color: #4f46e5; }
        
        .payment-buttons { display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap; }
        .btn-payment { flex: 1; padding: 8px; border: none; border-radius: 10px; font-weight: 600; font-size: 0.7rem; transition: 0.3s; cursor: pointer; }
        .btn-cash { background: #10b981; color: white; }
        .btn-card { background: #3b82f6; color: white; }
        .btn-multiple { background: #8b5cf6; color: white; }
        .btn-credit { background: #f59e0b; color: white; }
        
        .action-buttons { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .btn-sales-list { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; border: none; padding: 8px; border-radius: 10px; flex: 1; text-decoration: none; text-align: center; font-size: 0.75rem; }
        .btn-cancel-order { background: #64748b; color: white; border: none; padding: 8px; border-radius: 10px; flex: 1; font-size: 0.75rem; cursor: pointer; }
        .btn-complete { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px; border-radius: 10px; font-weight: 700; width: 100%; margin-top: 12px; font-size: 0.85rem; cursor: pointer; }
        .btn-upload-excel { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 8px; border-radius: 10px; flex: 1; font-size: 0.75rem; cursor: pointer; }
        .btn-view-excel { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 8px; border-radius: 10px; flex: 1; font-size: 0.75rem; cursor: pointer; text-decoration: none; text-align: center; display: inline-block; }
        
        .customer-section { margin-bottom: 12px; padding: 8px; background: #f8fafc; border-radius: 12px; }
        .customer-badge { background: #4f46e5; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        
        .success-alert-custom { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 15px; border-radius: 14px; margin-bottom: 20px; animation: slideDown 0.5s ease; cursor: pointer; }
        .selected-payment-info { background: #e0e7ff; border-radius: 10px; padding: 8px; margin-top: 12px; text-align: center; font-size: 0.75rem; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 450px; max-width: 90%; }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
            .pos-container { flex-direction: column; }
            .cart-section { position: static; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>🚗 Adam Car</h3><p>Accessories System</p></div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="pos.php" class="menu-item active"><i class="fas fa-shopping-cart"></i> POS</a>
        <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <?php if(hasRole('admin')): ?>
        <a href="excel_uploads.php" class="menu-item"><i class="fas fa-file-excel"></i> 📊 Excel Uploads</a>
        <?php endif; ?>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-cash-register me-2"></i>Point of Sale</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= date('h:i A') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['pos_success']) && is_array($_SESSION['pos_success'])): ?>
        <div class="success-alert-custom" onclick="window.location.href='sales.php'">
            <div class="d-flex justify-content-between align-items-center">
                <div><i class="fas fa-check-circle fa-2x me-3"></i></div>
                <div class="flex-grow-1">
                    <strong class="fs-6">Sale Completed!</strong><br>
                    Invoice: <?= $_SESSION['pos_success']['invoice'] ?> | Total: $<?= number_format($_SESSION['pos_success']['total'], 2) ?> | Payment: <?= $_SESSION['pos_success']['payment_method'] ?>
                </div>
                <div><i class="fas fa-arrow-right fa-2x"></i><small>View</small></div>
            </div>
        </div>
        <?php unset($_SESSION['pos_success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['pos_success_temp'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['pos_success_temp'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['pos_success_temp']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['upload_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['upload_success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['upload_success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['upload_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['upload_error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['upload_error']); ?>
    <?php endif; ?>
    
    <div class="pos-container">
        <div class="products-section">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search product by name or SKU...">
                <div class="category-filter">
                    <select id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= $cat['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div id="productList">
                <div class="product-grid">
                    <?php if(mysqli_num_rows($products) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($products)): 
                            $has_discount = ($p['discount_price'] && $p['discount_expiry'] >= date('Y-m-d'));
                            $display_price = $has_discount ? $p['discount_price'] : $p['price_selling'];
                        ?>
                            <div class="product-card" onclick="showPriceModal(<?= $p['id'] ?>, '<?= addslashes($p['product_name']) ?>', <?= $display_price ?>, <?= $p['price_regular'] ?>, <?= $p['price_last'] ?>, <?= $p['quantity'] ?>)">
                                <?php if($has_discount): ?>
                                    <span class="discount-badge"><?= round((($p['price_selling'] - $p['discount_price']) / $p['price_selling']) * 100) ?>% OFF</span>
                                <?php endif; ?>
                                <?php if(!empty($p['image']) && file_exists('assets/uploads/' . $p['image'])): ?>
                                    <img src="assets/uploads/<?= $p['image'] ?>" class="product-img">
                                <?php else: ?>
                                    <div class="product-img-placeholder"><i class="fas fa-box"></i></div>
                                <?php endif; ?>
                                <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
                                <?php if($has_discount): ?><div class="old-price">$<?= number_format($p['price_selling'], 2) ?></div><?php endif; ?>
                                <div class="product-price">$<?= number_format($display_price, 2) ?></div>
                                <div class="product-stock"><i class="fas fa-boxes"></i> <?= $p['quantity'] ?> left</div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">No products found</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="cart-section">
            <div class="customer-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div><span class="customer-badge"><i class="fas fa-user me-1"></i> Walk-In</span></div>
                    <a href="#" class="text-primary small">Add Customer</a>
                </div>
            </div>
            
            <div class="cart-header"><strong>Product</strong><strong>Qty</strong><strong>Total</strong></div>
            
            <div class="cart-items" id="cartItems">
                <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <?php foreach($_SESSION['cart'] as $i => $item): $item_total = $item['qty'] * $item['price']; ?>
                        <div class="cart-item">
                            <div class="cart-item-info"><div class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></div><div class="cart-item-price">$<?= number_format($item['price'], 2) ?></div></div>
                            <div class="cart-item-qty"><form method="POST"><input type="hidden" name="cart_id" value="<?= $i ?>"><input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" class="qty-input" onchange="this.form.submit()"><input type="hidden" name="update_qty" value="1"></form><a href="?remove=<?= $i ?>" class="cart-item-remove"><i class="fas fa-trash-alt"></i></a></div>
                            <div class="cart-item-total">$<?= number_format($item_total, 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4"><i class="fas fa-shopping-cart fa-2x mb-2 opacity-50"></i><p>Cart empty</p></div>
                <?php endif; ?>
            </div>
            
            <div class="cart-totals">
                <div class="total-row"><span>Subtotal:</span><span>$<?= number_format($cart_total, 2) ?></span></div>
                <div class="total-row grand-total"><span>Total:</span><span>$<?= number_format($cart_total, 2) ?></span></div>
            </div>
            
            <?php if($cart_items_count > 0): ?>
                <?php if(!isset($_SESSION['show_complete_btn'])): ?>
                    <form method="POST" id="paymentForm">
                        <div class="payment-buttons">
                            <button type="submit" name="select_payment" value="Cash" class="btn-payment btn-cash" onclick="setPayment('Cash')">Cash</button>
                            <button type="submit" name="select_payment" value="Card" class="btn-payment btn-card" onclick="setPayment('Card')">Card</button>
                            <button type="submit" name="select_payment" value="Multiple" class="btn-payment btn-multiple" onclick="setPayment('Multiple')">Multi</button>
                            <button type="submit" name="select_payment" value="Credit" class="btn-payment btn-credit" onclick="setPayment('Credit')">Credit</button>
                        </div>
                        <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="">
                    </form>
                <?php else: ?>
                    <div class="selected-payment-info"><i class="fas fa-check-circle"></i> Payment: <strong><?= $_SESSION['selected_payment'] ?></strong></div>
                    <form method="POST"><button type="submit" name="complete_sale" class="btn-complete"><i class="fas fa-check-circle me-2"></i> COMPLETE SALE</button></form>
                    <form method="POST" class="mt-2"><button type="submit" name="cancel_payment" class="btn-cancel-order w-100">Cancel</button></form>
                <?php endif; ?>
                <div class="action-buttons mt-3">
                    <a href="sales.php" class="btn-sales-list">Sales List</a>
                    <button class="btn-cancel-order" onclick="clearCart()">Clear Cart</button>
                </div>
            <?php endif; ?>
            
            <!-- Upload Excel Button - Always Visible -->
            <div class="action-buttons mt-2">
                <button class="btn-upload-excel" onclick="openUploadModal()"><i class="fas fa-file-excel me-1"></i> Upload Excel</button>
                <?php if(hasRole('admin')): ?>
                <a href="excel_uploads.php" class="btn-view-excel"><i class="fas fa-folder-open me-1"></i> View Uploads</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Price Modal -->
<div id="priceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; padding: 25px; width: 380px; max-width: 90%;">
        <h5 class="mb-3"><i class="fas fa-tag me-2"></i>Add to Cart</h5>
        <div id="modalProductInfo" class="alert alert-info py-2 mb-3"></div>
        <div class="mb-3"><label>Quantity</label><input type="number" id="modalQty" class="form-control" value="1" min="1"></div>
        <div class="mb-3"><label>Selling Price ($)</label><input type="number" id="modalPrice" class="form-control" step="0.01"><small class="text-muted" id="priceWarning"></small></div>
        <div class="d-flex gap-2"><button onclick="confirmAddToCart()" class="btn btn-primary flex-grow-1">Add</button><button onclick="closeModal()" class="btn btn-secondary">Cancel</button></div>
    </div>
</div>

<!-- Upload Excel Modal -->
<div id="uploadModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h5 class="mb-3"><i class="fas fa-file-excel text-success me-2"></i>Upload Excel File</h5>
        <p class="text-muted small mb-3">Upload Excel file (.xlsx, .xls, .csv) containing financial data, sales reports, or accounting records.</p>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Select Excel File</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <div class="form-text text-muted">Allowed formats: .xlsx, .xls, .csv | Max size: 10MB</div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="upload_excel" class="btn btn-success flex-grow-1"><i class="fas fa-upload me-2"></i>Upload</button>
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentProduct = {};

function setPayment(method) {
    document.getElementById('selectedPaymentMethod').value = method;
    document.getElementById('paymentForm').submit();
}

function openUploadModal() {
    document.getElementById('uploadModal').style.display = 'flex';
}

function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    let uploadModal = document.getElementById('uploadModal');
    let priceModal = document.getElementById('priceModal');
    if(event.target == uploadModal) {
        uploadModal.style.display = 'none';
    }
    if(event.target == priceModal) {
        priceModal.style.display = 'none';
    }
}

function showPriceModal(id, name, defaultPrice, cost, lastPrice, maxQty) {
    currentProduct = { id, name, defaultPrice, cost, lastPrice, maxQty };
    document.getElementById('modalProductInfo').innerHTML = `<strong>${name}</strong><br>Price: $${defaultPrice} | Last: $${lastPrice} | Stock: ${maxQty}`;
    document.getElementById('modalPrice').value = defaultPrice;
    document.getElementById('modalQty').value = 1;
    document.getElementById('modalQty').max = maxQty;
    
    let warningSpan = document.getElementById('priceWarning');
    if(lastPrice > 0 && defaultPrice < lastPrice) {
        warningSpan.innerHTML = `<span style="color:#ef4444;">⚠️ Warning: Price below last price ($${lastPrice})!</span>`;
    } else {
        warningSpan.innerHTML = `<span style="color:#64748b;">Last price: $${lastPrice}</span>`;
    }
    document.getElementById('priceModal').style.display = 'flex';
}

function closeModal() { document.getElementById('priceModal').style.display = 'none'; }

function confirmAddToCart() {
    let qty = parseInt(document.getElementById('modalQty').value);
    let price = parseFloat(document.getElementById('modalPrice').value);
    let lastPrice = currentProduct.lastPrice;
    let maxQty = currentProduct.maxQty;
    
    if(lastPrice > 0 && price < lastPrice) {
        Swal.fire({
            icon: 'error',
            title: '⚠️ Price Validation Failed!',
            html: `<div><strong>Selling price ($${price.toFixed(2)}) cannot be less than Last Price ($${lastPrice.toFixed(2)})!</strong><br><br>Please enter a price that is equal to or greater than $${lastPrice.toFixed(2)}.</div>`,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Try Again'
        });
        return;
    }
    
    if(qty > maxQty) {
        Swal.fire({
            icon: 'error',
            title: '⚠️ Insufficient Stock!',
            text: `Only ${maxQty} units available!`,
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    Swal.fire({
        icon: 'success',
        title: '✓ Item Added!',
        text: `${currentProduct.name} has been added to cart.`,
        timer: 1500,
        showConfirmButton: false
    });
    
    closeModal();
    
    setTimeout(() => {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="product_id" value="${currentProduct.id}">
                         <input type="hidden" name="product_name" value="${currentProduct.name}">
                         <input type="hidden" name="price" value="${price}">
                         <input type="hidden" name="cost" value="${currentProduct.cost}">
                         <input type="hidden" name="qty" value="${qty}">
                         <input type="hidden" name="add_to_cart" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }, 500);
}

function clearCart() {
    if(<?= $cart_items_count ?> > 0) {
        Swal.fire({
            title: 'Clear Cart?',
            text: 'All items will be removed',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, clear'
        }).then((result) => {
            if(result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="clear_cart" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

let searchTimeout;
$('#searchInput').on('keyup', function() {
    clearTimeout(searchTimeout);
    let query = $(this).val();
    let category = $('#categoryFilter').val();
    searchTimeout = setTimeout(function() {
        $.ajax({ url: 'search_products_ajax.php', method: 'GET', data: {q: query, category: category}, success: function(data) { $('#productList').html(data); } });
    }, 300);
});
$('#categoryFilter').on('change', function() {
    let query = $('#searchInput').val();
    let category = $(this).val();
    $.ajax({ url: 'search_products_ajax.php', method: 'GET', data: {q: query, category: category}, success: function(data) { $('#productList').html(data); } });
});
</script>
</body>
</html>