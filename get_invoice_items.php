<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

$invoice_no = $_GET['invoice_no'] ?? '';
$items = mysqli_query($conn, "SELECT s.*, p.product_name, p.sku 
                              FROM sales s 
                              JOIN products p ON s.product_id = p.id 
                              WHERE s.invoice_no = '$invoice_no' 
                              ORDER BY s.id");

$result = [];
while($item = mysqli_fetch_assoc($items)) {
    $result[] = [
        'id' => $item['id'],
        'product_name' => $item['product_name'],
        'sku' => $item['sku'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['unit_price'],
        'total' => $item['total'],
        'profit' => $item['profit'],
        'sale_date' => $item['sale_date']
    ];
}
echo json_encode($result);
?>