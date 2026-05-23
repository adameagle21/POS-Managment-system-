<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

$search = $_GET['q'] ?? '';
$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 AND (product_name LIKE '%$search%' OR sku LIKE '%$search%') LIMIT 30");
?>

<div class="product-grid">
    <?php if(mysqli_num_rows($products) > 0): ?>
        <?php while($p = mysqli_fetch_assoc($products)): ?>
            <div class="product-card" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['product_name']) ?>', <?= $p['price_selling'] ?>, <?= $p['price_regular'] ?>, <?= $p['quantity'] ?>)">
                <div>
                    <?php if($p['image'] && file_exists('assets/uploads/' . $p['image'])): ?>
                        <img src="assets/uploads/<?= $p['image'] ?>" class="product-img" onerror="this.src='https://placehold.co/80x80?text=No+Image'">
                    <?php else: ?>
                        <div class="product-img" style="background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto; width:80px;height:80px;border-radius:12px;">
                            <i class="fas fa-box fa-2x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
                <div class="product-price">$<?= number_format($p['price_selling'], 2) ?></div>
                <div class="product-stock"><i class="fas fa-boxes me-1"></i> Stock: <?= $p['quantity'] ?></div>
                <small class="text-muted"><?= $p['sku'] ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted py-5" style="grid-column: 1/-1;">
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <p>No products found</p>
        </div>
    <?php endif; ?>
</div>

<style>
.product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; margin-bottom: 10px; background: #f1f5f9; }
.product-card { background: white; border-radius: 16px; padding: 15px; cursor: pointer; transition: all 0.3s; border: 1px solid #e2e8f0; text-align: center; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: #4f46e5; }
.product-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; }
.product-price { font-size: 1.2rem; font-weight: 700; color: #4f46e5; }
.product-stock { font-size: 0.7rem; color: #64748b; margin-top: 5px; }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 5px; }
</style>