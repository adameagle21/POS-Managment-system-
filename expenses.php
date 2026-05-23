<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
if(!hasPermission('expenses_access') && !hasRole('admin')) header("Location: index.php");

if(isset($_POST['add'])) {
    $expense_date = $_POST['expense_date'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $amount = $_POST['amount'];
    mysqli_query($conn, "INSERT INTO expenses (expense_date, category, description, amount) VALUES ('$expense_date', '$category', '$description', '$amount')");
    header("Location: expenses.php");
    exit();
}
if(isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM expenses WHERE id = ".$_GET['delete']);
    header("Location: expenses.php");
    exit();
}
$expenses = mysqli_query($conn, "SELECT * FROM expenses ORDER BY expense_date DESC");
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses"));
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Expenses - Adam Car</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.sidebar{width:280px;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);position:fixed;height:100vh;padding:20px 0;}
.sidebar-header{text-align:center;padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,0.1);}
.sidebar-header h3{color:#FFD700;}
.sidebar-menu{padding:0 15px;}
.menu-item{display:flex;align-items:center;gap:12px;padding:12px 18px;margin:5px 0;color:#cbd5e1;text-decoration:none;border-radius:12px;transition:0.3s;}
.menu-item:hover{background:rgba(255,255,255,0.1);color:white;}
.main-content{margin-left:280px;padding:20px;}
.top-bar{background:white;border-radius:16px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;}
.page-title{font-size:1.5rem;font-weight:700;color:#1e293b;}
.stat-card{background:white;border-radius:20px;padding:20px;text-align:center;margin-bottom:20px;}
.stat-value{font-size:2rem;font-weight:800;color:#ef4444;}
.data-table{background:white;border-radius:20px;overflow:hidden;}
.data-table th, .data-table td{padding:12px 15px;border-bottom:1px solid #e2e8f0;}
.btn-sm{padding:5px 10px;border-radius:8px;font-size:0.7rem;}
.btn-delete{background:#ef4444;color:white;}
@media (max-width:992px){.sidebar{transform:translateX(-100%);}.main-content{margin-left:0;}}
</style>
</head>
<body>
<div class="sidebar"><div class="sidebar-header"><h3>🚗 Adam Car</h3></div>
<div class="sidebar-menu">
<a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
<a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
<a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
<a href="discounts.php" class="menu-item"><i class="fas fa-tag"></i> Discounts</a>
<a href="categories.php" class="menu-item"><i class="fas fa-tags"></i> Categories</a>
<a href="expenses.php" class="menu-item active"><i class="fas fa-receipt"></i> Expenses</a>
<a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
<a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
<div class="menu-divider"></div><a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div></div>
<div class="main-content">
<div class="top-bar"><h1 class="page-title"><i class="fas fa-receipt me-2"></i>Expenses</h1><div><div class="user-avatar" style="width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div></div></div>

<div class="row"><div class="col-md-3"><div class="stat-card"><div class="stat-value">$<?= number_format($total['total'] ?? 0,2) ?></div><div>Total Expenses</div></div></div></div>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add Expense</button>

<div class="data-table"><table class="table"><thead><tr><th>ID</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Actions</th></tr></thead>
<tbody><?php while($row = mysqli_fetch_assoc($expenses)): ?>
<tr><td><?= $row['id'] ?></td><td><?= $row['expense_date'] ?></td><td><?= htmlspecialchars($row['category']) ?></td><td><?= htmlspecialchars($row['description']) ?></td><td class="text-danger fw-bold">$<?= number_format($row['amount'],2) ?></td>
<td><a href="?delete=<?= $row['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this expense?')"><i class="fas fa-trash"></i> Delete</a></td></tr>
<?php endwhile; ?></tbody></table></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body">
<div class="mb-2"><label>Date</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
<div class="mb-2"><label>Category</label><select name="category" class="form-control"><option>Rent</option><option>Utilities</option><option>Salaries</option><option>Maintenance</option><option>Marketing</option><option>Other</option></select></div>
<div class="mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label>Amount ($)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
</div><div class="modal-footer"><button type="submit" name="add" class="btn btn-primary">Save</button></div></form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>