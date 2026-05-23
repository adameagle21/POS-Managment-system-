<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check permission - only users with delete permission or admin can delete
if(!hasPermission('products_delete') && !hasRole('admin')) {
    $_SESSION['error_msg'] = "You don't have permission to delete products!";
    header("Location: products.php");
    exit();
}

$id = $_GET['id'] ?? 0;

// Get product details before deleting
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));

if(!$product) {
    $_SESSION['error_msg'] = "Product not found!";
    header("Location: products.php");
    exit();
}

// Delete product image if exists
if($product['image'] && file_exists('assets/uploads/'.$product['image'])) {
    unlink('assets/uploads/'.$product['image']);
}

// Delete product from database
if(mysqli_query($conn, "DELETE FROM products WHERE id=$id")) {
    $_SESSION['success_msg'] = "Product '" . htmlspecialchars($product['product_name']) . "' deleted successfully!";
} else {
    $_SESSION['error_msg'] = "Error deleting product: " . mysqli_error($conn);
}

header("Location: products.php");
exit();
?>