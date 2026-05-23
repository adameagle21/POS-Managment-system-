<?php
session_start();
require_once __DIR__ . '/db.php';

if(!isset($_SESSION['user_id'])) header("Location: login.php");

// ============================================
// PERMISSION CHECKS FOR EXCEL UPLOADS PAGE
// ============================================

// Check if user has access to this page
if(!hasPermission('excel_uploads_access') && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

// ============================================
// HANDLE DELETE (Requires delete permission)
// ============================================
if(isset($_GET['delete'])) {
    if(!hasPermission('excel_uploads_delete') && !hasRole('admin')) {
        $_SESSION['upload_error'] = "❌ You don't have permission to delete files!";
        header("Location: excel_uploads.php");
        exit();
    }
    
    $id = (int)$_GET['delete'];
    
    // Get file name before deleting from database
    $query = "SELECT file_name FROM excel_uploads WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $file_path = 'uploads/excel/' . $row['file_name'];
        
        // Delete file from folder
        if(file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        mysqli_query($conn, "DELETE FROM excel_uploads WHERE id = $id");
        $_SESSION['upload_success'] = "✓ File deleted successfully!";
    }
    header("Location: excel_uploads.php");
    exit();
}

// ============================================
// HANDLE EDIT (RENAME) - Requires edit permission
// ============================================
if(isset($_POST['rename_file'])) {
    if(!hasPermission('excel_uploads_edit') && !hasRole('admin')) {
        $_SESSION['upload_error'] = "❌ You don't have permission to rename files!";
        header("Location: excel_uploads.php");
        exit();
    }
    
    $id = (int)$_POST['file_id'];
    $new_name = trim($_POST['new_name']);
    
    if(!empty($new_name)) {
        // Get current file info
        $query = "SELECT file_name, original_name FROM excel_uploads WHERE id = $id";
        $result = mysqli_query($conn, $query);
        if($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $old_file = 'uploads/excel/' . $row['file_name'];
            $ext = pathinfo($row['file_name'], PATHINFO_EXTENSION);
            $new_file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $new_name) . '.' . $ext;
            $new_file_path = 'uploads/excel/' . $new_file_name;
            
            // Rename file
            if(file_exists($old_file)) {
                rename($old_file, $new_file_path);
            }
            
            // Update database
            mysqli_query($conn, "UPDATE excel_uploads SET file_name = '$new_file_name', original_name = '$new_name' WHERE id = $id");
            $_SESSION['upload_success'] = "✓ File renamed successfully!";
        }
    }
    header("Location: excel_uploads.php");
    exit();
}

// ============================================
// GET ALL EXCEL UPLOADS
// ============================================

