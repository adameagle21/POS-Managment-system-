<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ============================================
// REDIRECT IF NO DASHBOARD ACCESS
// ============================================
if(!hasPermission('dashboard_access') && !hasRole('admin')) {
    if(hasPermission('pos_access')) {
        header("Location: pos.php");
    } elseif(hasPermission('products_access')) {
        header("Location: products.php");
    } elseif(hasPermission('sales_access')) {
        header("Location: sales.php");
    } else {
        header("Location: logout.php");
    }
    exit();
}

// Get today's date
$today = date('Y-m-d');

// ============================================
// GET DATA BASED ON PERMISSIONS (Only fetch what user can see)
// ============================================

// Total Sales - only if user has permission
$total_sales = 0;
if(hasPermission('dashboard_total_sales_view') || hasRole('admin')) {
    $total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM sales"))['total'];
}

// Total Profit - only if user has permission
$total_profit = 0;
if(hasPermission('dashboard_total_profit_view') || hasRole('admin')) {
    $total_profit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(profit),0) as profit FROM sales"))['profit'];
}

// Today's Profit - only if user has permission
$today_profit = 0;
if(hasPermission('dashboard_today_profit_view') || hasRole('admin')) {
    $today_profit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(profit),0) as profit FROM sales WHERE sale_date='$today'"))['profit'];
}

// Today's Sales - only if user has permission
$today_sales = 0;
if(hasPermission('dashboard_today_sales_view') || hasRole('admin')) {
    $today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM sales WHERE sale_date='$today'"))['total'];
}

// Total Expenses - only if user has permission
$total_expenses = 0;
if(hasPermission('dashboard_total_expenses_view') || hasRole('admin')) {
    $total_expenses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM expenses"))['total'];
}

// Total Products - only if user has permission
$total_products = 0;
if(hasPermission('dashboard_total_products_view') || hasRole('admin')) {
    $total_products = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products"));
}

// Total Users - only if user has permission
$total_users = 0;
if(hasPermission('dashboard_total_users_view') || hasRole('admin')) {
    $total_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
}

