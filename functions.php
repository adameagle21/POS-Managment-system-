<?php
/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Get status badge
 */
function getStatusBadge($status) {
    if($status == 'active') {
        return '<span class="badge-active"><i class="fas fa-check-circle"></i> Active</span>';
    }
    return '<span class="badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
}

/**
 * Get stock status
 */
function getStockStatus($quantity, $alert_quantity) {
    if($quantity <= 0) {
        return '<span class="stock-badge stock-out">Out of Stock</span>';
    } elseif($quantity <= $alert_quantity) {
        return '<span class="stock-badge stock-low">Low Stock</span>';
    }
    return '<span class="stock-badge stock-good">In Stock</span>';
}

/**
 * Generate invoice number
 */
function generateInvoice() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * Get user permissions
 */
function hasPermission($permission) {
    if(hasRole('admin')) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    return in_array($permission, $permissions);
}

/**
 * Get total sales
 */
function getTotalSales() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM sales");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

/**
 * Get total profit
 */
function getTotalProfit() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(profit),0) as profit FROM sales");
    $row = mysqli_fetch_assoc($result);
    return $row['profit'];
}

/**
 * Get total expenses
 */
function getTotalExpenses() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM expenses");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}
?>