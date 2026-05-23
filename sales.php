<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

// ============================================
// PERMISSION CHECKS
// ============================================
if(!hasPermission('sales_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// Handle delete sale - delete entire invoice
if(isset($_GET['delete_invoice'])) {
    if(!hasPermission('sales_delete') && !hasRole('admin')) {
        $_SESSION['sales_msg'] = "<div class='alert alert-danger'>✗ You don't have permission to delete sales!</div>";
        header("Location: sales.php");
        exit();
    }
    
    $invoice_no = $_GET['delete_invoice'];
    $items = mysqli_query($conn, "SELECT product_id, quantity FROM sales WHERE invoice_no = '$invoice_no'");
    while($item = mysqli_fetch_assoc($items)) {
        mysqli_query($conn, "UPDATE products SET quantity = quantity + {$item['quantity']} WHERE id = {$item['product_id']}");
    }
    mysqli_query($conn, "DELETE FROM sales WHERE invoice_no = '$invoice_no'");
    $_SESSION['sales_msg'] = "<div class='alert alert-success'>✓ Invoice #$invoice_no deleted successfully! Stock restored.</div>";
    header("Location: sales.php");
    exit();
}

// Handle delete single item from invoice
if(isset($_GET['delete_item'])) {
    if(!hasPermission('sales_delete') && !hasRole('admin')) {
        $_SESSION['sales_msg'] = "<div class='alert alert-danger'>✗ You don't have permission to delete sales items!</div>";
        header("Location: sales.php");
        exit();
    }
    
    $id = $_GET['delete_item'];
    $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_id, quantity, invoice_no FROM sales WHERE id = $id"));
    if($sale) {
        mysqli_query($conn, "UPDATE products SET quantity = quantity + {$sale['quantity']} WHERE id = {$sale['product_id']}");
    }
    mysqli_query($conn, "DELETE FROM sales WHERE id = $id");
    
    $remaining = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM sales WHERE invoice_no = '{$sale['invoice_no']}'"));
    if($remaining == 0) {
        $_SESSION['sales_msg'] = "<div class='alert alert-success'>✓ Item deleted successfully. Invoice is now empty.</div>";
    } else {
        $_SESSION['sales_msg'] = "<div class='alert alert-success'>✓ Item deleted successfully!</div>";
    }
    header("Location: sales.php");
    exit();
}

// Handle edit sale item
if(isset($_POST['update_sale_item'])) {
    if(!hasPermission('sales_edit') && !hasRole('admin')) {
        $_SESSION['sales_msg'] = "<div class='alert alert-danger'>✗ You don't have permission to edit sales!</div>";
        header("Location: sales.php");
        exit();
    }
    
    $sale_id = $_POST['sale_id'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $total = $quantity * $unit_price;
    
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_id, quantity as old_qty FROM sales WHERE id = $sale_id"));
    $product_id = $current['product_id'];
    $old_qty = $current['old_qty'];
    
    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_regular, price_last FROM products WHERE id = $product_id"));
    $cost = $product['price_regular'];
    $profit = $total - ($cost * $quantity);
    
    $qty_diff = $quantity - $old_qty;
    mysqli_query($conn, "UPDATE products SET quantity = quantity - $qty_diff WHERE id = $product_id");
    mysqli_query($conn, "UPDATE sales SET quantity = $quantity, unit_price = $unit_price, total = $total, profit = $profit WHERE id = $sale_id");
    
    $_SESSION['sales_msg'] = "<div class='alert alert-success'>✓ Sale item updated successfully!</div>";
    header("Location: sales.php");
    exit();
}

// Clear manual cart
if(isset($_POST['clear_manual_cart'])) {
    unset($_SESSION['manual_cart']);
    unset($_SESSION['manual_cart_date']);
    header("Location: sales.php");
    exit();
}

// Complete manual sale
if(isset($_POST['complete_manual_sale'])) {
    $payment_method = $_POST['payment_method'];
    $sale_date = $_SESSION['manual_cart_date'] ?? date('Y-m-d');
    $invoice_no = 'MAN-' . date('Ymd') . '-' . rand(1000, 9999);
    $total_amount = 0;
    $total_profit = 0;
    
    foreach($_SESSION['manual_cart'] as $item) {
        $total = $item['quantity'] * $item['unit_price'];
        $profit = $total - ($item['cost'] * $item['quantity']);
        $total_amount += $total;
        $total_profit += $profit;
        
        mysqli_query($conn, "UPDATE products SET quantity = quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
        mysqli_query($conn, "INSERT INTO sales (invoice_no, sale_date, product_id, quantity, unit_price, total, profit) 
                            VALUES ('$invoice_no', '$sale_date', '{$item['product_id']}', '{$item['quantity']}', '{$item['unit_price']}', '$total', '$profit')");
    }
    
    unset($_SESSION['manual_cart']);
    unset($_SESSION['manual_cart_date']);
    
    $_SESSION['sales_msg'] = "<div class='alert alert-success'>✓ Sale completed successfully! Invoice: $invoice_no | Total: $" . number_format($total_amount, 2) . " | Payment: $payment_method | Profit: $" . number_format($total_profit, 2) . "</div>";
    header("Location: sales.php");
    exit();
}

// Get filter values
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$invoice_query = "SELECT invoice_no, sale_date, SUM(total) as total_amount, SUM(profit) as total_profit, COUNT(*) as item_count 
                  FROM sales 
                  WHERE sale_date BETWEEN '$from_date' AND '$to_date'";

if($search) {
    $invoice_query .= " AND invoice_no LIKE '%$search%'";
}
$invoice_query .= " GROUP BY invoice_no, sale_date ORDER BY sale_date DESC, invoice_no DESC";
$invoices = mysqli_query($conn, $invoice_query);

$total_sales = 0;
$total_profit = 0;
$invoice_count = 0;
while($inv = mysqli_fetch_assoc($invoices)) {
    $total_sales += $inv['total_amount'];
    $total_profit += $inv['total_profit'];
    $invoice_count++;
}
mysqli_data_seek($invoices, 0);

// Get products for dropdown
$products = mysqli_query($conn, "SELECT id, product_name, sku, price_selling, price_last, quantity FROM products WHERE quantity > 0 ORDER BY product_name");

// Calculate cart total
$cart_total = 0;
$cart_items = $_SESSION['manual_cart'] ?? [];
foreach($cart_items as $item) {
    $cart_total += $item['quantity'] * $item['unit_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Adam Car Accessories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        .sidebar { width: 280px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: fixed; height: 100vh; padding: 20px 0; overflow-y: auto; }
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
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        
        .stat-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; }
        .stat-icon.primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .stat-value { font-size: 1.8rem; font-weight: 800; }
        .stat-label { color: #64748b; font-size: 0.85rem; }
        
        .filter-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .filter-input { border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px 15px; width: 100%; transition: 0.3s; }
        .filter-input:focus { border-color: #4f46e5; outline: none; }
        .btn-filter { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; }
        
        .data-table { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .data-table th { background: #f8fafc; padding: 15px; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #e2e8f0; }
        .data-table tr:hover { background: #f8fafc; }
        
        .badge-invoice { background: #d1fae5; color: #059669; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .btn-view, .btn-edit-item, .btn-print, .btn-delete, .btn-view-items { padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; margin: 2px; border: none; cursor: pointer; }
        .btn-view { background: #3b82f6; color: white; }
        .btn-view-items { background: #8b5cf6; color: white; }
        .btn-edit-item { background: #f59e0b; color: white; }
        .btn-print { background: #64748b; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        
        .modal-custom { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 24px; max-width: 90%; padding: 25px; animation: slideIn 0.3s ease; max-height: 85vh; overflow-y: auto; }
        .modal-large { width: 850px; }
        .modal-sm { width: 500px; }
        @keyframes slideIn { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .cart-item { background: #f8fafc; border-radius: 12px; padding: 12px; margin-bottom: 10px; border-left: 3px solid #4f46e5; }
        .payment-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .btn-payment { flex: 1; padding: 10px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; }
        .btn-payment:hover { transform: translateY(-2px); }
        .btn-cash { background: #10b981; color: white; }
        .btn-card { background: #3b82f6; color: white; }
        .btn-multiple { background: #8b5cf6; color: white; }
        .btn-credit { background: #f59e0b; color: white; }
        .btn-complete { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 700; width: 100%; margin-top: 15px; cursor: pointer; }
        .btn-complete:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #f8fafc; padding: 10px; font-size: 0.8rem; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 0.85rem; }
        .discount-badge { background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; display: inline-block; margin-left: 5px; }
        .edit-form-input, .add-form-input { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 10px; margin-top: 5px; }
        
        .price-warning { color: #ef4444; font-size: 0.7rem; margin-top: 5px; display: none; }
        
        @media (max-width: 992px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; } .main-content { margin-left: 0; } .sidebar.active { transform: translateX(0); } }
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
        <a href="sales.php" class="menu-item active"><i class="fas fa-chart-line"></i> Sales</a>
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
        <h1 class="page-title"><i class="fas fa-chart-line me-2"></i>Sales Management</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </div>
    </div>
    
    <?php if(isset($_SESSION['sales_msg'])): echo $_SESSION['sales_msg']; unset($_SESSION['sales_msg']); endif; ?>
    
    <div class="row">
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon primary"><i class="fas fa-dollar-sign"></i></div><div class="stat-value">$<?= number_format($total_sales, 2) ?></div><div class="stat-label">Total Sales</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon success"><i class="fas fa-chart-line"></i></div><div class="stat-value">$<?= number_format($total_profit, 2) ?></div><div class="stat-label">Total Profit</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon warning"><i class="fas fa-receipt"></i></div><div class="stat-value"><?= $invoice_count ?></div><div class="stat-label">Orders</div></div></div>
    </div>
    
    <!-- Add Manual Sale Button -->
    <?php if(hasPermission('sales_add') || hasRole('admin')): ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn-filter" onclick="showAddSaleModal()" style="background: #10b981;">
            <i class="fas fa-plus-circle me-1"></i> New Manual Sale
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Add Manual Sale Modal -->
    <div id="addSaleModal" class="modal-custom">
        <div class="modal-content modal-large">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2 text-success"></i>Manual Sale - Add Items</h5>
                <button onclick="closeAddSaleModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3"><label>Sale Date *</label><input type="date" id="manual_sale_date" class="add-form-input" value="<?= $_SESSION['manual_cart_date'] ?? date('Y-m-d') ?>"></div>
                    <div class="mb-3"><label>Select Product *</label><select id="manual_product_id" class="add-form-input"><option value="">-- Select Product --</option><?php while($prod = mysqli_fetch_assoc($products)): ?><option value="<?= $prod['id'] ?>" data-price="<?= $prod['price_selling'] ?>" data-lastprice="<?= $prod['price_last'] ?>" data-stock="<?= $prod['quantity'] ?>" data-name="<?= htmlspecialchars($prod['product_name']) ?>" data-sku="<?= $prod['sku'] ?>" data-cost="<?= $prod['price_regular'] ?>"><?= htmlspecialchars($prod['product_name']) ?> (<?= $prod['sku'] ?>) - Stock: <?= $prod['quantity'] ?> - $<?= number_format($prod['price_selling'], 2) ?><?php if($prod['price_last'] > 0): ?><span class="text-danger"> (Last: $<?= number_format($prod['price_last'], 2) ?>)</span><?php endif; ?></option><?php endwhile; ?></select></div>
                    <div class="row"><div class="col-md-6"><div class="mb-3"><label>Quantity</label><input type="number" id="manual_qty" class="add-form-input" value="1" min="1"></div></div><div class="col-md-6"><div class="mb-3"><label>Unit Price ($)</label><input type="number" step="0.01" id="manual_price" class="add-form-input"><div id="priceWarning" class="price-warning"></div></div></div></div>
                    <button class="btn-filter w-100" onclick="addToManualCart()" style="background: #4f46e5;"><i class="fas fa-cart-plus me-1"></i> Add Item to Cart</button>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2"><i class="fas fa-shopping-basket me-2"></i>Current Cart (<span id="cartCount"><?= count($cart_items) ?></span> items)</h6>
                    <div id="manualCartItems" style="max-height: 300px; overflow-y: auto;"><?php if(count($cart_items) > 0): ?><?php foreach($cart_items as $idx => $item): ?><div class="cart-item" data-index="<?= $idx ?>"><div class="d-flex justify-content-between align-items-start"><div><strong><?= htmlspecialchars($item['product_name']) ?></strong><br><small><?= $item['sku'] ?> | Qty: <?= $item['quantity'] ?> x $<?= number_format($item['unit_price'], 2) ?></small></div><div class="text-end"><strong>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></strong><br><a href="#" class="text-danger small" onclick="removeCartItem(<?= $idx ?>); return false;">Remove</a></div></div></div><?php endforeach; ?><?php else: ?><p class="text-muted text-center" id="emptyCartMsg">Cart is empty</p><?php endif; ?></div>
                    <hr><div class="d-flex justify-content-between"><strong>Total:</strong><strong class="text-primary" id="cartTotal">$<?= number_format($cart_total, 2) ?></strong></div>
                    <div class="mt-3"><label class="fw-semibold">Payment Method</label><div class="payment-buttons"><button type="button" class="btn-payment btn-cash" onclick="selectPaymentMethod('Cash')">Cash</button><button type="button" class="btn-payment btn-card" onclick="selectPaymentMethod('Card')">Card</button><button type="button" class="btn-payment btn-multiple" onclick="selectPaymentMethod('Multiple')">Multiple</button><button type="button" class="btn-payment btn-credit" onclick="selectPaymentMethod('Credit')">Credit</button></div>
                    <button type="button" class="btn-complete" id="completeSaleBtn" onclick="completeManualSale()" <?= count($cart_items) == 0 ? 'disabled' : '' ?>><i class="fas fa-check-circle me-2"></i> COMPLETE SALE</button>
                    <?php if(count($cart_items) > 0): ?><button type="button" class="btn-delete w-100 mt-2" onclick="clearManualCart()">Clear Cart</button><?php endif; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="filter-card">
        <form method="GET" class="row g-3">
            <div class="col-md-4"><label class="form-label fw-semibold">From Date</label><input type="date" name="from_date" class="filter-input" value="<?= $from_date ?>"></div>
            <div class="col-md-4"><label class="form-label fw-semibold">To Date</label><input type="date" name="to_date" class="filter-input" value="<?= $to_date ?>"></div>
            <div class="col-md-4"><label class="form-label fw-semibold">Search Invoice #</label><input type="text" name="search" class="filter-input" placeholder="Invoice number..." value="<?= htmlspecialchars($search) ?>"></div>
            <div class="col-md-12"><button type="submit" class="btn-filter w-100"><i class="fas fa-filter me-2"></i>Filter Orders</button></div>
        </form>
    </div>
    
    <div class="data-table">
        <table class="table mb-0">
            <thead><tr><th>Invoice #</th><th>Date</th><th>Items</th><th>Total Amount</th><th>Total Profit</th><th>Actions</th></tr></thead>
            <tbody><?php if(mysqli_num_rows($invoices) > 0): ?><?php while($inv = mysqli_fetch_assoc($invoices)): ?><tr><td><span class="badge-invoice"><i class="fas fa-receipt me-1"></i><?= $inv['invoice_no'] ?></span></td><td><?= date('M d, Y', strtotime($inv['sale_date'])) ?> <br><small class="text-muted"><?= date('h:i A', strtotime($inv['sale_date'])) ?></small></td><td><?= $inv['item_count'] ?> items</td><td class="fw-bold text-primary">$<?= number_format($inv['total_amount'], 2) ?></td><td class="text-success">$<?= number_format($inv['total_profit'], 2) ?></td><td><button class="btn-view-items" onclick="viewInvoiceItems('<?= $inv['invoice_no'] ?>')"><i class="fas fa-eye"></i> View Items</button><button class="btn-print" onclick="printInvoice('<?= $inv['invoice_no'] ?>', '<?= $inv['sale_date'] ?>', <?= $inv['total_amount'] ?>)"><i class="fas fa-print"></i> Print</button><?php if(hasPermission('sales_delete') || hasRole('admin')): ?><button class="btn-delete" onclick="deleteInvoice('<?= $inv['invoice_no'] ?>')"><i class="fas fa-trash"></i> Delete Order</button><?php endif; ?></td></tr><?php endwhile; ?><?php else: ?><tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i><p>No orders found</p></td></tr><?php endif; ?></tbody>
        </table>
    </div>
</div>

<!-- Invoice Items Modal - UPDATED with Discount Display -->
<div id="invoiceModal" class="modal-custom">
    <div class="modal-content modal-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Invoice Details: <span id="invoiceNumber"></span></h5>
            <button onclick="closeModal('invoiceModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
        </div>
        <div id="invoiceItemsBody"></div>
        <hr>
        <div class="d-flex justify-content-between"><strong>Subtotal:</strong><strong class="text-primary" id="invoiceSubtotal">$0.00</strong></div>
        <div class="d-flex justify-content-between mt-1"><strong>Discount Savings:</strong><strong class="text-success" id="invoiceDiscountTotal">$0.00</strong></div>
        <div class="d-flex justify-content-between mt-1"><strong>Total Paid:</strong><strong class="fw-bold text-primary fs-5" id="invoiceTotal">$0.00</strong></div>
        <div class="d-flex justify-content-between mt-2"><strong>Total Profit:</strong><strong class="text-success" id="invoiceProfit">$0.00</strong></div>
        <hr>
        <button onclick="closeModal('invoiceModal')" class="btn-filter w-100">Close</button>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal-custom"><div class="modal-content modal-sm"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Sale Item</h5><button onclick="closeModal('editItemModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button></div><form method="POST"><input type="hidden" name="sale_id" id="edit_sale_id"><div class="mb-3"><label>Product</label><input type="text" id="edit_product_name" class="form-control" disabled></div><div class="mb-3"><label>Quantity</label><input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required></div><div class="mb-3"><label>Unit Price ($)</label><input type="number" step="0.01" name="unit_price" id="edit_unit_price" class="form-control" required></div><div class="d-flex gap-2"><button type="submit" name="update_sale_item" class="btn-filter w-100">Update</button><button type="button" onclick="closeModal('editItemModal')" class="btn-delete w-100">Cancel</button></div></form></div></div>

<script>
let selectedPaymentMethod = null;

function showAddSaleModal() { document.getElementById('addSaleModal').style.display = 'flex'; refreshCartDisplay(); }
function closeAddSaleModal() { document.getElementById('addSaleModal').style.display = 'none'; }

document.getElementById('manual_product_id').addEventListener('change', function() {
    let selectedOption = this.options[this.selectedIndex];
    let price = selectedOption.getAttribute('data-price');
    let lastPrice = parseFloat(selectedOption.getAttribute('data-lastprice')) || 0;
    if(price) document.getElementById('manual_price').value = price;
    let priceWarning = document.getElementById('priceWarning');
    if(lastPrice > 0) { priceWarning.style.display = 'block'; priceWarning.innerHTML = '⚠️ Last Price: $' + lastPrice.toFixed(2) + ' - Selling price cannot be below this!'; }
    else { priceWarning.style.display = 'none'; }
});

function validatePrice(price, lastPrice) {
    if(lastPrice > 0 && price < lastPrice) {
        Swal.fire({ icon: 'error', title: 'Price Validation Failed!', text: `Selling price ($${price.toFixed(2)}) cannot be less than Last Price ($${lastPrice.toFixed(2)})`, confirmButtonColor: '#ef4444' });
        return false;
    }
    return true;
}

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.btn-payment').forEach(btn => { btn.classList.remove('active'); if(btn.innerText.trim() === method) btn.classList.add('active'); });
    Swal.fire({ icon: 'success', title: 'Payment Method Selected', text: method, timer: 1500, showConfirmButton: false });
}

function addToManualCart() {
    let select = document.getElementById('manual_product_id');
    let selectedOption = select.options[select.selectedIndex];
    if(!selectedOption.value) { Swal.fire('Error', 'Please select a product', 'error'); return; }
    let productId = selectedOption.value;
    let price = parseFloat(document.getElementById('manual_price').value);
    let qty = parseInt(document.getElementById('manual_qty').value);
    let stock = parseInt(selectedOption.getAttribute('data-stock'));
    let lastPrice = parseFloat(selectedOption.getAttribute('data-lastprice')) || 0;
    let saleDate = document.getElementById('manual_sale_date').value;
    if(isNaN(price) || price <= 0) { Swal.fire('Error', 'Please enter valid price', 'error'); return; }
    if(!validatePrice(price, lastPrice)) return;
    if(qty > stock) { Swal.fire('Error', 'Insufficient stock! Only ' + stock + ' units available', 'error'); return; }
    $.ajax({ url: 'ajax_sales.php', method: 'POST', data: { ajax_add_to_cart: 1, product_id: productId, quantity: qty, unit_price: price, sale_date: saleDate }, dataType: 'json', success: function(response) { if(response.success) { Swal.fire('Success', response.message, 'success'); refreshCartDisplay(); document.getElementById('manual_qty').value = 1; } else { Swal.fire('Error', response.message, 'error'); } }, error: function() { Swal.fire('Error', 'Failed to add item to cart', 'error'); } });
}

function removeCartItem(index) { $.ajax({ url: 'ajax_sales.php', method: 'POST', data: { ajax_remove_cart_item: 1, index: index }, dataType: 'json', success: function(response) { if(response.success) refreshCartDisplay(); } }); }

function refreshCartDisplay() {
    $.ajax({ url: 'ajax_sales.php', method: 'POST', data: { ajax_get_cart: 1 }, dataType: 'json', success: function(data) {
        let cartHtml = '', cartTotal = 0;
        if(data.items && data.items.length > 0) {
            data.items.forEach(function(item, idx) {
                let itemTotal = item.quantity * item.unit_price;
                cartTotal += itemTotal;
                cartHtml += `<div class="cart-item" data-index="${idx}"><div class="d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(item.product_name)}</strong><br><small>${escapeHtml(item.sku)} | Qty: ${item.quantity} x $${item.unit_price.toFixed(2)}</small></div><div class="text-end"><strong>$${itemTotal.toFixed(2)}</strong><br><a href="#" class="text-danger small" onclick="removeCartItem(${idx}); return false;">Remove</a></div></div></div>`;
            });
            document.getElementById('manualCartItems').innerHTML = cartHtml;
            document.getElementById('cartCount').innerText = data.items.length;
            document.getElementById('cartTotal').innerHTML = '$' + cartTotal.toFixed(2);
            document.getElementById('completeSaleBtn').disabled = false;
        } else {
            document.getElementById('manualCartItems').innerHTML = '<p class="text-muted text-center">Cart is empty</p>';
            document.getElementById('cartCount').innerText = '0';
            document.getElementById('cartTotal').innerHTML = '$0.00';
            document.getElementById('completeSaleBtn').disabled = true;
        }
    } });
}

function clearManualCart() {
    Swal.fire({ title: 'Clear Cart?', text: 'All items will be removed', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, clear' }).then((result) => { if(result.isConfirmed) { let form = document.createElement('form'); form.method = 'POST'; form.innerHTML = '<input type="hidden" name="clear_manual_cart" value="1">'; document.body.appendChild(form); form.submit(); } });
}

function completeManualSale() {
    if(!selectedPaymentMethod) { Swal.fire({ icon: 'error', title: 'No Payment Method Selected', text: 'Please select a payment method: Cash, Card, Multiple, or Credit', confirmButtonColor: '#ef4444' }); return; }
    const cartCount = parseInt(document.getElementById('cartCount').innerText);
    if(cartCount === 0) { Swal.fire('Error', 'Cart is empty', 'error'); return; }
    Swal.fire({ title: 'Complete Sale?', text: `Payment Method: ${selectedPaymentMethod}`, icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981', confirmButtonText: 'Yes, complete' }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            $.ajax({ url: 'ajax_sales.php', method: 'POST', data: { ajax_complete_sale: 1, payment_method: selectedPaymentMethod }, dataType: 'json', success: function(response) {
                if(response.success) { Swal.fire({ icon: 'success', title: 'Sale Completed!', html: response.message.replace(/\n/g, '<br>'), confirmButtonColor: '#10b981' }).then(() => { closeAddSaleModal(); location.reload(); }); }
                else { Swal.fire('Error', response.message, 'error'); }
            }, error: function() { Swal.fire('Error', 'Failed to complete sale', 'error'); } });
        }
    });
}

function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m) { if(m === '&') return '&amp;'; if(m === '<') return '&lt;'; if(m === '>') return '&gt;'; return m; }); }

// Updated viewInvoiceItems with discount display
function viewInvoiceItems(invoiceNo) {
    document.getElementById('invoiceNumber').innerText = invoiceNo;
    document.getElementById('invoiceModal').style.display = 'flex';
    
    $.ajax({
        url: 'get_invoice_items.php',
        method: 'GET',
        data: {invoice_no: invoiceNo},
        success: function(data) {
            let items = JSON.parse(data);
            let html = '<table class="items-table"><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Total</th><th>Profit</th><th>Actions</th></tr></thead><tbody>';
            let subtotal = 0;
            let totalDiscount = 0;
            let totalProfit = 0;
            
            items.forEach(function(item) {
                subtotal += parseFloat(item.total);
                totalProfit += parseFloat(item.profit);
                if(item.has_discount) {
                    totalDiscount += parseFloat(item.discount_saved) * item.quantity;
                }
                html += `<tr>
                    <td><strong>${item.product_name}</strong></td>
                    <td><small>${item.sku}</small></td>
                    <td>${item.quantity}</td>
                    <td>`;
                if(item.has_discount) {
                    html += `<span class="text-muted" style="text-decoration:line-through;">$${parseFloat(item.original_price).toFixed(2)}</span><br>
                             <strong class="text-success">$${parseFloat(item.discounted_price).toFixed(2)}</strong>`;
                } else {
                    html += `$${parseFloat(item.unit_price).toFixed(2)}`;
                }
                html += `</td>
                    <td>`;
                if(item.has_discount) {
                    html += `<span class="discount-badge"><i class="fas fa-tag"></i> -${item.discount_percent}% ($${parseFloat(item.discount_saved).toFixed(2)} off)</span>`;
                } else {
                    html += `<span class="text-muted">-</span>`;
                }
                html += `</td>
                    <td class="text-primary fw-bold">$${parseFloat(item.total).toFixed(2)}</td>
                    <td class="text-success">$${parseFloat(item.profit).toFixed(2)}</td>
                    <td>
                        <?php if(hasPermission('sales_edit') || hasRole('admin')): ?>
                        <button class="btn-edit-item" onclick="editSaleItem(${item.id}, '${item.product_name}', ${item.quantity}, ${item.unit_price})"><i class="fas fa-edit"></i> Edit</button>
                        <?php endif; ?>
                        <?php if(hasPermission('sales_delete') || hasRole('admin')): ?>
                        <a href="?delete_item=${item.id}" class="btn-delete" onclick="return confirm('Delete this item?')"><i class="fas fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('invoiceItemsBody').innerHTML = html;
            document.getElementById('invoiceSubtotal').innerHTML = '$' + subtotal.toFixed(2);
            document.getElementById('invoiceDiscountTotal').innerHTML = '-$' + totalDiscount.toFixed(2);
            document.getElementById('invoiceTotal').innerHTML = '$' + (subtotal - totalDiscount).toFixed(2);
            document.getElementById('invoiceProfit').innerHTML = '$' + totalProfit.toFixed(2);
        }
    });
}

function editSaleItem(id, productName, qty, price) {
    document.getElementById('edit_sale_id').value = id;
    document.getElementById('edit_product_name').value = productName;
    document.getElementById('edit_quantity').value = qty;
    document.getElementById('edit_unit_price').value = price;
    document.getElementById('editItemModal').style.display = 'flex';
}

function deleteInvoice(invoiceNo) {
    Swal.fire({ title: 'Delete Entire Order?', text: "Stock will be restored!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete!' }).then((result) => { if(result.isConfirmed) { window.location.href = '?delete_invoice=' + invoiceNo; } });
}

function printInvoice(invoiceNo, date, total) {
    let w = window.open('', '_blank');
    w.document.write(`<html><head><title>Invoice - ${invoiceNo}</title><style>body{font-family:Arial;padding:30px}.invoice-header{text-align:center;margin-bottom:30px}.invoice-header h2{color:#4f46e5}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd}.total{font-size:1.2rem;font-weight:bold;text-align:right;margin-top:20px}.footer{text-align:center;margin-top:50px;font-size:0.8rem;color:#666}</style></head><body><div class='invoice-header'><h2>🚗 Adam Car Accessories</h2><p>Premium Car Accessories Store</p></div><div><strong>Invoice No:</strong> ${invoiceNo}<br><strong>Date:</strong> ${date}<br></div><div id="printItems"></div><div class='total'>Total: $${parseFloat(total).toFixed(2)}</div><div class='footer'><p>Thank you!</p></div></body></html>`);
    $.ajax({ url: 'get_invoice_items.php', method: 'GET', data: {invoice_no: invoiceNo}, async: false, success: function(data) {
        let items = JSON.parse(data);
        let html = '<table><thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead><tbody>';
        items.forEach(function(item) { html += `<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>$${parseFloat(item.unit_price).toFixed(2)}</td><td>$${parseFloat(item.total).toFixed(2)}</td></tr>`; });
        html += '</tbody><table>';
        w.document.getElementById('printItems').innerHTML = html;
    } });
    w.document.close(); w.print();
}

function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
window.onclick = function(event) { if(event.target.classList.contains('modal-custom')) event.target.style.display = 'none'; }
</script>
</body>
</html>