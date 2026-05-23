<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.quantity > 0";

if(!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.product_name LIKE '%$search_term%' OR p.sku LIKE '%$search_term%')";
}

if(!empty($category)) {
    $query .= " AND c.name = '$category'";
}

$query .= " ORDER BY p.product_name LIMIT 30";
$products = mysqli_query($conn, $query);
?>

<div class="product-grid">
    <?php if(mysqli_num_rows($products) > 0): ?>
        <?php while($p = mysqli_fetch_assoc($products)): ?>
            <div class="product-card" onclick="showPriceModal(<?= $p['id'] ?>, '<?= addslashes($p['product_name']) ?>', <?= $p['price_selling'] ?>, <?= $p['price_regular'] ?>, <?= $p['price_last'] ?>, <?= $p['quantity'] ?>)">
                <?php if(!empty($p['image']) && file_exists('assets/uploads/' . $p['image'])): ?>
                    <img src="assets/uploads/<?= $p['image'] ?>" class="product-img">
                <?php else: ?>
                    <div class="product-img-placeholder"><i class="fas fa-box"></i></div>
                <?php endif; ?>
                <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
                <div class="product-price">$<?= number_format($p['price_selling'], 2) ?></div>
                <div class="product-stock"><?= $p['quantity'] ?> left</div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted py-5">No products found</div>
    <?php endif; ?>
</div>

<style>
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
.product-card { background: white; border-radius: 14px; padding: 10px; cursor: pointer; text-align: center; border: 1px solid #e2e8f0; transition: 0.3s; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #4f46e5; }
.product-img { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; margin-bottom: 8px; }
.product-img-placeholder { width: 70px; height: 70px; border-radius: 10px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
.product-name { font-weight: 600; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.product-price { font-size: 0.9rem; font-weight: 700; color: #4f46e5; }
.product-stock { font-size: 0.6rem; color: #10b981; }
</style>