// Low Stock Products - only if user has permission
$low_stock_count = 0;
$low_stock_products = null;
if(hasPermission('dashboard_low_stock_view') || hasRole('admin')) {
    $low_stock_products = mysqli_query($conn, "SELECT p.*, c.name as category_name 
                                                FROM products p 
                                                LEFT JOIN categories c ON p.category_id = c.id 
                                                WHERE p.quantity <= p.alert_quantity 
                                                ORDER BY p.quantity ASC");
    $low_stock_count = mysqli_num_rows($low_stock_products);
}

// Chart data - only if user has permission
$chart_labels = [];
$chart_sales = [];
$chart_profit = [];
if(hasPermission('dashboard_chart_view') || hasRole('admin')) {
    for($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('M d', strtotime($date));
        $day_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total, COALESCE(SUM(profit),0) as profit FROM sales WHERE sale_date='$date'"));
        $chart_sales[] = $day_data['total'];
        $chart_profit[] = $day_data['profit'];
    }
}

// Recent Sales - only if user has permission
$recent_sales = null;
if(hasPermission('dashboard_recent_sales_view') || hasRole('admin')) {
    $recent_sales = mysqli_query($conn, "SELECT s.*, p.product_name FROM sales s JOIN products p ON s.product_id = p.id ORDER BY s.id DESC LIMIT 10");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #e8ecf1 0%, #dce2e8 100%); overflow-x: hidden; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%); position: fixed; left: 0; top: 0; height: 100vh; padding: 25px 0; z-index: 100; transition: all 0.3s; box-shadow: 8px 0 30px rgba(0,0,0,0.15); overflow-y: auto; }
        .sidebar-header { text-align: center; padding: 0 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 25px; }
        .sidebar-header h3 { font-size: 1.6rem; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-header p { color: #94a3b8; font-size: 0.7rem; margin-top: 8px; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 6px 0; color: #cbd5e1; text-decoration: none; border-radius: 14px; transition: all 0.3s; font-weight: 500; font-size: 0.9rem; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 24px; }
        .menu-item.active { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; box-shadow: 0 6px 15px rgba(79,70,229,0.4); }
        .menu-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 20px 18px; }
        .main-content { margin-left: 280px; padding: 25px 30px; min-height: 100vh; }
        .top-bar { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 24px; padding: 15px 25px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.6rem; font-weight: 700; background: linear-gradient(135deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
        .user-avatar { width: 44px; height: 44px; background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; }
        .stat-card { background: white; border-radius: 24px; padding: 22px; margin-bottom: 25px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.05); cursor: pointer; border: 1px solid rgba(0,0,0,0.03); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 20px 35px rgba(0,0,0,0.1); }
        .card-icon { width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 18px; }
        .card-icon.primary { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }
        .card-icon.success { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #10b981; }
        .card-icon.danger { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #ef4444; }
        .card-icon.warning { background: linear-gradient(135deg, #fed7aa, #fde68a); color: #f59e0b; }
        .card-icon.info { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #8b5cf6; }
        .card-value { font-size: 2rem; font-weight: 800; margin: 10px 0 5px; color: #1e293b; }
        .card-label { color: #64748b; font-size: 0.85rem; font-weight: 500; }
        .small-text { font-size: 0.7rem; color: #94a3b8; margin-top: 8px; }
        .chart-card { background: white; border-radius: 24px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .calculator-card { background: white; border-radius: 24px; padding: 22px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .calc-screen { background: linear-gradient(135deg, #1e293b, #0f172a); color: #f1f5f9; font-size: 1.8rem; padding: 18px; text-align: right; border-radius: 16px; margin-bottom: 18px; font-family: monospace; min-height: 75px; }
        .calc-btn { width: 100%; padding: 12px; font-size: 1rem; font-weight: 600; border: none; border-radius: 12px; transition: all 0.2s; }
        .calc-btn:hover { transform: scale(0.96); }
        .calc-number { background: #f1f5f9; color: #1e293b; }
        .calc-operator { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; }
        .calc-equal { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stock-alert-btn { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 15px 20px; border-radius: 20px; font-weight: 700; width: 100%; transition: all 0.3s; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow: 0 0 0 12px rgba(239,68,68,0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); } }
        .stock-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 2000; align-items: center; justify-content: center; }
        .stock-modal-content { background: white; border-radius: 28px; width: 90%; max-width: 850px; max-height: 85vh; overflow: hidden; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateY(-40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .stock-modal-header { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; }
        .stock-modal-body { padding: 25px; max-height: 60vh; overflow-y: auto; }
        .stock-item { background: #f8fafc; border-radius: 16px; padding: 18px; margin-bottom: 12px; border-left: 4px solid #ef4444; transition: all 0.3s; }
        .table-card { background: white; border-radius: 24px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-custom { width: 100%; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 14px 12px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-weight: 600; }
        .table-custom td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .badge-active { background: #d1fae5; color: #059669; padding: 5px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 600; }
        @media (max-width: 992px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .sidebar.active { transform: translateX(0); } }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>🚗 Adam Car</h3><p>PREMIUM ACCESSORIES</p></div>
    <div class="sidebar-menu">
        <!-- Dashboard -->
        <a href="index.php" class="menu-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        
        <!-- POS System -->
        <?php if(hasPermission('pos_access') || hasRole('admin')): ?>
        <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i><span>POS System</span></a>
        <?php endif; ?>
        
        <!-- Products -->
        <?php if(hasPermission('products_access') || hasRole('admin')): ?>
        <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i><span>Products</span></a>
        <?php endif; ?>
        
        <!-- Sales -->
        <?php if(hasPermission('sales_access') || hasRole('admin')): ?>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i><span>Sales</span></a>
        <?php endif; ?>
        
        <!-- ========== MENU CADAAN ========== -->
        
        <!-- Categories (NEW) -->
        <?php if(hasPermission('products_access') || hasRole('admin')): ?>
        <a href="categories.php" class="menu-item"><i class="fas fa-tags"></i><span>Categories</span></a>
        <?php endif; ?>
        
        <!-- Expenses (NEW) -->
        <?php if(hasPermission('expenses_access') || hasRole('admin')): ?>
        <a href="expenses.php" class="menu-item"><i class="fas fa-receipt"></i><span>Expenses</span></a>
        <?php endif; ?>
        
        <!-- Users -->
        <?php if(hasPermission('users_access') || hasRole('admin')): ?>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Users</span></a>
        <?php endif; ?>
        
        <!-- Reports -->
        <?php if(hasPermission('reports_access') || hasRole('admin')): ?>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
        <?php endif; ?>
        
        <!-- ========== EXCEL UPLOADS (ADMIN ONLY) ========== -->
        <?php if(hasRole('admin')): ?>
        <a href="excel_uploads.php" class="menu-item"><i class="fas fa-file-excel"></i><span>📊 Excel Uploads</span></a>
        <?php endif; ?>
        
        <div class="menu-divider"></div>
        
        <!-- Logout -->
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title">Dashboard</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>

    <!-- ========== STATS ROW 1 ========== -->
    <div class="row">
        <?php if(hasPermission('dashboard_total_sales_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='sales.php'"><div class="card-icon primary"><i class="fas fa-dollar-sign"></i></div><div class="card-value">$<?= number_format($total_sales, 2) ?></div><div class="card-label">Total Sales</div><div class="small-text">Lifetime revenue</div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_total_profit_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='reports.php'"><div class="card-icon success"><i class="fas fa-chart-line"></i></div><div class="card-value">$<?= number_format($total_profit, 2) ?></div><div class="card-label">Total Profit</div><div class="small-text">Gross profit</div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_total_expenses_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='expenses.php'"><div class="card-icon danger"><i class="fas fa-receipt"></i></div><div class="card-value">$<?= number_format($total_expenses, 2) ?></div><div class="card-label">Total Expenses</div><div class="small-text">Total costs</div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_low_stock_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="showStockModal()"><div class="card-icon warning"><i class="fas fa-exclamation-triangle"></i></div><div class="card-value" style="color:#dc2626;"><?= $low_stock_count ?></div><div class="card-label">Low Stock Alert</div><div class="small-text text-danger">Needs attention</div></div></div>
        <?php endif; ?>
    </div>

    <!-- ========== STATS ROW 2 ========== -->
    <div class="row mt-3">
        <?php if(hasPermission('dashboard_today_sales_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='pos.php'"><div class="card-icon info"><i class="fas fa-calendar-day"></i></div><div class="card-value">$<?= number_format($today_sales, 2) ?></div><div class="card-label">Today's Sales</div><div class="small-text">Profit: $<?= number_format($today_profit, 2) ?></div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_total_products_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='products.php'"><div class="card-icon success"><i class="fas fa-boxes"></i></div><div class="card-value"><?= $total_products ?></div><div class="card-label">Total Products</div><div class="small-text">In inventory</div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_total_users_view') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="stat-card" onclick="window.location.href='users.php'"><div class="card-icon primary"><i class="fas fa-users"></i></div><div class="card-value"><?= $total_users ?></div><div class="card-label">Total Users</div><div class="small-text">System accounts</div></div></div>
        <?php endif; ?>
        
        <?php if(hasPermission('dashboard_calculator') || hasRole('admin')): ?>
        <div class="col-md-3"><div class="calculator-card"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0"><i class="fas fa-calculator me-2 text-primary"></i>Quick Calc</h6><button class="btn btn-sm btn-link text-danger p-0" onclick="clearCalc()">Clear</button></div><div class="calc-screen" id="calcScreen">0</div><div class="row g-2"><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('7')">7</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('8')">8</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('9')">9</button></div><div class="col-3"><button class="calc-btn calc-operator" onclick="calcInput('/')">÷</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('4')">4</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('5')">5</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('6')">6</button></div><div class="col-3"><button class="calc-btn calc-operator" onclick="calcInput('*')">×</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('1')">1</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('2')">2</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('3')">3</button></div><div class="col-3"><button class="calc-btn calc-operator" onclick="calcInput('-')">-</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('0')">0</button></div><div class="col-3"><button class="calc-btn calc-number" onclick="calcInput('.')">.</button></div><div class="col-3"><button class="calc-btn calc-equal" onclick="calcResult()">=</button></div><div class="col-3"><button class="calc-btn calc-operator" onclick="calcInput('+')">+</button></div></div></div></div>
        <?php endif; ?>
    </div>

    <!-- ========== CHART ROW ========== -->
    <?php if(hasPermission('dashboard_chart_view') || hasRole('admin')): ?>
    <div class="row mt-4">
        <div class="col-md-8"><div class="chart-card"><h5 class="mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Sales & Profit Trend (Last 7 Days)</h5><canvas id="salesChart" height="280"></canvas></div></div>
        <div class="col-md-4"><div class="chart-card"><h5 class="mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Quick Stats</h5><div class="mb-3"><div class="d-flex justify-content-between mb-2 py-1"><span><i class="fas fa-box me-2 text-primary"></i>Total Products:</span><strong class="text-primary"><?= $total_products ?></strong></div><div class="d-flex justify-content-between mb-2 py-1"><span><i class="fas fa-users me-2 text-success"></i>Total Users:</span><strong class="text-success"><?= $total_users ?></strong></div><div class="d-flex justify-content-between mb-2 py-1"><span><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Low Stock Items:</span><strong class="text-danger"><?= $low_stock_count ?></strong></div><div class="d-flex justify-content-between mb-2 py-1"><span><i class="fas fa-user-tag me-2 text-info"></i>Your Role:</span><strong class="text-info"><?= ucfirst($_SESSION['role'] ?? 'Staff') ?></strong></div></div><hr><div class="d-grid gap-2 mt-3"><?php if(hasPermission('quick_start_sale') || hasRole('admin')): ?><a href="pos.php" class="btn btn-primary py-2" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 12px; text-decoration: none; color: white;">Start New Sale</a><?php endif; ?><?php if(hasPermission('quick_add_product') || hasRole('admin')): ?><a href="add_product.php" class="btn btn-success py-2" style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; text-decoration: none; color: white;">Add New Product</a><?php endif; ?></div></div></div>
    </div>
    <?php endif; ?>

    <!-- ========== RECENT SALES TABLE ========== -->
    <?php if(hasPermission('dashboard_recent_sales_view') || hasRole('admin')): ?>
    <div class="table-card mt-4"><h5 class="mb-3"><i class="fas fa-history me-2 text-primary"></i>Recent Transactions</h5><div class="table-responsive"><table class="table-custom"><thead><tr><th>Date</th><th>Invoice</th><th>Product</th><th>Quantity</th><th>Total</th></tr></thead><tbody><?php if($recent_sales && mysqli_num_rows($recent_sales) > 0): ?><?php while($row = mysqli_fetch_assoc($recent_sales)): ?><tr><td><?= date('M d, Y', strtotime($row['sale_date'])) ?></td><td><span class="badge-active"><?= $row['invoice_no'] ?></span></td><td><?= htmlspecialchars($row['product_name']) ?></td><td><?= $row['quantity'] ?></td><td class="text-primary fw-bold">$<?= number_format($row['total'], 2) ?></td></tr><?php endwhile; ?><?php else: ?><tr><td colspan="5" class="text-center text-muted py-4">No sales yet</td><?php endif; ?></tbody></table></div></div>
    <?php endif; ?>
</div>

<!-- Stock Alert Modal -->
<div id="stockModal" class="stock-modal"><div class="stock-modal-content"><div class="stock-modal-header"><h5><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products (<?= $low_stock_count ?>)</h5><button class="close-modal" onclick="closeStockModal()">&times;</button></div><div class="stock-modal-body"><?php if($low_stock_count > 0 && $low_stock_products): ?><?php while($product = mysqli_fetch_assoc($low_stock_products)): ?><div class="stock-item"><div class="d-flex justify-content-between align-items-start"><div><strong><?= htmlspecialchars($product['product_name']) ?></strong><br><small class="text-muted">SKU: <?= $product['sku'] ?> | Category: <?= $product['category_name'] ?? 'Uncategorized' ?></small></div><div class="text-end"><span class="stock-badge"><i class="fas fa-boxes me-1"></i> Stock: <?= $product['quantity'] ?></span><br><small class="text-muted">Alert: <?= $product['alert_quantity'] ?></small></div></div><div class="mt-2"><div class="progress" style="height: 8px;"><?php $pct = min(100, max(0, ($product['quantity'] / $product['alert_quantity']) * 100)); ?><div class="progress-bar <?= $pct < 30 ? 'bg-danger' : ($pct < 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= $pct ?>%"></div></div><div class="d-flex justify-content-between mt-2"><small>Price: $<?= number_format($product['price_selling'], 2) ?></small><a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Restock Now</a></div></div></div><?php endwhile; ?><?php else: ?><div class="text-center text-muted py-5"><i class="fas fa-check-circle fa-4x mb-3 text-success"></i><p>All products have sufficient stock!</p></div><?php endif; ?></div><div class="stock-modal-header" style="background: #475569;"><div></div><button class="close-modal" onclick="closeStockModal()" style="background: #ef4444; padding: 6px 20px; border-radius: 30px;">Close</button></div></div></div>

<script>
<?php if(hasPermission('dashboard_chart_view') || hasRole('admin')): ?>
new Chart(document.getElementById('salesChart'), { type: 'line', data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Sales ($)', data: <?= json_encode($chart_sales) ?>, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.08)', borderWidth: 3, fill: true, tension: 0.4 }, { label: 'Profit ($)', data: <?= json_encode($chart_profit) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)', borderWidth: 3, fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + v; } } } } } });
<?php endif; ?>

let calcValue = '0', calcResulted = false;
function updateCalcScreen() { document.getElementById('calcScreen').innerText = calcValue; }
function calcInput(v) { if(calcResulted) { calcValue = '0'; calcResulted = false; } calcValue = (calcValue === '0' && v !== '.') ? v : calcValue + v; updateCalcScreen(); }
function calcResult() { try { let r = eval(calcValue.replace(/×/g, '*').replace(/÷/g, '/')); calcValue = r.toString(); calcResulted = true; updateCalcScreen(); } catch(e) { calcValue = 'Error'; updateCalcScreen(); setTimeout(() => { calcValue = '0'; updateCalcScreen(); }, 1000); } }
function clearCalc() { calcValue = '0'; calcResulted = false; updateCalcScreen(); }
function showStockModal() { document.getElementById('stockModal').style.display = 'flex'; }
function closeStockModal() { document.getElementById('stockModal').style.display = 'none'; }
window.onclick = function(e) { let m = document.getElementById('stockModal'); if(e.target == m) m.style.display = 'none'; }
</script>
</body>
</html>