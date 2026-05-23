<?php
session_start();
require_once __DIR__ . '/db.php';

if(!isset($_SESSION['user_id'])) header("Location: login.php");

// Only admin can view this page
if(!hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// Get all Excel uploads
$uploads_query = "SELECT * FROM excel_uploads ORDER BY upload_date DESC";
$uploads = mysqli_query($conn, $uploads_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Uploads - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
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
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: rgba(79,70,229,0.2); color: white; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 12px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card { border: none; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-view { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 5px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>🚗 Adam Car</h3><p>Admin Panel</p></div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="pos.php" class="menu-item"><i class="fas fa-shopping-cart"></i> POS</a>
        <a href="products.php" class="menu-item"><i class="fas fa-boxes"></i> Products</a>
        <a href="sales.php" class="menu-item"><i class="fas fa-chart-line"></i> Sales</a>
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="excel_uploads.php" class="menu-item active"><i class="fas fa-file-excel"></i> 📊 Excel Uploads</a>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-file-excel text-success me-2"></i>Excel Uploads</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary">Admin</span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">📁 All Uploaded Excel Files</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Original File Name</th>
                            <th>File Size</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($uploads) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($uploads)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['original_name']) ?></td>
                                <td><?= $row['file_size'] ?></td>
                                <td><?= htmlspecialchars($row['uploaded_by']) ?></td>
                                <td><?= $row['upload_date'] ?></td>
                                <td>
                                    <a href="uploads/excel/<?= $row['file_name'] ?>" class="btn-view" target="_blank"><i class="fas fa-eye me-1"></i> View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-file-excel fa-3x mb-2 opacity-50"></i>
                                    <p>No Excel files uploaded yet.</p>
                                    <a href="pos.php" class="btn btn-sm btn-success">Go to POS to upload</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        if(sidebar) sidebar.classList.remove('active');
    } else {
        if(sidebar) sidebar.classList.add('active');
    }
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
</script>
</body>
</html>