<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, ['ID', 'Product Name', 'SKU', 'Unit', 'Category', 'Quantity', 'Alert Quantity', 'Regular Price', 'Selling Price', 'Last Price', 'Created Date']);

// Data
$products = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");

while($row = mysqli_fetch_assoc($products)) {
    fputcsv($output, [
        $row['id'],
        $row['product_name'],
        $row['sku'],
        $row['unit'],
        $row['category_name'] ?? 'Uncategorized',
        $row['quantity'],
        $row['alert_quantity'],
        $row['price_regular'],
        $row['price_selling'],
        $row['price_last'],
        $row['created_at']
    ]);
}

fclose($output);
exit();
?>