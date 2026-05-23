<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

$id = $_GET['id'] ?? 0;
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_name, price_selling FROM products WHERE id = $id"));

echo json_encode([
    'product_name' => $product['product_name'],
    'price_selling' => $product['price_selling']
]);
?>