// If user is not admin, only show their own uploads (optional)
if(hasRole('admin') || hasPermission('excel_uploads_view_all')) {
    $uploads_query = "SELECT * FROM excel_uploads ORDER BY upload_date DESC";
} else {
    // Regular user sees only their own uploads
    $user_id = $_SESSION['user_id'];
    $uploads_query = "SELECT * FROM excel_uploads WHERE user_id = $user_id ORDER BY upload_date DESC";
}
$uploads = mysqli_query($conn, $uploads_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Uploads - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .sidebar-header p { color: #94a3b8; font-size: 0.7rem; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: rgba(79,70,229,0.2); color: white; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 12px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-title { font-size: 1.3rem; font-weight: 700; color: #1e293b; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .card { border: none; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* Action Buttons */
        .btn-view { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 5px 12px; border-radius: 8px; text-decoration: none; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; }
        .btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-edit:hover { background: linear-gradient(135deg, #d97706, #b45309); color: white; }
        .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-delete:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; }
        .btn-upload { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 400px; max-width: 90%; }
        
        .success-alert { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #10b981; }
        .error-alert { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #ef4444; }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
        }
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
        <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-file-alt"></i> Reports</a>
        <?php if(hasPermission('excel_uploads_access') || hasRole('admin')): ?>
        <a href="excel_uploads.php" class="menu-item active"><i class="fas fa-file-excel"></i> 📊 Excel Uploads</a>
        <?php endif; ?>
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-file-excel text-success me-2"></i>Excel Uploads Management</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['upload_success'])): ?>
        <div class="success-alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['upload_success'] ?>
        </div>
        <?php unset($_SESSION['upload_success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['upload_error'])): ?>
        <div class="error-alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['upload_error'] ?>
        </div>
        <?php unset($_SESSION['upload_error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-excel me-2"></i>Uploaded Excel Files</h5>
            <?php if(hasPermission('excel_uploads_upload') || hasRole('admin')): ?>
            <a href="pos.php" class="btn-upload"><i class="fas fa-upload"></i> Upload New Excel</a>
            <?php else: ?>
            <span class="text-muted small"><i class="fas fa-lock me-1"></i> No upload permission</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($uploads) && mysqli_num_rows($uploads) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($uploads)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['original_name']) ?></strong>
                                    <br><small class="text-muted"><?= $row['file_name'] ?></small>
                                </td>
                                <td><?= $row['file_size'] ?></td>
                                <td><?= htmlspecialchars($row['uploaded_by']) ?></td>
                                <td><?= $row['upload_date'] ?></td>
                                <td class="text-nowrap">
                                    <!-- View Button - Requires view permission -->
                                    <?php if(hasPermission('excel_uploads_view') || hasRole('admin')): ?>
                                    <a href="uploads/excel/<?= $row['file_name'] ?>" class="btn-view" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Edit Button - Requires edit permission -->
                                    <?php if(hasPermission('excel_uploads_edit') || hasRole('admin')): ?>
                                    <button class="btn-edit" onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['original_name']) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button - Requires delete permission -->
                                    <?php if(hasPermission('excel_uploads_delete') || hasRole('admin')): ?>
                                    <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['original_name']) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- If user has no permissions -->
                                    <?php if(!hasPermission('excel_uploads_view') && !hasPermission('excel_uploads_edit') && !hasPermission('excel_uploads_delete') && !hasRole('admin')): ?>
                                    <span class="text-muted small"><i class="fas fa-lock"></i> No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-file-excel fa-3x mb-2 opacity-50"></i>
                                    <p>No Excel files uploaded yet.</p>
                                    <?php if(hasPermission('excel_uploads_upload') || hasRole('admin')): ?>
                                    <a href="pos.php" class="btn btn-sm btn-success">Go to POS to upload</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Permission Info Card -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <i class="fas fa-eye text-primary"></i>
                    <div class="small">View</div>
                    <div class="badge bg-<?= (hasPermission('excel_uploads_view') || hasRole('admin')) ? 'success' : 'secondary' ?>">
                        <?= (hasPermission('excel_uploads_view') || hasRole('admin')) ? '✓ Allowed' : '✗ Denied' ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-upload text-success"></i>
                    <div class="small">Upload</div>
                    <div class="badge bg-<?= (hasPermission('excel_uploads_upload') || hasRole('admin')) ? 'success' : 'secondary' ?>">
                        <?= (hasPermission('excel_uploads_upload') || hasRole('admin')) ? '✓ Allowed' : '✗ Denied' ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-edit text-warning"></i>
                    <div class="small">Edit</div>
                    <div class="badge bg-<?= (hasPermission('excel_uploads_edit') || hasRole('admin')) ? 'success' : 'secondary' ?>">
                        <?= (hasPermission('excel_uploads_edit') || hasRole('admin')) ? '✓ Allowed' : '✗ Denied' ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-trash text-danger"></i>
                    <div class="small">Delete</div>
                    <div class="badge bg-<?= (hasPermission('excel_uploads_delete') || hasRole('admin')) ? 'success' : 'secondary' ?>">
                        <?= (hasPermission('excel_uploads_delete') || hasRole('admin')) ? '✓ Allowed' : '✗ Denied' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <h5><i class="fas fa-edit text-warning me-2"></i>Rename File</h5>
        <form method="POST">
            <input type="hidden" name="file_id" id="editFileId">
            <label class="form-label">New File Name (without extension)</label>
            <input type="text" name="new_name" id="editFileName" class="form-control" required>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" name="rename_file" class="btn btn-warning" style="flex:1;"><i class="fas fa-save me-1"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Edit Modal Functions
function openEditModal(id, fileName) {
    // Remove extension from file name for editing
    let nameWithoutExt = fileName.replace(/\.[^/.]+$/, '');
    document.getElementById('editFileId').value = id;
    document.getElementById('editFileName').value = nameWithoutExt;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Delete Confirmation with SweetAlert
function confirmDelete(id, fileName) {
    Swal.fire({
        title: '⚠️ Delete File?',
        html: `Are you sure you want to delete <strong>"${fileName}"</strong>?<br><br>This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'excel_uploads.php?delete=' + id;
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    let editModal = document.getElementById('editModal');
    if(event.target == editModal) {
        editModal.style.display = 'none';
    }
}

// Mobile menu toggle
const menuToggleBtn = document.createElement('button');
menuToggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
menuToggleBtn.className = 'menu-toggle-btn';
menuToggleBtn.style.position = 'fixed';
menuToggleBtn.style.bottom = '20px';
menuToggleBtn.style.right = '20px';
menuToggleBtn.style.background = '#4f46e5';
menuToggleBtn.style.color = 'white';
menuToggleBtn.style.border = 'none';
menuToggleBtn.style.width = '50px';
menuToggleBtn.style.height = '50px';
menuToggleBtn.style.borderRadius = '50%';
menuToggleBtn.style.zIndex = '1001';
menuToggleBtn.style.cursor = 'pointer';
menuToggleBtn.style.display = 'none';
document.body.appendChild(menuToggleBtn);

const sidebar = document.querySelector('.sidebar');
function checkScreenSize() {
    if (window.innerWidth <= 992) {
        menuToggleBtn.style.display = 'flex';
        menuToggleBtn.style.alignItems = 'center';
        menuToggleBtn.style.justifyContent = 'center';
        if(sidebar) sidebar.classList.remove('active');
    } else {
        menuToggleBtn.style.display = 'none';
        if(sidebar) sidebar.classList.add('active');
    }
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
if(menuToggleBtn) {
    menuToggleBtn.addEventListener('click', function() { 
        if(sidebar) sidebar.classList.toggle('active'); 
    });
}
</script>
</body>
</html>