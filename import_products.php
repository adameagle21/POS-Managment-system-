<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

$message = '';
$error = '';
$imported = 0;
$errors = 0;

// Function to generate SKU automatically
function generateSKUAuto($product_name, $conn) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $product_name), 0, 3));
    if(empty($prefix)) $prefix = 'PRD';
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
    $count = mysqli_fetch_assoc($result);
    $new_id = $count['count'] + 1;
    
    return $prefix . '-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
}

if(isset($_POST['import'])) {
    if(isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $file = $_FILES['excel_file']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        
        if($extension == 'csv') {
            $file_handle = fopen($file, 'r');
            if($file_handle !== false) {
                // Skip header row
                $headers = fgetcsv($file_handle);
                
                while(($row = fgetcsv($file_handle)) !== false) {
                    // Skip empty rows
                    if(empty($row[0]) && empty($row[1])) continue;
                    
                    $product_name = mysqli_real_escape_string($conn, trim($row[0] ?? ''));
                    $sku = mysqli_real_escape_string($conn, trim($row[1] ?? ''));
                    $unit = mysqli_real_escape_string($conn, trim($row[2] ?? 'pieces'));
                    $category_name = mysqli_real_escape_string($conn, trim($row[3] ?? ''));
                    $quantity = (int)($row[4] ?? 0);
                    $alert_qty = (int)($row[5] ?? 5);
                    $price_regular = (float)($row[6] ?? 0);
                    $price_selling = (float)($row[7] ?? 0);
                    $price_last = (float)($row[8] ?? 0);
                    
                    if(empty($product_name)) {
                        $errors++;
                        continue;
                    }
                    
                    // Get or create category
                    $category_id = 'NULL';
                    if(!empty($category_name)) {
                        $cat_check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$category_name'");
                        if(mysqli_num_rows($cat_check) > 0) {
                            $cat = mysqli_fetch_assoc($cat_check);
                            $category_id = $cat['id'];
                        } else {
                            mysqli_query($conn, "INSERT INTO categories (name) VALUES ('$category_name')");
                            $category_id = mysqli_insert_id($conn);
                        }
                    }
                    
                    // Auto generate SKU if empty
                    if(empty($sku)) {
                        $sku = generateSKUAuto($product_name, $conn);
                    }
                    
                    // Check if SKU already exists
                    $sku_check = mysqli_query($conn, "SELECT id FROM products WHERE sku = '$sku'");
                    if(mysqli_num_rows($sku_check) > 0) {
                        $sku = $sku . '-' . rand(100, 999);
                    }
                    
                    $query = "INSERT INTO products (product_name, sku, unit, category_id, quantity, alert_quantity, price_regular, price_selling, price_last) 
                              VALUES ('$product_name', '$sku', '$unit', $category_id, '$quantity', '$alert_qty', '$price_regular', '$price_selling', '$price_last')";
                    
                    if(mysqli_query($conn, $query)) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                }
                fclose($file_handle);
                
                if($imported > 0) {
                    $message = "<div class='alert alert-success'>
                        <i class='fas fa-check-circle me-2'></i> 
                        <strong>Import Successful!</strong><br>
                        Imported: $imported products | Failed: $errors products
                    </div>";
                } else {
                    $error = "<div class='alert alert-danger'>
                        <i class='fas fa-exclamation-circle me-2'></i> 
                        No products were imported. Please check your file format.
                    </div>";
                }
            } else {
                $error = "<div class='alert alert-danger'>✗ Could not open the file</div>";
            }
        } else {
            $error = "<div class='alert alert-danger'>✗ Please upload a CSV file (Comma Separated Values)</div>";
        }
    } else {
        $error = "<div class='alert alert-danger'>✗ Please select a file to import</div>";
    }
}

