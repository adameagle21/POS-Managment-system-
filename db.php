<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'adam_car_db';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// PERMISSION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && strtolower($_SESSION['role']) == strtolower($role);
}

function hasPermission($permission) {
    if(!isset($_SESSION['user_id'])) return false;
    
    // Admin role has all permissions
    if(isset($_SESSION['role']) && (strtolower($_SESSION['role']) == 'admin' || strtolower($_SESSION['role']) == 'administrator')) {
        return true;
    }
    
    $permissions = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
    
    if(is_string($permissions) && $permissions != '') {
        $permissions = explode(',', $permissions);
    }
    
    if(is_string($permissions) && $permissions == '') {
        $permissions = [];
    }
    
    if(!is_array($permissions)) {
        $permissions = [];
    }
    
    return in_array($permission, $permissions);
}

function canAccess($feature, $redirect = true) {
    if(!isset($_SESSION['user_id'])) {
        if($redirect) header("Location: login.php");
        return false;
    }
    
    if(hasRole('admin') || hasRole('administrator')) return true;
    
    $hasAccess = hasPermission($feature);
    
    if(!$hasAccess && $redirect) {
        header("Location: index.php");
        exit();
    }
    
    return $hasAccess;
}

function redirect($page) {
    header("Location: " . $page);
    exit();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function generateInvoice() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

// ============================================
// DEFAULT PERMISSIONS CHECK
// ============================================

// Check if user has access to Expenses
function canAccessExpenses() {
    return hasPermission('expenses_access') || hasRole('admin');
}

// Check if user has access to Categories
function canAccessCategories() {
    return hasPermission('categories_access') || hasRole('admin');
}

// Check if user has access to Discounts
function canAccessDiscounts() {
    return hasPermission('discounts_access') || hasRole('admin');
}

// Check if user has access to Reports
function canAccessReports() {
    return hasPermission('reports_access') || hasRole('admin');
}

// Check if user has access to Users
function canAccessUsers() {
    return hasPermission('users_access') || hasRole('admin');
}

// Check if user has access to Products
function canAccessProducts() {
    return hasPermission('products_access') || hasRole('admin');
}

// Check if user has access to Sales
function canAccessSales() {
    return hasPermission('sales_access') || hasRole('admin');
}

// Check if user has access to POS
function canAccessPOS() {
    return hasPermission('pos_access') || hasRole('admin');
}

// ============================================
// DASHBOARD PERMISSIONS
// ============================================

function canViewDashboard() {
    return hasPermission('dashboard_access') || hasRole('admin');
}

function canViewTotalSales() {
    return hasPermission('dashboard_total_sales_view') || hasRole('admin');
}

function canViewTotalProfit() {
    return hasPermission('dashboard_total_profit_view') || hasRole('admin');
}

function canViewTotalExpenses() {
    return hasPermission('dashboard_total_expenses_view') || hasRole('admin');
}

function canViewTodaySales() {
    return hasPermission('dashboard_today_sales_view') || hasRole('admin');
}

function canViewTodayProfit() {
    return hasPermission('dashboard_today_profit_view') || hasRole('admin');
}

function canViewTotalProducts() {
    return hasPermission('dashboard_total_products_view') || hasRole('admin');
}

function canViewTotalUsers() {
    return hasPermission('dashboard_total_users_view') || hasRole('admin');
}

function canViewLowStock() {
    return hasPermission('dashboard_low_stock_view') || hasRole('admin');
}

function canViewChart() {
    return hasPermission('dashboard_chart_view') || hasRole('admin');
}

function canViewRecentSales() {
    return hasPermission('dashboard_recent_sales_view') || hasRole('admin');
}

function canUseCalculator() {
    return hasPermission('dashboard_calculator') || hasRole('admin');
}

function canQuickStartSale() {
    return hasPermission('quick_start_sale') || hasRole('admin');
}

function canQuickAddProduct() {
    return hasPermission('quick_add_product') || hasRole('admin');
}

// ============================================
// REFRESH USER PERMISSIONS
// ============================================

function refreshUserPermissions() {
    global $conn;
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $result = mysqli_query($conn, "SELECT permissions, role FROM users WHERE id = $user_id");
        if($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['role'] = $user['role'];
            if(!empty($user['permissions'])) {
                $_SESSION['permissions'] = explode(',', $user['permissions']);
            } else {
                $_SESSION['permissions'] = [];
            }
            return true;
        }
    }
    return false;
}
?>