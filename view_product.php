<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

$id = $_GET['id'] ?? 0;
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as category_name, c.color as category_color 
                                                    FROM products p 
                                                    LEFT JOIN categories c ON p.category_id = c.id 
                                                    WHERE p.id = $id"));
if(!$product) header("Location: products.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['product_name']) ?> - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .top-bar{background:white;border-radius:16px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;}
        .page-title{font-size:1.5rem;font-weight:700;color:#1e293b;}
        .user-avatar{width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;}
        .container-custom{max-width:1000px;margin:50px auto;padding:20px;}
        .product-card{background:white;border-radius:24px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,0.1);}
        .product-img{max-width:300px;border-radius:16px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .price{font-size:2.5rem;font-weight:800;color:#4f46e5;}
        .old-price{text-decoration:line-through;color:#94a3b8;font-size:1.2rem;}
        .btn-back{background:#64748b;color:white;padding:10px 20px;border-radius:12px;text-decoration:none;display:inline-block;}
        .btn-edit{background:#f59e0b;color:white;padding:10px 20px;border-radius:12px;text-decoration:none;display:inline-block;}
        .btn-buy{background:#10b981;color:white;padding:10px 20px;border-radius:12px;text-decoration:none;display:inline-block;}
        .btn-back:hover{background:#475569;color:white;}
        .btn-edit:hover{background:#d97706;color:white;}
        .btn-buy:hover{background:#059669;color:white;}
        @media (max-width:992px){.sidebar{transform:translateX(-100%);}.main-content{margin-left:0;}.sidebar.active{transform:translateX(0);}}
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
        <h1 class="page-title"><i class="fas fa-eye me-2"></i>Product Details</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>
    
    <div class="container-custom">
        <div class="product-card">
            <div class="row">
                <div class="col-md-5 text-center">
                    <?php if(!empty($product['image']) && file_exists('assets/uploads/'.$product['image'])): ?>
                        <img src="assets/uploads/<?= $product['image'] ?>" class="product-img">
                    <?php else: ?>
                        <div style="width:300px;height:300px;background:#f1f5f9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                            <i class="fas fa-box fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <h2><?= htmlspecialchars($product['product_name']) ?></h2>
                    <p class="text-muted">SKU: <?= $product['sku'] ?> | Unit: <?= ucfirst($product['unit']) ?></p>
                    
                    <?php if($product['category_name']): ?>
                        <p>
                            <span class="badge" style="background:<?= $product['category_color'] ?>20; color:<?= $product['category_color'] ?>; padding:5px 12px;">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <span class="price">$<?= number_format($product['price_selling'],2) ?></span>
                        <?php if($product['price_regular'] > $product['price_selling']): ?>
                            <span class="old-price ms-2">$<?= number_format($product['price_regular'],2) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="mt-3"><strong>Last Price:</strong> $<?= number_format($product['price_last'],2) ?></p>
                    
                    <p><strong>Stock:</strong> 
                        <span class="badge <?= $product['quantity'] <= $product['alert_quantity'] ? 'bg-danger' : 'bg-success' ?>">
                            <?= $product['quantity'] ?> units left
                        </span>
                    </p>
                    
                    <div class="mt-4 d-flex gap-2">
                        <a href="pos.php" class="btn-buy"><i class="fas fa-shopping-cart"></i> Buy Now</a>
                        <a href="products.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                        <?php if(hasPermission('products_edit') || hasRole('admin')): ?>
                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>
</body>
</html>