// Download sample CSV
if(isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="product_sample.csv"');
    
    $output = fopen('php://output', 'w');
    // Headers
    fputcsv($output, ['Product Name', 'SKU (leave empty for auto)', 'Unit', 'Category', 'Quantity', 'Alert Quantity', 'Regular Price', 'Selling Price', 'Last Price']);
    // Sample row 1
    fputcsv($output, ['Premium Car Mat', 'CM001', 'pieces', 'Car Mats', '50', '10', '25.00', '45.00', '40.00']);
    // Sample row 2 (SKU empty - will auto generate)
    fputcsv($output, ['Leather Seat Cover', '', 'pieces', 'Seat Covers', '30', '5', '80.00', '150.00', '130.00']);
    // Sample row 3
    fputcsv($output, ['Sports Steering Cover', 'ST001', 'pieces', 'Steering Covers', '100', '20', '10.00', '25.00', '20.00']);
    // Sample row 4
    fputcsv($output, ['Royal Car Perfume', '', 'pieces', 'Car Perfumes', '200', '30', '5.00', '15.00', '12.00']);
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Products - Adam Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;}
        .sidebar{width:280px;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);position:fixed;height:100vh;padding:20px 0;}
        .sidebar-header{text-align:center;padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px;}
        .sidebar-header h3{color:#FFD700;}
        .sidebar-menu{padding:0 15px;}
        .menu-item{display:flex;align-items:center;gap:12px;padding:12px 18px;margin:5px 0;color:#cbd5e1;text-decoration:none;border-radius:12px;transition:0.3s;}
        .menu-item:hover{background:rgba(255,255,255,0.1);color:white;}
        .menu-item i{width:22px;}
        .menu-item.active{background:rgba(79,70,229,0.2);color:white;}
        .menu-divider{height:1px;background:rgba(255,255,255,0.1);margin:15px 18px;}
        .main-content{margin-left:280px;padding:20px;}
        .top-bar{background:white;border-radius:16px;padding:15px 25px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;}
        .page-title{font-size:1.5rem;font-weight:700;color:#1e293b;}
        .user-avatar{width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;}
        .import-card{background:white;border-radius:20px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .btn-import{background:linear-gradient(135deg,#4f46e5,#4338ca);color:white;border:none;padding:12px 30px;border-radius:12px;font-weight:600;transition:0.3s;}
        .btn-import:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(79,70,229,0.4);}
        .btn-download{background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;padding:12px 30px;border-radius:12px;font-weight:600;text-decoration:none;display:inline-block;transition:0.3s;}
        .btn-download:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(16,185,129,0.4);color:white;}
        .btn-back{background:#64748b;color:white;padding:12px 30px;border-radius:12px;text-decoration:none;display:inline-block;transition:0.3s;}
        .btn-back:hover{background:#475569;color:white;}
        .file-input{border:2px solid #e2e8f0;border-radius:12px;padding:12px;width:100%;}
        .file-input:focus{border-color:#4f46e5;outline:none;}
        .instruction-list{background:#f8fafc;border-radius:12px;padding:15px 20px;}
        .instruction-list li{margin-bottom:8px;}
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
        <div class="menu-divider"></div>
        <a href="logout.php" class="menu-item" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title"><i class="fas fa-file-import me-2"></i>Import Products (CSV)</h1>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A',0,1)) ?></div>
    </div>
    
    <div class="import-card">
        <?= $message ?>
        <?= $error ?>
        
        <!-- Instructions -->
        <div class="instruction-list mb-4">
            <h6><i class="fas fa-info-circle text-primary me-2"></i>How to Import Products:</h6>
            <ol class="mb-0">
                <li>Click <strong>"Download Sample CSV"</strong> to get the template file</li>
                <li>Open the file in <strong>Microsoft Excel, Google Sheets, or any text editor</strong></li>
                <li>Add your products in the same format (one product per row)</li>
                <li>Save the file as <strong>CSV (Comma delimited)</strong> format</li>
                <li>Click "Choose File" and select your CSV file</li>
                <li>Click <strong>"Import Products"</strong> to upload</li>
            </ol>
        </div>
        
        <!-- Download Sample -->
        <div class="text-center mb-4">
            <a href="?download_sample=1" class="btn-download">
                <i class="fas fa-download me-2"></i> Download Sample CSV Template
            </a>
        </div>
        
        <hr>
        
        <!-- Import Form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label class="fw-semibold mb-2">Select CSV File</label>
                <input type="file" name="excel_file" class="file-input" accept=".csv" required>
                <small class="text-muted mt-1 d-block">Only CSV files are accepted (max 10MB)</small>
            </div>
            <div class="d-flex gap-3 mt-4">
                <button type="submit" name="import" class="btn-import">
                    <i class="fas fa-upload me-2"></i> Import Products
                </button>
                <a href="products.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Products
                </a>
            </div>
        </form>
        
        <!-- CSV Format Example -->
        <hr class="mt-4">
        <div class="mt-3">
            <h6><i class="fas fa-table me-2"></i>CSV Format Example:</h6>
            <div class="bg-dark text-white p-3 rounded" style="font-family: monospace; font-size: 12px; overflow-x: auto;">
                Product Name,SKU (leave empty for auto),Unit,Category,Quantity,Alert Quantity,Regular Price,Selling Price,Last Price<br>
                Premium Car Mat,CM001,pieces,Car Mats,50,10,25.00,45.00,40.00<br>
                Leather Seat Cover,,pieces,Seat Covers,30,5,80.00,150.00,130.00<br>
                Sports Steering Cover,ST001,pieces,Steering Covers,100,20,10.00,25.00,20.00
            </div>
        </div>
    </div>
</div>
</body>
</html>