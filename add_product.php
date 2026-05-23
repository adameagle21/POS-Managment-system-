<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check permission - only users with add permission or admin can access
if(!hasPermission('products_add') && !hasRole('admin')) {
    header("Location: products.php");
    exit();
}

// Create uploads folder if not exists
$upload_dir = __DIR__ . '/assets/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$categories = mysqli_query($conn, "SELECT * FROM categories");
$message = '';
$error = '';

// Function to generate SKU automatically
function generateSKU($product_name, $conn) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $product_name), 0, 3));
    if(empty($prefix)) $prefix = 'PRD';
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
    $count = mysqli_fetch_assoc($result);
    $new_id = $count['count'] + 1;
    
    return $prefix . '-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
}

if(isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $sku = mysqli_real_escape_string($conn, $_POST['sku']);
    $unit = $_POST['unit'];
    $category_id = $_POST['category_id'] ?: 'NULL';
    $quantity = $_POST['quantity'];
    $alert_qty = $_POST['alert_quantity'];
    $price_regular = $_POST['price_regular'];
    $price_selling = $_POST['price_selling'];
    $price_last = $_POST['price_last'];
    
    // Validate: selling price cannot be less than last price
    if($price_last > 0 && $price_selling < $price_last) {
        $error = "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-circle me-2'></i> 
            Selling Price (\$$price_selling) cannot be less than Last Price (\$$price_last)!
        </div>";
    } else {
        // Auto generate SKU if empty
        if(empty($sku)) {
            $sku = generateSKU($name, $conn);
        }
        
        // Handle image upload
        $image = '';
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $image = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $_FILES['image']['name']);
                $target_path = $upload_dir . $image;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // File uploaded successfully
                } else {
                    $error = "<div class='alert alert-danger'>✗ Failed to upload image. Check folder permissions.</div>";
                }
            } else {
                $error = "<div class='alert alert-danger'>✗ Invalid image format. Use JPG, PNG, or GIF.</div>";
            }
        }
        
        if(empty($error)) {
            $query = "INSERT INTO products (product_name, sku, unit, category_id, quantity, alert_quantity, image, price_regular, price_selling, price_last) 
                      VALUES ('$name', '$sku', '$unit', $category_id, '$quantity', '$alert_qty', '$image', '$price_regular', '$price_selling', '$price_last')";
            
            if(mysqli_query($conn, $query)) {
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Product added successfully! SKU: $sku',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => {
                        window.location = 'products.php';
                    });
                </script>";
            } else {
                $error = "<div class='alert alert-danger'>✗ Database Error: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Adam Car</title>
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
        .form-container{background:white;border-radius:20px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .form-group{margin-bottom:20px;}
        .form-group label{font-weight:600;margin-bottom:8px;display:block;color:#1e293b;}
        .form-control{border:2px solid #e2e8f0;border-radius:12px;padding:12px 16px;width:100%;transition:0.3s;}
        .form-control:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,0.1);outline:none;}
        .btn-save{background:linear-gradient(135deg,#10b981,#059669);color:white;padding:12px 30px;border:none;border-radius:12px;font-weight:600;transition:0.3s;cursor:pointer;}
        .btn-save:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(16,185,129,0.4);}
        .btn-cancel{background:#64748b;color:white;padding:12px 30px;border-radius:12px;text-decoration:none;margin-left:10px;display:inline-block;}
        .btn-cancel:hover{background:#475569;color:white;}
        .sku-hint{font-size:0.75rem;color:#64748b;margin-top:5px;}
        .price-warning{background:#fef3c7;border-left:4px solid #f59e0b;padding:10px;border-radius:8px;margin-bottom:15px;}
        @media (max-width:768px){.sidebar{transform:translateX(-100%);}.main-content{margin-left:0;}}
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
        <h1 class="page-title"><i class="fas fa-plus-circle me-2"></i>Add New Product</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>
    <div class="form-container">
        <?= $message ?>
        <?= $error ?>
        
        <div class="price-warning mb-3">
            <small><i class="fas fa-info-circle me-1"></i> Note: Selling Price cannot be less than Last Price</small>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required onkeyup="generateSkuHint(this.value)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SKU (Leave empty for auto)</label>
                        <input type="text" name="sku" class="form-control" placeholder="Auto-generated if empty">
                        <div class="sku-hint" id="skuHint"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit" class="form-control">
                            <option value="pieces">Pieces</option>
                            <option value="meter">Meter</option>
                            <option value="box">Box</option>
                            <option value="litter">Litter</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php while($cat = mysqli_fetch_assoc($categories)){ ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Alert Quantity</label>
                        <input type="number" name="alert_quantity" class="form-control" value="5">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Regular Price ($)</label>
                        <input type="number" step="0.01" name="price_regular" class="form-control" id="price_regular" onkeyup="validatePrices()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Selling Price ($) *</label>
                        <input type="number" step="0.01" name="price_selling" class="form-control" required id="price_selling" onkeyup="validatePrices()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Last Price ($)</label>
                        <input type="number" step="0.01" name="price_last" class="form-control" id="price_last" onkeyup="validatePrices()">
                        <small class="text-muted">Lowest price ever sold</small>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Supported formats: JPG, PNG, GIF, WEBP</small>
                    </div>
                </div>
            </div>
            <div id="priceError" class="alert alert-danger d-none mt-2">
                <i class="fas fa-exclamation-circle me-2"></i> Selling Price cannot be less than Last Price!
            </div>
            <div class="mt-4">
                <button type="submit" name="submit" class="btn-save" id="submitBtn"><i class="fas fa-save me-2"></i>Save Product</button>
                <a href="products.php" class="btn-cancel"><i class="fas fa-times me-2"></i>Cancel</a>
            </div>
        </form>
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
            if(menuToggle) menuToggle.style.display = 'block';
            if(sidebar) sidebar.classList.remove('active');
        } else {
            if(menuToggle) menuToggle.style.display = 'none';
            if(sidebar) sidebar.classList.add('active');
        }
    }
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    
    if(menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    function generateSkuHint(productName) {
        if(productName.length > 0) {
            let prefix = productName.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '');
            if(prefix.length < 3) prefix = prefix + 'RD';
            document.getElementById('skuHint').innerHTML = 'Suggested SKU: <code>' + prefix + '-001</code> (leave empty to auto-generate)';
        } else {
            document.getElementById('skuHint').innerHTML = '';
        }
    }

    function validatePrices() {
        let selling = parseFloat(document.getElementById('price_selling').value) || 0;
        let last = parseFloat(document.getElementById('price_last').value) || 0;
        let errorDiv = document.getElementById('priceError');
        let submitBtn = document.getElementById('submitBtn');
        
        if(last > 0 && selling < last) {
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            errorDiv.classList.add('d-none');
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    }
</script>
</body>
</html>