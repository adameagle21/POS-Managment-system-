<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ============================================
// PERMISSION CHECK
// ============================================
if(!hasPermission('reports_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

// Get data based on report type
$sales_data = mysqli_query($conn, "SELECT s.*, p.product_name, p.sku 
                                   FROM sales s 
                                   JOIN products p ON s.product_id = p.id 
                                   WHERE s.sale_date BETWEEN '$from_date' AND '$to_date' 
                                   ORDER BY s.sale_date DESC");

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total, SUM(profit) as profit, COUNT(*) as count 
                                                FROM sales WHERE sale_date BETWEEN '$from_date' AND '$to_date'"));

// Chart data for sales trend
$chart_labels = [];
$chart_values = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($date));
    $day_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM sales WHERE sale_date='$date'"));
    $chart_values[] = $day_total['total'];
}

// For product stock report
$stock_data = mysqli_query($conn, "SELECT p.*, c.name as category_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   ORDER BY p.quantity ASC");

// For top selling products
$top_products = mysqli_query($conn, "SELECT p.product_name, SUM(s.quantity) as total_sold, SUM(s.total) as total_revenue 
                                     FROM sales s 
                                     JOIN products p ON s.product_id = p.id 
                                     WHERE s.sale_date BETWEEN '$from_date' AND '$to_date' 
                                     GROUP BY s.product_id 
                                     ORDER BY total_sold DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .filter-card{background:white;border-radius:16px;padding:20px;margin-bottom:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .summary-card{background:white;border-radius:16px;padding:20px;text-align:center;transition:0.3s;}
        .summary-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
        .summary-value{font-size:2rem;font-weight:800;}
        .chart-card{background:white;border-radius:20px;padding:20px;margin-bottom:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .data-table{background:white;border-radius:20px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .data-table th{background:#f8fafc;padding:15px;font-weight:600;border-bottom:2px solid #e2e8f0;}
        .data-table td{padding:12px 15px;border-bottom:1px solid #e2e8f0;}
        .data-table tr:hover{background:#f8fafc;}
        .badge-invoice{background:#d1fae5;color:#059669;padding:4px 10px;border-radius:30px;font-size:0.7rem;}
        .btn-export{background:#10b981;color:white;border:none;padding:8px 16px;border-radius:8px;margin-left:10px;}
        .btn-export:hover{background:#059669;color:white;}
        .stock-good{background:#d1fae5;color:#059669;padding:4px 12px;border-radius:30px;font-size:0.7rem;}
        .stock-low{background:#fed7aa;color:#c2410c;padding:4px 12px;border-radius:30px;font-size:0.7rem;}
        .stock-out{background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:30px;font-size:0.7rem;}
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
        <?php if(hasPermission('users_access') || hasRole('admin')): ?>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <?php endif; ?>
        <?php if(hasPermission('reports_access') || hasRole('admin')): ?>
        <a href="reports.php" class="menu-item active"><i class="fas fa-file-alt"></i> Reports</a>
        <?php endif; ?>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-chart-bar me-2"></i>Reports Center</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Report Type</label>
                <select name="report_type" class="form-control">
                    <option value="sales" <?= $report_type == 'sales' ? 'selected' : '' ?>>Sales Report</option>
                    <option value="stock" <?= $report_type == 'stock' ? 'selected' : '' ?>>Stock Report</option>
                    <option value="top_products" <?= $report_type == 'top_products' ? 'selected' : '' ?>>Top Selling Products</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Generate</button>
            </div>
        </form>
    </div>

    <?php if($report_type == 'sales'): ?>
    <!-- Sales Report -->
    <div class="row mb-4">
        <div class="col-md-4"><div class="summary-card"><div class="summary-value text-primary">$<?= number_format($total['total'] ?? 0,2) ?></div><div>Total Sales</div><small><?= $from_date ?> to <?= $to_date ?></small></div></div>
        <div class="col-md-4"><div class="summary-card"><div class="summary-value text-success">$<?= number_format($total['profit'] ?? 0,2) ?></div><div>Total Profit</div><small>Gross profit</small></div></div>
        <div class="col-md-4"><div class="summary-card"><div class="summary-value text-info"><?= number_format($total['count'] ?? 0) ?></div><div>Transactions</div><small>Number of sales</small></div></div>
    </div>

    <!-- Sales Trend Chart -->
    <div class="chart-card">
        <h5><i class="fas fa-chart-line me-2"></i>Sales Trend (Last 7 Days)</h5>
        <canvas id="salesChart" height="200"></canvas>
    </div>

    <!-- Sales Data Table -->
    <div class="data-table">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong><i class="fas fa-list me-2"></i>Sales Details</strong>
            <button class="btn-export" onclick="exportToCSV()"><i class="fas fa-download me-1"></i> Export CSV</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="salesTable">
                <thead>
                    <tr><th>Date</th><th>Invoice</th><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Profit</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($sales_data) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($sales_data)): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($row['sale_date'])) ?></td>
                            <td><span class="badge-invoice"><?= $row['invoice_no'] ?></span></td>
                            <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                            <td><small class="text-muted"><?= $row['sku'] ?></small></td>
                            <td><?= $row['quantity'] ?></td>
                            <td>$<?= number_format($row['unit_price'],2) ?></td>
                            <td class="fw-bold text-primary">$<?= number_format($row['total'],2) ?></td>
                            <td class="text-success">$<?= number_format($row['profit'],2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No sales data found for selected period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'stock'): ?>
    <!-- Stock Report -->
    <div class="data-table">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong><i class="fas fa-boxes me-2"></i>Current Stock Status</strong>
            <button class="btn-export" onclick="exportStockToCSV()"><i class="fas fa-download me-1"></i> Export CSV</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="stockTable">
                <thead>
                    <tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Stock</th><th>Alert Level</th><th>Status</th><th>Price</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($stock_data) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($stock_data)): 
                            $stock_class = $row['quantity'] <= 0 ? 'stock-out' : ($row['quantity'] <= $row['alert_quantity'] ? 'stock-low' : 'stock-good');
                            $stock_text = $row['quantity'] <= 0 ? 'Out of Stock' : ($row['quantity'] <= $row['alert_quantity'] ? 'Low Stock' : 'In Stock');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                            <td><?= $row['sku'] ?></td>
                            <td><?= $row['category_name'] ?? 'Uncategorized' ?></td>
                            <td><?= $row['quantity'] ?> units</td>
                            <td><?= $row['alert_quantity'] ?></td>
                            <td><span class="<?= $stock_class ?>"><?= $stock_text ?></span></td>
                            <td>$<?= number_format($row['price_selling'],2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No products found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'top_products'): ?>
    <!-- Top Selling Products Report -->
    <div class="data-table">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong><i class="fas fa-trophy me-2"></i>Top Selling Products (<?= $from_date ?> to <?= $to_date ?>)</strong>
            <button class="btn-export" onclick="exportTopProducts()"><i class="fas fa-download me-1"></i> Export CSV</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="topProductsTable">
                <thead>
                    <td><th>#</th><th>Product Name</th><th>Total Quantity Sold</th><th>Total Revenue</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($top_products) > 0): ?>
                        <?php $rank = 1; while($row = mysqli_fetch_assoc($top_products)): ?>
                        <tr>
                            <td><strong><?= $rank++ ?></strong></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= $row['total_sold'] ?> units</td>
                            <td class="text-primary fw-bold">$<?= number_format($row['total_revenue'],2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No sales data found for selected period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
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
    menuToggle.addEventListener('click', function() { sidebar.classList.toggle('active'); });
}

<?php if($report_type == 'sales'): ?>
// Sales Chart
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{ label: 'Sales Amount ($)', data: <?= json_encode($chart_values) ?>, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.1)', borderWidth: 3, fill: true, tension: 0.4 }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + v; } } } } }
});

function exportToCSV() {
    let csv = [];
    let rows = document.querySelectorAll('#salesTable tr');
    for(let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for(let j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    let link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    link.download = 'sales_report_<?= date('Y-m-d') ?>.csv';
    link.click();
}
<?php endif; ?>

<?php if($report_type == 'stock'): ?>
function exportStockToCSV() {
    let csv = [];
    let rows = document.querySelectorAll('#stockTable tr');
    for(let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for(let j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    let link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    link.download = 'stock_report_<?= date('Y-m-d') ?>.csv';
    link.click();
}
<?php endif; ?>

<?php if($report_type == 'top_products'): ?>
function exportTopProducts() {
    let csv = [];
    let rows = document.querySelectorAll('#topProductsTable tr');
    for(let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for(let j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    let link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    link.download = 'top_products_report_<?= date('Y-m-d') ?>.csv';
    link.click();
}
<?php endif; ?>
</script>
</body>
</html>